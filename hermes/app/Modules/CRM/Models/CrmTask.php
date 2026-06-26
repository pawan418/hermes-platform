<?php

namespace App\Modules\CRM\Models;

use App\Models\User;
use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CrmTask extends Model
{
    use BelongsToTenant;

    protected $table = 'crm_tasks';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'title',
        'description',
        'status',
        'due_at',
    ];

    protected $casts = [
        'due_at' => 'datetime',
    ];

    /**
     * Agent owner assigned to complete the action.
     */
    public function assignedAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
