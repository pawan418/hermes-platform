<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuditLogMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // We only audit write operations (POST, PUT, PATCH, DELETE)
        if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            $tenantManager = app(TenantManager::class);

            if ($tenantManager->hasTenant()) {
                $user = Auth::user();
                $actionName = $request->route() ? $request->route()->getName() : $request->path();
                
                // Scrub sensitive fields
                $payload = $this->scrubSensitiveFields($request->all());

                AuditLog::create([
                    'tenant_id' => $tenantManager->getTenantId(),
                    'user_id' => $user?->id,
                    'action' => $request->method() . ' ' . ($actionName ?? $request->path()),
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'new_values' => $payload,
                ]);
            }
        }

        return $response;
    }

    /**
     * Remove sensitive parameter keys.
     */
    protected function scrubSensitiveFields(array $data): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'api_key', 'secret', 'key_hash', 'raw_key'];
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->scrubSensitiveFields($value);
            } elseif (in_array(strtolower($key), $sensitiveKeys)) {
                $data[$key] = '********';
            }
        }

        return $data;
    }
}
