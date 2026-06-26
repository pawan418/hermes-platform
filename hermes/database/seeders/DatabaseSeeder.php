<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create default Tenant (LSPL)
        $tenant = Tenant::create([
            'name' => 'Longway Softronix Private Limited',
            'domain' => 'localhost',
            'status' => 'active',
            'settings' => [
                'branding' => [
                    'logo' => null,
                    'colors' => [
                        'primary' => '#4f46e5', // Indigo
                    ],
                ],
                'ai' => [
                    'default_provider' => 'openai',
                    'default_embedding' => 'openai',
                ]
            ],
        ]);

        // Set the resolved tenant context in TenantManager for the rest of seeding
        $tenantManager = app(TenantManager::class);
        $tenantManager->setTenant($tenant);

        // 2. Create Global Permissions
        $permissionsList = [
            ['name' => 'Manage Users', 'slug' => 'manage_users', 'description' => 'Can create, edit, and delete company users.'],
            ['name' => 'Manage Settings', 'slug' => 'manage_settings', 'description' => 'Can manage company settings and api credentials.'],
            ['name' => 'Manage CRM', 'slug' => 'manage_crm', 'description' => 'Can manage crm leads, deals, tasks, pipelines, and proposals.'],
            ['name' => 'Manage Knowledge Base', 'slug' => 'manage_knowledge', 'description' => 'Can upload and index documents in vector database.'],
            ['name' => 'Manage Prompts', 'slug' => 'manage_prompts', 'description' => 'Can configure system prompts and version settings.'],
            ['name' => 'Use AI Agent Chat', 'slug' => 'use_chat', 'description' => 'Can interact with AI agents via web portal.'],
            ['name' => 'Use Voice Agent Calls', 'slug' => 'use_voice', 'description' => 'Can make or receive voice agent calls.'],
            ['name' => 'Audit System Logs', 'slug' => 'audit_logs', 'description' => 'Can view security audit logs for compliance.'],
        ];

        $permissions = [];
        foreach ($permissionsList as $p) {
            $permissions[$p['slug']] = Permission::create($p);
        }

        // 3. Create Tenant Roles
        $rolesList = [
            ['name' => 'Owner', 'slug' => 'owner', 'description' => 'Complete control over company settings, billing, and administration.'],
            ['name' => 'Admin', 'slug' => 'admin', 'description' => 'Administrative control over CRM, AI, knowledge base, and users.'],
            ['name' => 'Manager', 'slug' => 'manager', 'description' => 'Can manage CRM workflows, index documents, and chat.'],
            ['name' => 'Agent', 'slug' => 'agent', 'description' => 'Access to CRM client records, chat tools, and calls.'],
            ['name' => 'Customer', 'slug' => 'customer', 'description' => 'Access to customer portal to chat with support agents.'],
        ];

        $roles = [];
        foreach ($rolesList as $r) {
            $roles[$r['slug']] = Role::create([
                'tenant_id' => $tenant->id,
                'name' => $r['name'],
                'slug' => $r['slug'],
                'description' => $r['description'],
            ]);
        }

        // 4. Map Permissions to Roles
        // Owner gets all
        $roles['owner']->permissions()->sync(Permission::pluck('id')->toArray());

        // Admin gets all except billing
        $adminPerms = Permission::whereIn('slug', [
            'manage_users', 'manage_settings', 'manage_crm', 'manage_knowledge', 'manage_prompts', 'use_chat', 'use_voice', 'audit_logs'
        ])->pluck('id')->toArray();
        $roles['admin']->permissions()->sync($adminPerms);

        // Manager
        $managerPerms = Permission::whereIn('slug', [
            'manage_crm', 'manage_knowledge', 'use_chat', 'use_voice'
        ])->pluck('id')->toArray();
        $roles['manager']->permissions()->sync($managerPerms);

        // Agent
        $agentPerms = Permission::whereIn('slug', [
            'use_chat', 'use_voice'
        ])->pluck('id')->toArray();
        $roles['agent']->permissions()->sync($agentPerms);

        // Customer
        $customerPerms = Permission::whereIn('slug', [
            'use_chat'
        ])->pluck('id')->toArray();
        $roles['customer']->permissions()->sync($customerPerms);

        // 5. Create Default User (Admin)
        $adminUser = User::create([
            'tenant_id' => $tenant->id,
            'name' => 'Administrator',
            'email' => 'admin@lspl.xyz',
            'password' => Hash::make('admin123'),
            'email_verified_at' => now(),
        ]);

        // Attach Owner role to admin
        $adminUser->roles()->attach($roles['owner']->id);

        echo "Seeding completed: Default tenant 'Longway Softronix Private Limited' (localhost) and user 'admin@lspl.xyz' created.\n";
    }
}
