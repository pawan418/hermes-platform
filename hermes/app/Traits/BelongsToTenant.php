<?php

namespace App\Traits;

use App\Models\Scopes\TenantScope;
use App\Services\TenantManager;

trait BelongsToTenant
{
    /**
     * Boot the trait to apply the tenant scope and creation hooks.
     */
    protected static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function ($model) {
            $tenantManager = app(TenantManager::class);

            if ($tenantManager->hasTenant() && !isset($model->tenant_id)) {
                $model->tenant_id = $tenantManager->getTenantId();
            }
        });
    }

    /**
     * Relationship back to the Tenant.
     */
    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }
}
