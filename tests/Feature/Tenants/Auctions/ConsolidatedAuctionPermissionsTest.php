<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\UserManagement\Role\RoleService;
use Database\Seeders\tenant\PermissionsRolesSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\Support\TenantAuctionReportTestCase;

final class ConsolidatedAuctionPermissionsTest extends TenantAuctionReportTestCase
{
    public function test_existing_permissions_move_under_auctions_without_changing_grants(): void
    {
        $user = User::where('email', 'demo@lot.com')->firstOrFail();
        $permission = Permission::updateOrCreate(['name' => 'marketing_campaigns.view', 'guard_name' => 'tenant'], ['section' => 'marketing_campaigns_management']);
        $requestPermission = Permission::updateOrCreate(['name' => 'auction_establishment_requests.edit', 'guard_name' => 'tenant'], ['section' => 'auction_establishment_requests_management']);
        $role = Role::firstOrCreate(['name' => 'Auction permissions test', 'guard_name' => 'tenant']);
        $role->givePermissionTo($permission);
        $user->givePermissionTo($requestPermission);

        $migration = require database_path('migrations/tenant/2026_11_19_170000_consolidate_auction_permission_sections.php');
        $migration->up();
        $migration->up();

        self::assertSame('auctions_management', $permission->fresh()->section);
        self::assertSame('auctions_management', $requestPermission->fresh()->section);
        self::assertTrue($role->fresh()->hasPermissionTo($permission));
        self::assertTrue($user->fresh()->hasPermissionTo($requestPermission));
        $groups = app(RoleService::class)->getGroupedPermissions();
        self::assertFalse($groups->has('marketing_campaigns_management'));
        self::assertFalse($groups->has('auction_establishment_requests_management'));
    }

    public function test_seeding_keeps_core_and_integrated_auction_permissions_in_one_section(): void
    {
        app(PermissionsRolesSeeder::class)->run();
        foreach (['auctions.view', 'auctions.create', 'auctions.edit', 'auctions.delete', 'marketing_campaigns.view', 'marketing_campaigns.edit', 'auction_establishment_requests.view', 'auction_establishment_requests.edit'] as $name) {
            self::assertSame('auctions_management', Permission::where('guard_name', 'tenant')->where('name', $name)->sole()->section);
        }
        self::assertSame('pending_auctions_management', Permission::where('guard_name', 'tenant')->where('name', 'auction_establishment_requests.approve')->sole()->section);
    }
}
