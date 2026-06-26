<?php

namespace App\Modules\AICore\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PromptTemplate extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'slug',
        'description',
        'variables',
    ];

    protected $casts = [
        'variables' => 'array',
    ];

    /**
     * Versions of this prompt template.
     */
    public function versions(): HasMany
    {
        return $this->hasMany(PromptVersion::class)->orderBy('version', 'desc');
    }

    /**
     * Retrieve the active version of this template.
     */
    public function activeVersion(): ?PromptVersion
    {
        return $this->versions()->where('is_active', true)->first() 
            ?? $this->versions()->first(); // fallback to latest version if none explicitly active
    }
}
