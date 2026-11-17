<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\TestCase;

class TenantLimitsEnforcementFeatureTest extends TestCase
{
    private Tenant $tenant;

    private User $owner;

    private ?string $originalLimitsMode;

    private ?int $originalPropertiesLimit;

    private ?int $originalAuctionsLimit;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->owner = User::where('email', 'demo@lot.com')->firstOrFail();

        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $plan = $subscription->plan;
            $this->originalLimitsMode = $plan->limits_mode instanceof PlanLimitsMode ? $plan->limits_mode->value : (string) $plan->limits_mode;
            $this->originalPropertiesLimit = $plan->properties_limit;
            $this->originalAuctionsLimit = $plan->active_auctions_limit;
        });
    }

    protected function tearDown(): void
    {
        if (isset($this->originalLimitsMode)) {
            Tenancy::central(function () {
                $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
                $subscription->plan->update([
                    'limits_mode' => $this->originalLimitsMode,
                    'properties_limit' => $this->originalPropertiesLimit,
                    'active_auctions_limit' => $this->originalAuctionsLimit,
                ]);
            });
        }

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_cannot_access_create_property_when_limit_reached(): void
    {
        $currentPropertiesCount = Property::query()->count();

        Tenancy::central(function () use ($currentPropertiesCount) {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'limits_mode' => PlanLimitsMode::LIMITED->value,
                'properties_limit' => $currentPropertiesCount,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties/create'))
            ->assertRedirect($this->tenantUrl('properties'))
            ->assertSessionHas('error');
    }

    public function test_cannot_store_property_via_ajax_when_limit_reached(): void
    {
        $currentPropertiesCount = Property::query()->count();

        Tenancy::central(function () use ($currentPropertiesCount) {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'limits_mode' => PlanLimitsMode::LIMITED->value,
                'properties_limit' => $currentPropertiesCount,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('properties'), [
                'name' => 'عقار تجريبي جديد',
            ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_access_create_property_when_below_limit(): void
    {
        $currentPropertiesCount = Property::query()->count();

        Tenancy::central(function () use ($currentPropertiesCount) {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'limits_mode' => PlanLimitsMode::LIMITED->value,
                'properties_limit' => $currentPropertiesCount + 5,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('properties/create'))
            ->assertOk();
    }

    public function test_cannot_access_create_auction_when_active_auctions_limit_reached(): void
    {
        $activeAuctionsCount = Auction::open()->count();

        Tenancy::central(function () use ($activeAuctionsCount) {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'limits_mode' => PlanLimitsMode::LIMITED->value,
                'active_auctions_limit' => $activeAuctionsCount,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('auctions/create'))
            ->assertRedirect($this->tenantUrl('auctions'))
            ->assertSessionHas('error');
    }

    public function test_cannot_store_auction_via_ajax_when_limit_reached(): void
    {
        $activeAuctionsCount = Auction::open()->count();

        Tenancy::central(function () use ($activeAuctionsCount) {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'limits_mode' => PlanLimitsMode::LIMITED->value,
                'active_auctions_limit' => $activeAuctionsCount,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->postJson($this->tenantUrl('auctions'), [
                'name' => 'مزاد تجريبي جديد',
            ])
            ->assertStatus(403)
            ->assertJson([
                'success' => false,
            ]);
    }

    public function test_can_access_create_auction_when_unlimited(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->plan->update([
                'limits_mode' => PlanLimitsMode::UNLIMITED->value,
            ]);
        });

        $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('auctions/create'))
            ->assertOk();
    }

    private function tenantUrl(string $path): string
    {
        return 'http://test.localhost/'.ltrim($path, '/');
    }
}
