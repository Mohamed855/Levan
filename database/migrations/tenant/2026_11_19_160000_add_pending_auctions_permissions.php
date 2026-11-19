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
        foreach (config('auction-report-permissions.pending_auctions_management', []) as $name) {
            $permission = Permission::updateOrCreate(
                ['name' => $name, 'guard_name' => 'tenant'],
                ['section' => 'pending_auctions_management'],
            );
            Role::where('name', 'Admin')->where('guard_name', 'tenant')->first()?->givePermissionTo($permission);
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve assigned permissions when rolling back this additive change.
    }
};
