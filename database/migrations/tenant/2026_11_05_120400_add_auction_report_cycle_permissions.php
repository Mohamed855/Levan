<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        foreach (config('auction-report-permissions') as $section => $names) {
            foreach ($names as $name) {
                $permission = Permission::firstOrCreate(['name' => $name, 'guard_name' => 'tenant'], ['section' => $section]);
                Role::where('name', 'Admin')->where('guard_name', 'tenant')->first()?->givePermissionTo($permission);
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // Preserve assigned permissions when rolling back the additive workflow schema.
    }
};
