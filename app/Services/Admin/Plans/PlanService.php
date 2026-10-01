<?php

declare(strict_types=1);

namespace App\Services\Admin\Plans;

use App\Enums\Admin\Billing\BillStatus;
use App\Enums\Admin\Billing\BillType;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Models\Admin\Bills\Bill;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscription\Subscription;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PlanService
{
    public const MOST_REQUESTED_CACHE_KEY = 'plans:most-requested:last-30-days';

    /**
     * Create a new plan.
     */
    public function create(array $data): Plan
    {
        return DB::transaction(function () use ($data) {
            $features = [];
            if (!empty($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (!empty(trim((string) $feature))) {
                        $features[] = trim((string) $feature);
                    }
                }
            }

            $limitsMode = (string) ($data['limits_mode'] ?? PlanLimitsMode::LIMITED->value);
            $isUnlimited = $limitsMode === PlanLimitsMode::UNLIMITED->value;

            $code = strtoupper(trim((string) ($data['code'] ?? '')));

            $plan = Plan::create([
                ...$data,
                'code'                      => $code,
                'limits_mode'               => $limitsMode,
                'included_users'            => $isUnlimited ? null : ($data['included_users'] ?? 1),
                'properties_limit'          => $isUnlimited ? null : ($data['properties_limit'] ?? null),
                'storage_quota_gb'          => $isUnlimited ? null : ($data['storage_quota_gb'] ?? null),
                'active_auctions_limit'     => $isUnlimited ? null : ($data['active_auctions_limit'] ?? null),
                'branches_limit'            => $isUnlimited ? null : ($data['branches_limit'] ?? null),
                'unlimited_branches'        => $isUnlimited ? true : (bool) ($data['unlimited_branches'] ?? false),
                'features'                  => $features,
                'created_by'                => auth()->id(),
            ]);

            $this->clearMostRequestedCache();

            return $plan;
        });
    }

    /**
     * Update an existing plan with safety checks for plans already in use.
     */
    public function update(Plan $plan, array $data): Plan
    {
        return DB::transaction(function () use ($plan, $data) {
            $hasSubscriptions = $this->hasSubscriptions($plan);

            $features = [];
            if (!empty($data['features'])) {
                foreach ($data['features'] as $feature) {
                    if (!empty(trim((string) $feature))) {
                        $features[] = trim((string) $feature);
                    }
                }
            }

            // If plan has subscriptions, prevent altering critical contract terms
            if ($hasSubscriptions) {
                unset($data['code'], $data['subscription_billing_cycle'], $data['limits_mode']);
                $limitsMode = $plan->limits_mode instanceof PlanLimitsMode ? $plan->limits_mode->value : (string) $plan->limits_mode;
            } else {
                $limitsMode = (string) ($data['limits_mode'] ?? ($plan->limits_mode->value ?? $plan->limits_mode));
                if (isset($data['code'])) {
                    $data['code'] = strtoupper(trim((string) $data['code']));
                }
            }

            $isUnlimited = $limitsMode === PlanLimitsMode::UNLIMITED->value;

            $updateData = [
                ...$data,
                'features'   => !empty($features) ? $features : $plan->features,
                'updated_by' => auth()->id(),
            ];

            if ($isUnlimited) {
                $updateData['included_users']        = null;
                $updateData['properties_limit']      = null;
                $updateData['storage_quota_gb']      = null;
                $updateData['active_auctions_limit'] = null;
                $updateData['branches_limit']        = null;
                $updateData['unlimited_branches']    = true;
            }

            $plan->update($updateData);

            $this->clearMostRequestedCache();

            return $plan;
        });
    }

    /**
     * Check if a plan has any linked subscriptions.
     */
    public function hasSubscriptions(Plan $plan): bool
    {
        return $plan->subscriptions()->exists();
    }

    /**
     * Soft delete a plan only if it has NO subscriptions.
     *
     * @throws \DomainException
     */
    public function delete(Plan $plan): bool
    {
        if ($this->hasSubscriptions($plan)) {
            throw new \DomainException(__('plans.errors.cannot_delete_used_plan'));
        }

        return DB::transaction(function () use ($plan) {
            $deleted = (bool) $plan->delete();
            $this->clearMostRequestedCache();
            return $deleted;
        });
    }

    /**
     * Force delete a plan only if it has NO subscriptions.
     *
     * @throws \DomainException
     */
    public function forceDelete(Plan $plan): bool
    {
        if ($this->hasSubscriptions($plan)) {
            throw new \DomainException(__('plans.errors.cannot_force_delete_used_plan'));
        }

        return DB::transaction(function () use ($plan) {
            $deleted = (bool) $plan->forceDelete();
            $this->clearMostRequestedCache();
            return $deleted;
        });
    }

    /**
     * Toggle the active status of a plan.
     */
    public function toggleActive(Plan $plan): Plan
    {
        $plan->update([
            'is_active'  => !$plan->is_active,
            'updated_by' => auth()->id(),
        ]);

        $this->clearMostRequestedCache();

        return $plan->refresh();
    }

    /**
     * Get aggregated plan and subscription statistics including verified monthly revenue.
     */
    public function getPlanStatistics(): array
    {
        $totalPlans    = Plan::count();
        $activePlans   = Plan::where('is_active', true)->count();
        $inactivePlans = Plan::where('is_active', false)->count();

        $activeSubscriptions = Subscription::where('status', SubscriptionStatus::ACTIVE->value)->count();
        $expiredSubscriptions = Subscription::whereIn('status', [
            SubscriptionStatus::EXPIRED->value,
            SubscriptionStatus::CANCELLED->value,
        ])->count();

        // Monthly revenue: paid bills in the current month for new subscriptions or renewals only
        $startOfMonth = now()->startOfMonth();
        $endOfMonth   = now()->endOfMonth();

        $monthlyRevenue = (float) Bill::query()
            ->where('status', BillStatus::PAID->value)
            ->whereBetween('paid_at', [$startOfMonth, $endOfMonth])
            ->whereIn('type', [
                BillType::NEW_SUBSCRIPTION->value,
                BillType::RENEWAL->value,
            ])
            ->sum('amount');

        return [
            'total'                 => $totalPlans,
            'active'                => $activePlans,
            'inactive'              => $inactivePlans,
            'active_subscriptions'  => $activeSubscriptions,
            'expired_subscriptions' => $expiredSubscriptions,
            'monthly_revenue'       => $monthlyRevenue,
        ];
    }

    /**
     * Calculate or retrieve from cache the most requested plan ID in the last 30 days.
     */
    public function getMostRequestedPlanId(): ?int
    {
        return Cache::remember(self::MOST_REQUESTED_CACHE_KEY, 3600, function () {
            return DB::table('subscriptions')
                ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
                ->where('subscriptions.status', SubscriptionStatus::ACTIVE->value)
                ->where('subscriptions.created_at', '>=', now()->subDays(30))
                ->whereNull('plans.deleted_at')
                ->select('subscriptions.plan_id', DB::raw('COUNT(*) as active_count'))
                ->groupBy('subscriptions.plan_id', 'plans.sort_order', 'plans.created_at')
                ->orderByDesc('active_count')
                ->orderBy('plans.sort_order', 'asc')
                ->orderBy('plans.created_at', 'asc')
                ->value('subscriptions.plan_id');
        });
    }

    public function clearMostRequestedCache(): void
    {
        Cache::forget(self::MOST_REQUESTED_CACHE_KEY);
    }

    /**
     * Get plan full details with subscriptions snapshot information and unique company stats.
     */
    public function getPlanDetails(Plan $plan): array
    {
        $plan->load(['creator', 'updater']);

        $subscriptions = Subscription::where('plan_id', $plan->id)
            ->with(['tenant.city', 'currentPeriod'])
            ->orderBy('created_at', 'desc')
            ->get();

        $mostRequestedPlanId = $this->getMostRequestedPlanId();

        $statistics = [
            'total_subscriptions'   => $subscriptions->count(),
            'unique_companies'      => $subscriptions->pluck('tenant_id')->unique()->count(),
            'active_subscriptions'  => $subscriptions->where('status', SubscriptionStatus::ACTIVE)->count(),
            'expired_subscriptions' => $subscriptions->whereIn('status', [
                SubscriptionStatus::EXPIRED,
                SubscriptionStatus::CANCELLED,
            ])->count(),
            'total_revenue'         => (float) $subscriptions->sum('price'),
            'is_most_requested'     => $mostRequestedPlanId !== null && (int) $plan->id === (int) $mostRequestedPlanId,
        ];

        return [
            'plan'                => $plan,
            'subscriptions'       => $subscriptions,
            'statistics'          => $statistics,
            'mostRequestedPlanId' => $mostRequestedPlanId,
        ];
    }
}
