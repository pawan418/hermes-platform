<?php

namespace App\Modules\AICore\Services\LLM\Drivers;

use App\Modules\AICore\Services\LLM\Contracts\LLMDriverInterface;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAIDriver implements LLMDriverInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $tenantManager = app(TenantManager::class);
        $tenant = $tenantManager->getTenant();
        
        // Resolve key: check tenant custom settings first, then env fallback
        $this->apiKey = $tenant?->getSetting('keys.openai_api_key') 
            ?? env('OPENAI_API_KEY', '');
    }

    /**
     * Send chat messages to OpenAI.
     */
    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? 'gpt-4o';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2048;

        $startTime = microtime(true);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => $messages,
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]);

        $latency = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            Log::error('OpenAI Chat Completion API Error: ' . $response->body());
            throw new \Exception('OpenAI API Error: ' . $response->json('error.message', 'Unknown Error'));
        }

        $result = $response->json();
        
        $content = $result['choices'][0]['message']['content'] ?? '';
        $toolCalls = $result['choices'][0]['message']['tool_calls'] ?? null;
        
        // Estimate token cost (GPT-4o standard: $5/M input, $15/M output tokens)
        $inputTokens = $result['usage']['prompt_tokens'] ?? 0;
        $outputTokens = $result['usage']['completion_tokens'] ?? 0;
        $cost = ($inputTokens * 0.000005) + ($outputTokens * 0.000015);

        return [
            'content' => $content,
            'tool_calls' => $toolCalls,
            'cost' => $cost,
            'latency_ms' => $latency,
        ];
    }

    /**
     * Generate text embeddings.
     */
    public function generateEmbeddings(string $text): array
    {
        $startTime = microtime(true);

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->apiKey,
            'Content-Type' => 'application/json',
        ])->post('https://api.openai.com/v1/embeddings', [
            'input' => $text,
            'model' => 'text-embedding-3-large',
        ]);

        if (!$response->successful()) {
            Log::error('OpenAI Embedding API Error: ' . $response->body());
            throw new \Exception('OpenAI Embeddings Error: ' . $response->json('error.message', 'Unknown Error'));
        }

        return $response->json('data.0.embedding') ?? [];
    }
}
