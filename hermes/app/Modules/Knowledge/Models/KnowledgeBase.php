<?php

namespace App\Modules\Knowledge\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeBase extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
    ];

    /**
     * Documents belonging to this knowledge base.
     */
    public function documents(): HasMany
    {
        return $this->hasMany(Document::class);
    }
}
