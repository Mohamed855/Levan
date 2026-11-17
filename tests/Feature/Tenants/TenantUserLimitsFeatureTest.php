<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Shared\CentralSubscriptionContextGateway;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\TestCase;

class TenantUserLimitsFeatureTest extends TestCase
{
    private Tenant $tenant;

    private User $owner;

    private ?int $originalIncludedUsers;

    private ?string $originalExtraPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->owner = User::where('email', 'demo@lot.com')->firstOrFail();

        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $plan = $subscription->plan;
            $this->originalIncludedUsers = $plan->included_users;
            $this->originalExtraPrice = $plan->extra_user_license_price !== null ? (string) $plan->extra_user_license_price : null;
        });
    }

    protected function tearDown(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'included_users' => $this->originalIncludedUsers,
                'extra_user_license_price' => $this->originalExtraPrice,
            ]);
        });

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_unlimited_users_plan_allows_adding_users_without_limit(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'included_users' => null,
                'extra_user_license_price' => null,
            ]);
        });

        $gateway = app(CentralSubscriptionContextGateway::class);
        $this->assertTrue($gateway->canAddUser(100));

        $info = $gateway->getUserLimitInfo();
        $this->assertTrue($info['is_unlimited']);
        $this->assertNull($info['user_limit']);
    }

    public function test_cannot_access_license_checkout_when_plan_has_unlimited_users(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'included_users' => null,
                'extra_user_license_price' => null,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('subscriptions/licenses?new_licenses_count=1'))
            ->assertRedirect($this->tenantUrl('subscriptions'))
            ->assertSessionHas('error', 'باقات المستخدمين غير المحدودين لا تتطلب شراء تراخيص إضافية.');
    }

    public function test_cannot_process_license_when_plan_has_unlimited_users(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'included_users' => null,
                'extra_user_license_price' => null,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->post($this->tenantUrl('subscriptions/licenses'), [
                'new_licenses_count' => 1,
            ])
            ->assertRedirect($this->tenantUrl('subscriptions'))
            ->assertSessionHas('error', 'باقات المستخدمين غير المحدودين لا تتطلب شراء تراخيص إضافية.');
    }

    public function test_subscription_index_hides_extra_licenses_form_when_users_are_unlimited(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'included_users' => null,
                'extra_user_license_price' => null,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('subscriptions'))
            ->assertOk()
            ->assertSee('باقتك الحالية تتضمن مستخدمين غير محدودين')
            ->assertDontSee('id="custom-licenses"', false);
    }

    private function tenantUrl(string $path): string
    {
        return 'http://test.localhost/'.ltrim($path, '/');
    }
}
