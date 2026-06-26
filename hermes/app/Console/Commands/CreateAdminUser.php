<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdminUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hermes:create-admin {--name=Administrator} {--email=admin@lspl.xyz} {--password=}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Safely create or update a Hermes platform administrator';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->option('name');
        $email = $this->option('email');
        $password = $this->option('password');

        if (empty($password)) {
            $this->error('The --password option is required.');
            return Command::FAILURE;
        }

        // 1. Resolve first tenant as owner context
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->error('No tenant registered in database. Run database seeders first.');
            return Command::FAILURE;
        }

        // Set the active tenant context
        app(TenantManager::class)->setTenant($tenant);

        // 2. Create or Update User
        $user = User::where('email', $email)->first();
        $isNew = false;

        if ($user) {
            $user->update([
                'name' => $name,
                'password' => Hash::make($password),
            ]);
            $this->info("Successfully updated administrator: {$email}");
        } else {
            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $name,
                'email' => $email,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
            ]);
            $isNew = true;
            $this->info("Successfully created new administrator: {$email}");
        }

        // 3. Attach owner role
        $ownerRole = Role::where('tenant_id', $tenant->id)->where('slug', 'owner')->first();
        if ($ownerRole) {
            if (!$user->roles()->where('role_id', $ownerRole->id)->exists()) {
                $user->roles()->attach($ownerRole->id);
                $this->info("Attached 'Owner' role to {$email}.");
            }
        } else {
            $this->warn("Role 'owner' not found. Please verify roles seeding.");
        }

        return Command::SUCCESS;
    }
}
