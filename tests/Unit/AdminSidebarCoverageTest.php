<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class AdminSidebarCoverageTest extends TestCase
{
    public function test_it_exposes_every_admin_administration_index_route_in_the_sidebar(): void
    {
        $sidebar = file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/layouts/sections/menu/verticalMenu.blade.php');

        foreach ([
            'admin.dashboard',
            'admin.users.index',
            'admin.roles.index',
            'admin.clients.index',
            'admin.subscriptions.index',
            'admin.subscriptionManage.pending',
            'admin.plans.index',
            'admin.coupons.index',
            'admin.bills.index',
            'admin.tickets.index',
            'admin.contact-requests.index',
            'admin.trial-requests.index',
            'admin.settings.index',
            'admin.settings.banks.index',
            'admin.settings.domains.index',
            'admin.activity-logs.index',
            'admin.versions.index',
            'admin.version_testers.index',
            'admin.archives.index',
            'admin.archives.plans.index',
            'admin.archives.coupons.index',
            'admin.archives.tenants.index',
            'admin.archives.bills.index',
        ] as $route) {
            $this->assertStringContainsString("route('{$route}')", $sidebar, "Missing sidebar link for {$route}");
        }
    }

    public function test_public_request_links_are_not_nested_under_ticket_permission(): void
    {
        $sidebar = file_get_contents(dirname(__DIR__, 2).'/resources/views/admin/layouts/sections/menu/verticalMenu.blade.php');

        $ticketsBlockStart = strpos($sidebar, "@can('tickets.view')");
        $ticketsBlockEnd = strpos($sidebar, '@endcan', $ticketsBlockStart);
        $ticketsBlock = substr($sidebar, $ticketsBlockStart, $ticketsBlockEnd - $ticketsBlockStart);

        $this->assertStringNotContainsString("@can('contact_requests.view')", $ticketsBlock);
        $this->assertStringNotContainsString("@can('trial_requests.view')", $ticketsBlock);
    }

    public function test_default_admin_seeder_assigns_every_current_admin_permission(): void
    {
        $seeder = file_get_contents(dirname(__DIR__, 2).'/database/seeders/admin/AdminUserSeeder.php');
        $permissionsSeeder = file_get_contents(dirname(__DIR__, 2).'/database/seeders/admin/PermissionsRolesSeeder.php');

        $this->assertStringContainsString("syncRoles(['SuperAdmin'])", $seeder);
        $this->assertStringContainsString("syncPermissions(Permission::where(\"guard_name\", \"admin\")->get())", $seeder);
        $this->assertStringContainsString('$roleSuperAdmin->syncPermissions(Permission::where(\'guard_name\', \'admin\')->get())', $permissionsSeeder);
    }
}
