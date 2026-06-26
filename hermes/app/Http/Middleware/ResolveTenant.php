<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tenantManager = app(TenantManager::class);
        $host = $request->getHost();
        $tenant = null;

        // 1. Check custom headers (for API / external workflow engine)
        if ($request->hasHeader('X-Tenant-Id')) {
            $tenant = Tenant::find($request->header('X-Tenant-Id'));
        } elseif ($request->hasHeader('X-Tenant-Domain')) {
            $tenant = Tenant::where('domain', $request->header('X-Tenant-Domain'))->first();
        } else {
            // 2. Resolve via host domain/subdomain
            $tenant = Tenant::where('domain', $host)->first();

            // 3. Dev Fallback for localhost testing
            if (!$tenant && ($host === 'localhost' || $host === '127.0.0.1' || str_contains($host, '.test'))) {
                $tenant = Tenant::first();
            }
        }

        // 4. Verification and assignment
        if (!$tenant) {
            abort(Response::HTTP_NOT_FOUND, 'Company domain not registered on Hermes.');
        }

        if ($tenant->status === 'suspended') {
            abort(Response::HTTP_FORBIDDEN, 'Company access has been suspended.');
        }

        $tenantManager->setTenant($tenant);

        return $next($request);
    }
}
