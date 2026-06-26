<?php

namespace App\Modules\CRM\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Pipeline extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'stages',
    ];

    protected $casts = [
        'stages' => 'array',
    ];

    /**
     * Deals inside this pipeline.
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
