<?php

namespace App\Modules\AICore\Services\LLM\Drivers;

use App\Modules\AICore\Services\LLM\Contracts\LLMDriverInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OllamaDriver implements LLMDriverInterface
{
    protected string $host;

    public function __construct()
    {
        $this->host = env('OLLAMA_HOST', 'http://host.docker.internal:11434');
    }

    /**
     * Send chat messages to local Ollama server.
     */
    public function chat(array $messages, array $options = []): array
    {
        $model = $options['model'] ?? 'llama3';
        $temperature = $options['temperature'] ?? 0.7;

        $startTime = microtime(true);

        // Ollama expectations: system, user, assistant messages are compatible
        $response = Http::post($this->host . '/api/chat', [
            'model' => $model,
            'messages' => $messages,
            'options' => [
                'temperature' => $temperature,
            ],
            'stream' => false,
        ]);

        $latency = (int) ((microtime(true) - $startTime) * 1000);

        if (!$response->successful()) {
            Log::error('Ollama API Chat Error: ' . $response->body());
            throw new \Exception('Ollama API Chat Error: ' . $response->body());
        }

        $result = $response->json();
        
        $content = $result['message']['content'] ?? '';
        $toolCalls = null;

        // Local computing has no LLM provider cost
        $cost = 0.00;

        return [
            'content' => $content,
            'tool_calls' => $toolCalls,
            'cost' => $cost,
            'latency_ms' => $latency,
        ];
    }

    /**
     * Generate text embeddings locally via Ollama.
     */
    public function generateEmbeddings(string $text): array
    {
        $response = Http::post($this->host . '/api/embeddings', [
            'model' => 'nomic-embed-text',
            'prompt' => $text,
        ]);

        if (!$response->successful()) {
            Log::error('Ollama Embedding API Error: ' . $response->body());
            throw new \Exception('Ollama Embeddings Error: ' . $response->body());
        }

        return $response->json('embedding') ?? [];
    }
}
