<?php

namespace App\Modules\ProposalGenerator\Models;

use App\Modules\CRM\Models\Lead;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Proposal extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'lead_id',
        'title',
        'description',
        'content',
        'pricing_details',
        'status',
        'signature_path',
        'signed_at',
    ];

    protected $casts = [
        'pricing_details' => 'array',
        'signed_at' => 'datetime',
    ];

    /**
     * Source CRM lead relation.
     */
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
