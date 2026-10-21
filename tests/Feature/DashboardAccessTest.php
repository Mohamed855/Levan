<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Admin\AdminUser;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_the_matching_login_page(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('tenant.dashboard'))->assertRedirect(route('tenant.login'));
    }

    public function test_admin_can_open_only_the_admin_dashboard(): void
    {
        $admin = AdminUser::factory()->create();
        $this->actingAs($admin, 'admin')->get(route('admin.dashboard'))->assertOk()->assertSee('إدارة المنصة');
    }

    public function test_tenant_can_open_only_the_tenant_dashboard(): void
    {
        $user = User::factory()->create(['status' => 'active']);
        $this->actingAs($user, 'tenant')->get(route('tenant.dashboard'))->assertOk()->assertSee('لوحة المستأجر');
    }
}