<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\Role;
use App\Services\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_automatically_assigns_and_filters_records_by_resolved_tenant()
    {
        // 1. Create two Tenants
        $tenant1 = Tenant::create([
            'name' => 'Company One',
            'domain' => 'one.localhost',
        ]);

        $tenant2 = Tenant::create([
            'name' => 'Company Two',
            'domain' => 'two.localhost',
        ]);

        $tenantManager = app(TenantManager::class);

        // 2. Resolve to Tenant 1
        $tenantManager->setTenant($tenant1);

        // 3. Create a Role under Tenant 1
        $role1 = Role::create([
            'name' => 'Custom Agent',
            'slug' => 'custom-agent',
        ]);

        // Verify it automatically sets the tenant ID to tenant 1
        $this->assertEquals($tenant1->id, $role1->tenant_id);

        // 4. Resolve to Tenant 2
        $tenantManager->setTenant($tenant2);

        // Create a Role under Tenant 2
        $role2 = Role::create([
            'name' => 'Support Supervisor',
            'slug' => 'support-supervisor',
        ]);

        $this->assertEquals($tenant2->id, $role2->tenant_id);

        // 5. Query and check isolation
        // Scoped to Tenant 2: should only see Tenant 2's role
        $rolesCount = Role::count();
        $this->assertEquals(1, $rolesCount);
        $this->assertEquals('support-supervisor', Role::first()->slug);

        // Switch back to Tenant 1: should only see Tenant 1's role
        $tenantManager->setTenant($tenant1);
        $this->assertEquals(1, Role::count());
        $this->assertEquals('custom-agent', Role::first()->slug);
    }
}
