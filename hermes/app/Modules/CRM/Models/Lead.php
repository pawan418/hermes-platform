<?php

namespace App\Modules\CRM\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'company_id',
        'title',
        'source',
        'status',
        'value',
        'assigned_to',
    ];

    protected $casts = [
        'value' => 'float',
    ];

    /**
     * Associated contact profile.
     */
    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Associated company entity.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Agent assigned to manage this lead.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * Deals created from this lead.
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }
}
