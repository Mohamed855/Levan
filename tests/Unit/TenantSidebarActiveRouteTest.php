<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Helpers\Helpers;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class TenantSidebarActiveRouteTest extends TestCase
{
    public function test_it_identifies_clean_tenant_dashboard_as_active(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tenant.dashboard');
        $this->assertTrue(Helpers::isActiveRoute('dashboard'));
        $this->assertFalse(Helpers::isActiveRoute('users.*'));
    }

    public function test_it_identifies_clean_tenant_properties_as_active(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tenant.properties.index');
        $this->assertTrue(Helpers::isActiveRoute('properties.*'));
        $this->assertTrue(Helpers::isActiveRoute('properties.index'));
        $this->assertFalse(Helpers::isActiveRoute('dashboard'));
        $this->assertFalse(Helpers::isActiveRoute('users.*'));
    }

    public function test_it_identifies_clean_tenant_sub_routes_with_wildcards(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tenant.properties.create');
        $this->assertTrue(Helpers::isActiveRoute('properties.*'));
    }

    public function test_it_identifies_clean_tenant_archive_sub_routes(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tenant.archive.users.index');
        $this->assertTrue(Helpers::isActiveRoute('archive.*'));
        $this->assertTrue(Helpers::isActiveRoute('archive.users.*'));
        $this->assertFalse(Helpers::isActiveRoute('users.*'));
    }

    public function test_it_identifies_legacy_and_domain_route_prefixes_as_active(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('path.dashboard');
        $this->assertTrue(Helpers::isActiveRoute('dashboard'));
    }

    public function test_it_identifies_domain_routes_as_active(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('domain.users.index');
        $this->assertTrue(Helpers::isActiveRoute('users.*'));
    }

    public function test_it_identifies_central_domain_index_routes_as_active(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('central-domain-1.tenant.auctions.index');
        $this->assertTrue(Helpers::isActiveRoute('auctions.*'));
    }

    public function test_it_supports_array_of_routes(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tenant.dashboard');
        $this->assertTrue(Helpers::isActiveRoute(['home', 'dashboard']));
        $this->assertFalse(Helpers::isActiveRoute(['users.*', 'roles.*']));
    }

    public function test_it_handles_empty_or_null_routes_safely(): void
    {
        Route::shouldReceive('currentRouteName')->andReturn('tenant.dashboard');
        $this->assertFalse(Helpers::isActiveRoute(''));
        $this->assertFalse(Helpers::isActiveRoute([]));
    }
}
