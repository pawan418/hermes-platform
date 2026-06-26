<?php

namespace App\Modules\CRM\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Deal extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'pipeline_id',
        'lead_id',
        'name',
        'stage',
        'value',
        'status',
        'closed_at',
        'assigned_to',
    ];

    protected $casts = [
        'value' => 'float',
        'closed_at' => 'datetime',
    ];

    /**
     * Parent sales pipeline.
     */
    public function pipeline(): BelongsTo
    {
        return $this->belongsTo(Pipeline::class);
    }

    /**
     * Source lead if converted.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    /**
     * Owner sales agent.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
