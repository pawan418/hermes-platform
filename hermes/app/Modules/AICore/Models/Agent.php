<?php

namespace App\Modules\AICore\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'provider',
        'model',
        'temperature',
        'max_tokens',
        'allowed_tools',
        'system_prompt',
        'prompt_template_id',
    ];

    protected $casts = [
        'allowed_tools' => 'array',
        'temperature' => 'float',
        'max_tokens' => 'integer',
    ];

    /**
     * Get conversations associated with this agent.
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class);
    }

    /**
     * Get the prompt template associated with this agent.
     */
    public function promptTemplate(): BelongsTo
    {
        return $this->belongsTo(PromptTemplate::class);
    }

    /**
     * Compile and retrieve the resolved system prompt text for the agent.
     */
    public function compileSystemPrompt(array $variables = []): string
    {
        // 1. Check if the agent references a prompt template
        if ($this->promptTemplate) {
            $activeVersion = $this->promptTemplate->activeVersion();
            
            if ($activeVersion) {
                return $this->interpolateVariables($activeVersion->content, $variables);
            }
        }

        // 2. Fall back to static system prompt
        if ($this->system_prompt) {
            return $this->interpolateVariables($this->system_prompt, $variables);
        }

        return 'You are a helpful AI assistant.';
    }

    /**
     * Replace {variable_name} syntax with matching values.
     */
    protected function interpolateVariables(string $content, array $variables): string
    {
        foreach ($variables as $key => $value) {
            $content = str_replace('{' . $key . '}', (string) $value, $content);
        }

        return $content;
    }
}
