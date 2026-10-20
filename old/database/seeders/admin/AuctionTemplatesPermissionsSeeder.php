<?php

declare(strict_types=1);

namespace Database\Seeders\admin;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AuctionTemplatesPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            'auction_templates.view',
            'auction_templates.create',
            'auction_templates.edit',
            'auction_templates.delete',
            'auction_templates.restore',
            'auction_templates.force_delete',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(
                [
                    'name'       => $perm,
                    'guard_name' => 'admin',
                ],
                [
                    'section'    => 'auction_templates_management',
                ]
            );
        }

        $superAdmin = Role::where('name', 'SuperAdmin')->where('guard_name', 'admin')->first();
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }
}
