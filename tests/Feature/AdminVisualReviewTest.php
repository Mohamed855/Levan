<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Services\Admin\Companies\CompanyService;
use App\Services\Admin\Dashboard\DashboardService;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AdminVisualReviewTest extends TestCase
{
    private AdminUser $admin;

    protected function setUp(): void
    {
        parent::setUp();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        $this->admin = AdminUser::where('email', 'superadmin@gmail.com')->first();
        if (! $this->admin) {
            $this->admin = AdminUser::firstOrCreate(
                ['email' => 'admin_visual_test@levan.com'],
                [
                    'name' => 'Visual Test Admin',
                    'password' => bcrypt('password'),
                    'phone' => '0500000098',
                    'status' => 'active',
                    'is_super_admin' => true,
                ]
            );
        }
    }

    public function test_dashboard_expired_status_uses_dark_color(): void
    {
        $dashboardService = app(DashboardService::class);
        $distribution = $dashboardService->getSubscriptionStatusDistribution();

        $expiredItem = collect($distribution)->firstWhere('key', 'expired');
        $this->assertNotNull($expiredItem);
        $this->assertSame('dark', $expiredItem['color']);
    }

    public function test_dashboard_kpis_are_accurately_calculated(): void
    {
        $dashboardService = app(DashboardService::class);
        $kpis = $dashboardService->getDashboardData()['kpis'];

        $this->assertArrayHasKey('new_subscription_requests', $kpis);
        $this->assertArrayHasKey('subscribed_companies', $kpis);
        $this->assertArrayHasKey('active_subscriptions', $kpis);
        $this->assertArrayHasKey('suspended_subscriptions', $kpis);
        $this->assertArrayHasKey('central_templates', $kpis);
        $this->assertArrayHasKey('monthly_payments', $kpis);

        $this->assertSame(
            AuctionTemplate::where('is_active', true)->count(),
            $kpis['central_templates']
        );
    }

    public function test_admin_user_show_renders_gradient_when_no_cover_image(): void
    {
        $user = AdminUser::firstOrCreate(
            ['email' => 'user_show_test@levan.com'],
            [
                'name' => 'User Show Test',
                'password' => bcrypt('password'),
                'phone' => '0500000099',
                'status' => 'active',
                'background_image' => null,
                'image' => null,
            ]
        );

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.users.profile', $user->id));

        $response->assertStatus(200);
        $response->assertSee('linear-gradient', false);
        $response->assertDontSee('cta-bg-secondary3.png');
        $response->assertSee('static/brand/logos/logo.svg');
    }

    public function test_clients_statistics_includes_trial_count_and_renders_trial_card(): void
    {
        $companyService = app(CompanyService::class);
        $statistics = $companyService->getCompanyStatistics();

        $this->assertArrayHasKey('trial', $statistics);

        $response = $this->actingAs($this->admin, 'admin')
            ->get(route('admin.clients.index'));

        $response->assertStatus(200);
        $response->assertSee('عملاء التجربة');
        $response->assertSee('في فترة التجربة (7 أيام)');
        $response->assertDontSee('بدون اشتراك');
    }
}
