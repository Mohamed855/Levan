<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Migrate Arabic log_name to English key 'admin_users'
        DB::table('activity_log')
            ->where('log_name', 'إدارة المستخدمين')
            ->update(['log_name' => 'admin_users']);

        // 2. Ensure users.restore and users.force_delete permissions exist for admin guard
        $restorePermission = Permission::firstOrCreate([
            'name'       => 'users.restore',
            'guard_name' => 'admin',
        ]);

        $forceDeletePermission = Permission::firstOrCreate([
            'name'       => 'users.force_delete',
            'guard_name' => 'admin',
        ]);

        // Assign to SuperAdmin role if present
        $superAdminRole = Role::where('name', 'SuperAdmin')
            ->where('guard_name', 'admin')
            ->first();

        if ($superAdminRole) {
            $superAdminRole->givePermissionTo([$restorePermission, $forceDeletePermission]);
        }
    }

    public function down(): void
    {
        DB::table('activity_log')
            ->where('log_name', 'admin_users')
            ->update(['log_name' => 'إدارة المستخدمين']);

        $superAdminRole = Role::where('name', 'SuperAdmin')
            ->where('guard_name', 'admin')
            ->first();

        if ($superAdminRole) {
            $superAdminRole->revokePermissionTo(['users.restore', 'users.force_delete']);
        }

        Permission::where('name', 'users.restore')->where('guard_name', 'admin')->delete();
        Permission::where('name', 'users.force_delete')->where('guard_name', 'admin')->delete();
    }
};
