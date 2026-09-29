<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('permissions')
            ->where('name', 'حذف تذكرة')
            ->where('guard_name', 'admin')
            ->update([
                'name' => 'tickets.delete',
                'section' => 'tickets_management',
            ]);

        Permission::firstOrCreate(
            ['name' => 'tickets.delete', 'guard_name' => 'admin'],
            ['section' => 'tickets_management']
        );

        $superAdmin = Role::where('name', 'SuperAdmin')->where('guard_name', 'admin')->first();
        $superAdmin?->givePermissionTo('tickets.delete');

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('name', 'tickets.delete')
            ->where('guard_name', 'admin')
            ->update(['name' => 'حذف تذكرة']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
