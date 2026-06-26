<?php

namespace App\Modules\AICore\Services\LLM\Drivers;

use App\Modules\AICore\Services\LLM\Contracts\LLMDriverInterface;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AnthropicDriver implements LLMDriverInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $tenantManager = app(TenantManager::class);
        $tenant = $tenantManager->getTenant();
        
        $this->apiKey = $tenant?->getSetting('keys.anthropic_api_key') 
            ?? env('ANTHROPIC_API_KEY', '');
    }

    /**
     * Send chat messages to Anthropic Claude.
     */
    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? 'claude-3-5-sonnet-20240620';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2048;

        $startTime = microtime(true);

        // Anthropic separates system prompt from the messages array
        $systemPrompt = '';
        $filteredMessages = [];

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemPrompt .= $message['content'] . "\n";
            } else {
                $filteredMessages[] = [
                    'role' => $message['role'] === 'assistant' ? 'assistant' : 'user',
                    'content' => $message['content'],
                ];
            }
        }

        $payload = [
            'model' => $model,
            'messages' => $filteredMessages,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        if (!empty(trim($systemPrompt))) {
            $payload['system'] = trim($systemPrompt);
        }

        $response = Http::withHeaders([
            'x-api-key' => $this->apiKey,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->post('https://api.anthropic.com/v1/messages', $payload);

        $latency = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            Log::error('Anthropic API Error: ' . $response->body());
            throw new \Exception('Anthropic API Error: ' . $response->json('error.message', 'Unknown Error'));
        }

        $result = $response->json();
        
        $content = $result['content'][0]['text'] ?? '';
        $toolCalls = null; // Basic text completion, tool use can be added if needed

        // Estimate token cost (Claude 3.5 Sonnet: $3/M input, $15/M output tokens)
        $inputTokens = $result['usage']['input_tokens'] ?? 0;
        $outputTokens = $result['usage']['output_tokens'] ?? 0;
        $cost = ($inputTokens * 0.000003) + ($outputTokens * 0.000015);

        return [
            'content' => $content,
            'tool_calls' => $toolCalls,
            'cost' => $cost,
            'latency_ms' => $latency,
        ];
    }

    /**
     * Fallback to OpenAI embedding provider since Anthropic doesn't support vectorization natively.
     */
    public function generateEmbeddings(string $text): array
    {
        Log::info('Anthropic does not offer native embeddings. Falling back to OpenAI provider.');
        try {
            return (new OpenAIDriver())->generateEmbeddings($text);
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate embeddings. Anthropic requires OpenAI keys to process RAG vectors: ' . $e->getMessage());
        }
    }
}
