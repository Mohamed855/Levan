<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants;

use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\Subscription\SubscriptionManager;
use Carbon\Carbon;
use Stancl\Tenancy\Facades\Tenancy;
use Tests\TestCase;

class TenantSubscriptionRenewalFeatureTest extends TestCase
{
    private Tenant $tenant;

    private User $owner;

    private ?string $originalCycle;

    private ?float $originalPrice;

    private ?float $originalDiscount;

    private ?float $originalYearlyRenewalPrice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::findOrFail('test');
        tenancy()->initialize($this->tenant);
        $this->owner = User::where('email', 'demo@lot.com')->firstOrFail();

        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $plan = $subscription->plan;
            $this->originalCycle = $subscription->billing_cycle instanceof SubscriptionBillingCycle ? $subscription->billing_cycle->value : (string) $subscription->billing_cycle;
            $this->originalPrice = (float) $plan->price;
            $this->originalYearlyRenewalPrice = $plan->yearly_price !== null ? (float) $plan->yearly_price : null;
            $this->originalDiscount = (float) $plan->discount_percentage;
        });
    }

    protected function tearDown(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->update([
                'billing_cycle' => $this->originalCycle,
            ]);
            $subscription->plan->update([
                'price' => $this->originalPrice,
                'yearly_price' => $this->originalYearlyRenewalPrice,
                'discount_percentage' => $this->originalDiscount,
            ]);
        });

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        parent::tearDown();
    }

    public function test_renew_checkout_uses_actual_discounted_plan_price_for_monthly_cycles(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->update([
                'billing_cycle' => SubscriptionBillingCycle::MONTHLY->value,
            ]);
            $subscription->plan->update([
                'price' => 500,
                'discount_percentage' => 10,
            ]);
        });

        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('subscriptions/renew?cycle=monthly'));

        $response->assertOk();
        $response->assertSee('450.00');
    }

    public function test_renew_checkout_uses_discounted_plan_price_when_yearly_cycle(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->first();
            $subscription->update([
                'billing_cycle' => SubscriptionBillingCycle::YEARLY->value,
            ]);
            $subscription->plan->update([
                'price' => 500,
                'yearly_price' => 5000,
                'discount_percentage' => 15,
            ]);
        });

        $response = $this->actingAs($this->owner, 'tenant')
            ->get($this->tenantUrl('subscriptions/renew?cycle=yearly'));

        $response->assertOk();
        $response->assertSee('4,250.00');
    }

    public function test_early_renewal_extends_subscription_from_current_expiration_date_without_losing_days(): void
    {
        Tenancy::central(function () {
            $subscription = Subscription::where('tenant_id', 'test')->latest('id')->firstOrFail();
            $subscription->update([
                'billing_cycle' => SubscriptionBillingCycle::MONTHLY->value,
            ]);

            // Set current period to end 20 days in the future
            $currentPeriodEnd = now()->addDays(20)->startOfDay();
            $subscription->update([
                'ends_at' => $currentPeriodEnd,
                'next_payment_date' => $currentPeriodEnd,
            ]);

            // Clean up any extra periods left from previous manual runs
            SubscriptionPeriod::where('subscription_id', $subscription->id)
                ->where('is_current', false)
                ->forceDelete();

            $activePeriod = $subscription->currentPeriod;
            if ($activePeriod) {
                $activePeriod->update([
                    'period_end' => $currentPeriodEnd,
                    'status' => SubscriptionPeriodStatus::ACTIVE,
                    'is_current' => true,
                ]);
            } else {
                SubscriptionPeriod::create([
                    'subscription_id' => $subscription->id,
                    'period_start' => now()->subDays(10),
                    'period_end' => $currentPeriodEnd,
                    'status' => SubscriptionPeriodStatus::ACTIVE,
                    'is_current' => true,
                    'plan_id_snapshot' => $subscription->plan_id,
                ]);
            }

            // Perform early renewal
            $subscriptionManager = app(SubscriptionManager::class);
            [$renewedSubscription] = $subscriptionManager->renewSubscription($this->tenant, 450.00);

            // Assert that subscription end date was extended by 1 month from the future period_end (not from now!)
            $expectedExtendedEnd = $currentPeriodEnd->copy()->addMonth();
            $this->assertEquals(
                $expectedExtendedEnd->format('Y-m-d'),
                Carbon::parse($renewedSubscription->ends_at)->format('Y-m-d')
            );

            // Assert a QUEUED period was created for the upcoming period
            $queuedPeriod = SubscriptionPeriod::where('subscription_id', $subscription->id)
                ->where('status', SubscriptionPeriodStatus::QUEUED)
                ->first();

            $this->assertNotNull($queuedPeriod);
            $this->assertEquals(
                $currentPeriodEnd->format('Y-m-d'),
                Carbon::parse($queuedPeriod->period_start)->format('Y-m-d')
            );
            $this->assertEquals(
                $expectedExtendedEnd->format('Y-m-d'),
                Carbon::parse($queuedPeriod->period_end)->format('Y-m-d')
            );

            // Clean up the queued period
            $queuedPeriod->delete();
        });
    }

    private function tenantUrl(string $path): string
    {
        return 'http://test.localhost/'.ltrim($path, '/');
    }
}
