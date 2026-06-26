<?php

namespace App\Modules\AICore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    // Turn off timestamps - we only record created_at
    public $timestamps = false;

    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'tool_calls',
        'cost',
        'latency_ms',
        'created_at',
    ];

    protected $casts = [
        'tool_calls' => 'array',
        'cost' => 'float',
        'latency_ms' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Parent conversation relation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }
}
