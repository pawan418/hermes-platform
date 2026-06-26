<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiKey extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'key_hash',
        'key_peek',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    /**
     * Generate a new raw api key and return it alongside the hashed version.
     */
    public static function generate(string $name, ?int $expiresInDays = null): array
    {
        $raw = 'hr_' . Str::random(40);
        $peek = substr($raw, 0, 8);
        $hash = hash('sha256', $raw);

        $apiKey = self::create([
            'name' => $name,
            'key_hash' => $hash,
            'key_peek' => $peek,
            'expires_at' => $expiresInDays ? now()->addDays($expiresInDays) : null,
        ]);

        return [
            'api_key' => $apiKey,
            'raw_key' => $raw,
        ];
    }

    /**
     * Validate an incoming raw key.
     */
    public static function findByKey(string $rawKey): ?self
    {
        $hash = hash('sha256', $rawKey);
        
        return self::where('key_hash', $hash)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->first();
    }
}
