<?php

namespace App\Modules\AICore\Services\LLM\Contracts;

interface LLMDriverInterface
{
    /**
     * Send a list of conversation messages to the LLM.
     * Returns an array: ['content' => string, 'tool_calls' => array|null, 'cost' => float, 'latency_ms' => int]
     */
    public function chat(array $messages, array $options = []): array;

    /**
     * Generate vector embeddings for a given block of text.
     * Returns an array of floats.
     */
    public function generateEmbeddings(string $text): array;
}
