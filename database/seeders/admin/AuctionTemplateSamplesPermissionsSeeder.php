<?php

declare(strict_types=1);

namespace Database\Seeders\admin;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AuctionTemplateSamplesPermissionsSeeder extends Seeder
{
    private const PERMISSIONS = [
        'auction_template_samples.view',
        'auction_template_samples.create',
        'auction_template_samples.edit',
        'auction_template_samples.delete',
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::PERMISSIONS as $permission) {
            Permission::updateOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                ['section' => 'auction_template_samples_management'],
            );
        }

        $superAdmin = Role::where('name', 'SuperAdmin')->where('guard_name', 'admin')->first();
        $superAdmin?->givePermissionTo(self::PERMISSIONS);
    }
}
