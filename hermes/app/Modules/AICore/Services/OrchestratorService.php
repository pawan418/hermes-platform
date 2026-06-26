<?php

namespace App\Modules\AICore\Services;

use App\Modules\AICore\Models\Agent;
use App\Modules\AICore\Models\Conversation;
use App\Modules\AICore\Models\Message;
use App\Modules\AICore\Services\LLM\LLMService;
use App\Modules\AICore\Services\VectorStore\QdrantService;
use Illuminate\Support\Facades\Log;

class OrchestratorService
{
    protected LLMService $llm;
    protected QdrantService $qdrant;

    public function __construct(LLMService $llm, QdrantService $qdrant)
    {
        $this->llm = $llm;
        $this->qdrant = $qdrant;
    }

    /**
     * Process an incoming message inside a conversation and return the AI agent response.
     */
    public function handleMessage(Conversation $conversation, string $userMessageText): Message
    {
        // 1. Save user message to database
        $userMessage = Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => $userMessageText,
            'created_at' => now(),
        ]);

        // 2. Determine agent if not set
        $agent = $conversation->agent;
        if (!$agent) {
            $agent = $this->routeAgent($userMessageText, $conversation->tenant_id);
            $conversation->update(['agent_id' => $agent->id]);
        }

        // 3. Retrieve Context from Vector Store (RAG)
        // Check if agent settings or context data allows RAG searching
        $ragContext = '';
        try {
            $queryEmbedding = $this->llm->embed($userMessageText);
            
            if (!empty($queryEmbedding)) {
                $chunks = $this->qdrant->searchSimilarity($queryEmbedding, 3);
                
                if (!empty($chunks)) {
                    $ragContext = "\n[Retrieved Context from company database]:\n";
                    foreach ($chunks as $chunk) {
                        $ragContext .= "- " . $chunk['text'] . "\n";
                    }
                    $ragContext .= "\nInstructions: Use the above retrieved context to answer the user request. If the context does not contain the answer, rely on your default knowledge base, but do not make up facts.\n";
                }
            }
        } catch (\Exception $e) {
            Log::warning('RAG Retrieval failed during conversation process: ' . $e->getMessage());
        }

        // 4. Compile messages history
        $compiledMessages = [];
        
        // Add Agent System Prompt (with embedded RAG context)
        $systemPrompt = $agent->compileSystemPrompt();
        if (!empty($ragContext)) {
            $systemPrompt .= $ragContext;
        }

        $compiledMessages[] = [
            'role' => 'system',
            'content' => $systemPrompt,
        ];

        // Add recent message history (up to last 15 messages)
        $history = $conversation->messages()
            ->where('id', '<', $userMessage->id)
            ->latest('id')
            ->limit(14)
            ->get()
            ->reverse();

        foreach ($history as $historyMessage) {
            $compiledMessages[] = [
                'role' => $historyMessage->role,
                'content' => $historyMessage->content,
            ];
        }

        // Append current user message
        $compiledMessages[] = [
            'role' => 'user',
            'content' => $userMessageText,
        ];

        // 5. Send payload to LLM
        $startTime = microtime(true);
        
        $llmResult = $this->llm->chat($compiledMessages, [
            'model' => $agent->model,
            'temperature' => $agent->temperature,
            'max_tokens' => $agent->max_tokens,
        ], $agent->provider);

        $latency = (int) ((microtime(true) - $startTime) * 1000);

        // 6. Save and return AI message
        return Message::create([
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => $llmResult['content'],
            'tool_calls' => $llmResult['tool_calls'],
            'cost' => $llmResult['cost'] ?? 0.00,
            'latency_ms' => $llmResult['latency_ms'] ?? $latency,
            'created_at' => now(),
        ]);
    }

    /**
     * Route user queries to the best agent slug dynamically.
     */
    public function routeAgent(string $query, int $tenantId): Agent
    {
        $agents = Agent::where('tenant_id', $tenantId)->get();

        if ($agents->isEmpty()) {
            // Fallback: create default assistant agent if none exists
            return Agent::create([
                'tenant_id' => $tenantId,
                'name' => 'General Assistant',
                'slug' => 'general',
                'provider' => 'openai',
                'model' => 'gpt-4o',
                'system_prompt' => 'You are a general administrative assistant.',
            ]);
        }

        if ($agents->count() === 1) {
            return $agents->first();
        }

        // Format agent list for classifier pass
        $agentListText = "";
        foreach ($agents as $agent) {
            $agentListText .= "- Slug: {$agent->slug} | Name: {$agent->name}\n";
        }

        $classifierPrompt = [
            [
                'role' => 'system',
                'content' => "You are the Hermes AI Routing Engine. Your job is to select the single best agent slug to handle the user's inquiry.\n" .
                             "Respond with ONLY the exact matching agent slug. Do not add formatting, markdown, or punctuation.\n\n" .
                             "Available Agents:\n" . $agentListText,
            ],
            [
                'role' => 'user',
                'content' => "User Inquiry: \"{$query}\"\n\nWhich agent slug should handle this?",
            ]
        ];

        try {
            // Run classifier on cheap default LLM (gpt-4o-mini or gemini-1.5-flash)
            $response = $this->llm->chat($classifierPrompt, [
                'model' => 'gpt-4o-mini',
                'temperature' => 0.0,
                'max_tokens' => 20,
            ]);

            $chosenSlug = trim(strtolower($response['content'] ?? ''));
            
            // Cleanup any markdown code fences the LLM might have returned
            $chosenSlug = str_replace(['`', '*'], '', $chosenSlug);

            $matchedAgent = $agents->firstWhere('slug', $chosenSlug);
            
            if ($matchedAgent) {
                return $matchedAgent;
            }
        } catch (\Exception $e) {
            Log::warning('Agent routing classification query failed: ' . $e->getMessage());
        }

        // Fallback: return first agent
        return $agents->first();
    }
}
