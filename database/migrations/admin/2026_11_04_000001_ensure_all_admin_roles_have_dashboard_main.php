<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $perm = Permission::firstOrCreate([
            'name' => 'dashboard.main',
            'guard_name' => 'admin',
        ], [
            'section' => 'dashboard',
        ]);

        $roles = Role::where('guard_name', 'admin')->get();
        foreach ($roles as $role) {
            if (! $role->hasPermissionTo('dashboard.main')) {
                $role->givePermissionTo($perm);
            }
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Baseline overview permission is permanent
    }
};
