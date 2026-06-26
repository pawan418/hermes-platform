<?php

namespace App\Modules\AICore\Services\LLM\Drivers;

use App\Modules\AICore\Services\LLM\Contracts\LLMDriverInterface;
use App\Services\TenantManager;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiDriver implements LLMDriverInterface
{
    protected string $apiKey;

    public function __construct()
    {
        $tenantManager = app(TenantManager::class);
        $tenant = $tenantManager->getTenant();
        
        $this->apiKey = $tenant?->getSetting('keys.gemini_api_key') 
            ?? env('GEMINI_API_KEY', '');
    }

    /**
     * Send chat messages to Google Gemini API.
     */
    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? 'gemini-1.5-flash';
        $temperature = $options['temperature'] ?? 0.7;
        $maxTokens = $options['max_tokens'] ?? 2048;

        $startTime = microtime(true);

        $contents = [];
        $systemInstruction = null;

        foreach ($messages as $message) {
            if ($message['role'] === 'system') {
                $systemInstruction = [
                    'parts' => [
                        ['text' => $message['content']]
                    ]
                ];
            } else {
                $contents[] = [
                    'role' => $message['role'] === 'assistant' ? 'model' : 'user',
                    'parts' => [
                        ['text' => $message['content']]
                    ]
                ];
            }
        }

        $payload = [
            'contents' => $contents,
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
            ]
        ];

        if ($systemInstruction) {
            $payload['systemInstruction'] = $systemInstruction;
        }

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key=" . $this->apiKey;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->post($url, $payload);

        $latency = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            Log::error('Gemini API Error: ' . $response->body());
            throw new \Exception('Gemini API Error: ' . $response->json('error.message', 'Unknown Error'));
        }

        $result = $response->json();
        
        $content = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
        $toolCalls = null; // Can support Gemini function calling if needed

        // Estimate token cost (Gemini 1.5 Flash: $0.075 / 1M input, $0.30 / 1M output tokens)
        $inputTokens = $result['usageMetadata']['promptTokenCount'] ?? 0;
        $outputTokens = $result['usageMetadata']['candidatesTokenCount'] ?? 0;
        $cost = ($inputTokens * 0.000000075) + ($outputTokens * 0.0000003);

        return [
            'content' => $content,
            'tool_calls' => $toolCalls,
            'cost' => $cost,
            'latency_ms' => $latency,
        ];
    }

    /**
     * Fallback to OpenAI embedding provider since Gemini driver requires OpenAI RAG structure.
     */
    public function generateEmbeddings(string $text): array
    {
        Log::info('Gemini driver delegating embedding generation to OpenAI.');
        try {
            return (new OpenAIDriver())->generateEmbeddings($text);
        } catch (\Exception $e) {
            throw new \Exception('Failed to generate embeddings. Gemini requires OpenAI keys to process RAG vectors: ' . $e->getMessage());
        }
    }
}
