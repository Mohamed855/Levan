<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Permission::where('guard_name', 'tenant')
            ->whereIn('section', ['marketing_campaigns_management', 'auction_establishment_requests_management'])
            ->update(['section' => 'auctions_management']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        foreach (['marketing_campaigns', 'auction_establishment_requests'] as $module) {
            Permission::where('guard_name', 'tenant')
                ->where('section', 'auctions_management')
                ->where('name', 'like', $module.'.%')
                ->update(['section' => $module.'_management']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
