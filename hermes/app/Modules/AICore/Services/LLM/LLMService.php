<?php

namespace App\Modules\AICore\Services\LLM;

use App\Modules\AICore\Services\LLM\Contracts\LLMDriverInterface;
use App\Modules\AICore\Services\LLM\Drivers\AnthropicDriver;
use App\Modules\AICore\Services\LLM\Drivers\GeminiDriver;
use App\Modules\AICore\Services\LLM\Drivers\OllamaDriver;
use App\Modules\AICore\Services\LLM\Drivers\OpenAIDriver;
use App\Services\TenantManager;

class LLMService
{
    /**
     * Resolve the configured driver for a provider.
     */
    public function driver(?string $provider = null): LLMDriverInterface
    {
        if (empty($provider)) {
            $tenantManager = app(TenantManager::class);
            $tenant = $tenantManager->getTenant();
            $provider = $tenant?->getSetting('ai.default_provider') 
                ?? env('DEFAULT_LLM_PROVIDER', 'openai');
        }

        return match (strtolower($provider)) {
            'openai' => new OpenAIDriver(),
            'anthropic' => new AnthropicDriver(),
            'gemini' => new GeminiDriver(),
            'ollama' => new OllamaDriver(),
            default => throw new \InvalidArgumentException("AI provider '{$provider}' is not supported on Hermes."),
        };
    }

    /**
     * Run chat completion against the resolved driver.
     */
    public function chat(array $messages, array $options = [], ?string $provider = null): array
    {
        return $this->driver($provider)->chat($messages, $options);
    }

    /**
     * Generate text embeddings against the resolved driver.
     */
    public function embed(string $text, ?string $provider = null): array
    {
        // Embeddings are generally tied to OpenAI (text-embedding-3-large) or local Ollama
        if (empty($provider)) {
            $tenantManager = app(TenantManager::class);
            $tenant = $tenantManager->getTenant();
            $provider = $tenant?->getSetting('ai.default_embedding') 
                ?? env('DEFAULT_EMBEDDING_PROVIDER', 'openai');
        }

        return $this->driver($provider)->generateEmbeddings($text);
    }
}
