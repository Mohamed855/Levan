<?php

declare(strict_types=1);

namespace App\Services\Admin\Subscriptions;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Billing\PaymentMethod;
use App\Enums\Admin\Subscriptions\SubscriptionPeriodStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Licenses\LicenseSeat;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Subscription\SubscriptionPeriod;
use App\Models\Admin\Tenants\Tenant;
use App\Services\shared\Bills\BillService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminSubscriptionService
{
    public function __construct(
        private readonly BillService $billService
    ) {}

    public function getSubscriptionStatistics(): array
    {
        $baseQuery = Subscription::query();
        $now = now();

        return [
            'total'         => (clone $baseQuery)->count(),
            'pending'       => (clone $baseQuery)->where('status', SubscriptionStatus::PENDING)->count(),
            'active'        => (clone $baseQuery)
                ->where('status', SubscriptionStatus::ACTIVE)
                ->where(function ($query) use ($now) {
                    $query->whereHas('currentPeriod', function ($q) use ($now) {
                        $q->where('status', SubscriptionPeriodStatus::ACTIVE)
                            ->where('period_start', '<=', $now)
                            ->where('period_end', '>=', $now);
                    })->orWhereDoesntHave('periods');
                })
                ->count(),
            'expired'       => (clone $baseQuery)
                ->whereIn('status', [SubscriptionStatus::EXPIRED, SubscriptionStatus::CANCELLED])
                ->count(),
            'suspended'     => (clone $baseQuery)->where('status', SubscriptionStatus::SUSPENDED)->count(),
            'expiring_soon' => $this->getExpiringSoonCount(),
        ];
    }

    public function getExpiringSoonCount(): int
    {
        return Subscription::where('status', SubscriptionStatus::ACTIVE)
            ->whereHas('currentPeriod', function ($q) {
                $q->where('status', SubscriptionPeriodStatus::ACTIVE)
                    ->where('period_end', '<=', now()->addDays(30))
                    ->where('period_end', '>=', now()->startOfDay());
            })
            ->count();
    }

    public function createSubscription(Tenant $company, array $data): Subscription
    {
        // Rule: Can only create a new subscription if last subscription is expired (or no subscription exists)
        $lastSubscription = $company->lastSubscription;
        if ($lastSubscription && $lastSubscription->status !== SubscriptionStatus::EXPIRED) {
            throw ValidationException::withMessages([
                'company_id' => 'لا يمكن إنشاء اشتراك جديد للشركة إلا إذا كان آخر اشتراك لها منتهياً (expired).',
            ]);
        }

        return DB::connection('mysql')->transaction(function () use ($company, $data) {
            $plan = Plan::findOrFail($data['plan_id']);
            $activateImmediately = ! empty($data['activate_immediately']);

            $startDate = ! empty($data['start_date']) ? Carbon::parse($data['start_date']) : now();
            $endDate = ! empty($data['end_date'])
                ? Carbon::parse($data['end_date'])
                : (! empty($data['trial_end_date'])
                    ? Carbon::parse($data['trial_end_date'])
                    : $this->calculatePeriodEndForPlan($startDate, $plan));

            $paymentType = $data['payment_type'] ?? 'unpaid';

            if ($activateImmediately) {
                $subStatus = SubscriptionStatus::ACTIVE;
                $periodStatus = SubscriptionPeriodStatus::ACTIVE;
            } elseif ($paymentType === 'paid') {
                $subStatus = SubscriptionStatus::ACTIVE;
                $periodStatus = SubscriptionPeriodStatus::ACTIVE;
            } elseif ($paymentType === 'trial') {
                $subStatus = SubscriptionStatus::TRIAL;
                $periodStatus = SubscriptionPeriodStatus::ACTIVE;
            } else {
                $subStatus = SubscriptionStatus::PENDING;
                $periodStatus = SubscriptionPeriodStatus::PENDING_PAYMENT;
            }

            $discount = (($data['has_discount'] ?? 'no') === 'yes') ? (float) ($data['discount_value'] ?? 0) : 0;
            $amount = $paymentType === 'trial' ? 0.00 : max(0.00, (float) $plan->price_after_discount - $discount);

            $subscription = Subscription::create([
                'tenant_id'         => $company->id,
                'plan_id'           => $plan->id,
                'price'             => $amount,
                'status'            => $subStatus,
                'total_users'       => $plan->included_users,
                'used_users'        => 0,
                'starts_at'         => $startDate,
                'ends_at'           => $endDate,
                'next_payment_date' => $endDate,
                'auto_renew'        => false,
                'trial_end_date'    => ! empty($data['trial_end_date']) ? $data['trial_end_date'] : null,
            ]);

            // Create subscription period
            $nextPeriodNumber = ((int) SubscriptionPeriod::where('subscription_id', $subscription->id)->max('period_number')) + 1;
            SubscriptionPeriod::create([
                'subscription_id'  => $subscription->id,
                'period_number'    => $nextPeriodNumber,
                'period_start'     => $startDate,
                'period_end'       => $endDate,
                'plan_id_snapshot' => $plan->id,
                'price_snapshot'   => $amount,
                'status'           => $periodStatus,
                'is_current'       => true,
            ]);

            // Handle bill
            $paymentMethod = null;
            if ($paymentType === 'paid' && ! empty($data['payment_method'])) {
                $paymentMethod = PaymentMethod::tryFrom($data['payment_method']);
            }

            $bill = $this->billService->createPendingBill(
                amount: $amount,
                paymentMethod: $paymentMethod,
                originalAmount: (float) $plan->price_after_discount,
                type: BillType::NEW_SUBSCRIPTION,
                couponData: [
                    'discount_amount' => $discount,
                    'coupon_id'       => null,
                    'coupon_code'     => null,
                    'coupon_value'    => null,
                    'coupon_type'     => null,
                ],
                metadata: [
                    'tenant_id'       => $company->id,
                    'subscription_id' => $subscription->id,
                    'plan_id'         => $plan->id,
                ]
            );

            if ($bill) {
                $bill->update([
                    'tenant_id'       => $company->id,
                    'subscription_id' => $subscription->id,
                    'status'          => ($activateImmediately || $paymentType === 'paid') ? BillStatus::PAID : BillStatus::UNPAID,
                    'paid_at'         => ($activateImmediately || $paymentType === 'paid') ? now() : null,
                ]);
            }

            // Provision license seats for the plan quota
            $totalSeats = (int) ($subscription->total_users ?: $plan->included_users ?: 1);
            for ($i = 0; $i < $totalSeats; $i++) {
                LicenseSeat::create([
                    'id'              => (string) Str::uuid(),
                    'tenant_id'       => $company->id,
                    'subscription_id' => $subscription->id,
                    'status'          => 'free',
                    'is_core'         => $i === 0,
                ]);
            }

            // If immediate activation or paid, activate company as well
            if ($activateImmediately || $subStatus === SubscriptionStatus::ACTIVE) {
                $company->update(['status' => 'active']);
            }

            $this->clearSubscriptionCache($company->id);

            return $subscription;
        });
    }

    public function toggleStatus(Subscription $subscription): Subscription
    {
        $newStatus = match ($subscription->status) {
            SubscriptionStatus::ACTIVE => SubscriptionStatus::SUSPENDED,
            default                    => SubscriptionStatus::ACTIVE,
        };

        $subscription->update(['status' => $newStatus]);

        // If this is the company's latest subscription, sync company status
        $company = $subscription->tenant;
        if ($company && $company->lastSubscription?->id === $subscription->id) {
            $companyStatus = match ($newStatus) {
                SubscriptionStatus::SUSPENDED => 'suspended',
                SubscriptionStatus::ACTIVE    => 'active',
                default                       => $company->status,
            };
            $company->update(['status' => $companyStatus]);
        }

        $this->clearSubscriptionCache($subscription->tenant_id);

        return $subscription;
    }

    public function deleteSubscription(Subscription $subscription): bool
    {
        $tenantId = $subscription->tenant_id;

        return DB::connection('mysql')->transaction(function () use ($subscription, $tenantId) {
            // Nullify subscription_id on associated bills
            Bill::where('subscription_id', $subscription->id)->update(['subscription_id' => null]);

            // Delete license seats and periods
            LicenseSeat::where('subscription_id', $subscription->id)->delete();
            SubscriptionPeriod::where('subscription_id', $subscription->id)->delete();

            // Permanently hard-delete the subscription
            $deleted = (bool) $subscription->delete();

            $this->clearSubscriptionCache($tenantId);

            return $deleted;
        });
    }

    public function clearSubscriptionCache(string $tenantId): void
    {
        Cache::forget("tenant:{$tenantId}:subscription");
    }

    private function calculatePeriodEndForPlan(Carbon $startDate, Plan $plan): Carbon
    {
        if ($plan->subscription_duration_type === 'limited' && $plan->subscription_billing_cycle === 'monthly') {
            return $startDate->copy()->addMonth();
        }

        return $startDate->copy()->addYear();
    }
}
