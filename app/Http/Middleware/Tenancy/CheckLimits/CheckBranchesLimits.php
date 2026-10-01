<?php

namespace App\Http\Middleware\Tenancy\CheckLimits;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenants\Branch\Branch;
use App\Services\shared\Subscription\SubscriptionLimitsResolver;

class CheckBranchesLimits
{
    protected SubscriptionLimitsResolver $limitsResolver;

    public function __construct(?SubscriptionLimitsResolver $limitsResolver = null)
    {
        $this->limitsResolver = $limitsResolver ?? app(SubscriptionLimitsResolver::class);
    }

    public function handle(Request $request, Closure $next)
    {
        $tenantId = tenant('id');

        $limits = $this->limitsResolver->resolveForTenant($tenantId);

        if ($limits['plan_name'] === '-' && $limits['branches_limit'] === 0 && !$limits['is_unlimited']) {
            return redirect()->back()->with('error', 'لا يوجد اشتراك نشط');
        }

        if ($limits['is_unlimited'] || $limits['unlimited_branches']) {
            $request->merge([
                'subscription_limit' => null, // غير محدود
                'unlimited_branches' => true,
                'current_count'      => Branch::count(),
            ]);

            return $next($request);
        }

        $currentCount = Branch::count();
        $limit        = $limits['branches_limit'] ?? 0;

        if ($currentCount >= $limit) {
            return redirect()->back()->with(
                'error',
                "لقد وصلت للحد الأقصى المسموح في باقتك ({$limits['plan_name']}). الحد الأقصى: {$limit} فرع"
            );
        }

        $request->merge([
            'subscription_limit' => $limit,
            'unlimited_branches' => false,
            'current_count'      => $currentCount,
            'remaining_count'    => $limit - $currentCount,
        ]);

        return $next($request);
    }
}