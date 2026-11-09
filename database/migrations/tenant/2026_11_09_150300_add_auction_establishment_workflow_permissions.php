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
        foreach (['auction_establishment_requests_management', 'auction_closings_management'] as $section) {
            foreach (config('auction-report-permissions.'.$section, []) as $name) {
                $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'tenant'], ['section' => $section]);
                Role::where('name', 'Admin')->where('guard_name', 'tenant')->first()?->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Retain permissions when rolling back the additive workflow.
    }
};
