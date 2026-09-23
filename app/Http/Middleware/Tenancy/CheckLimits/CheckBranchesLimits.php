<?php

namespace App\Http\Middleware\Tenancy\CheckLimits;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Tenants\Branch\Branch;

class CheckBranchesLimits
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = tenant('id');

        $subscriptionData = Tenancy::central(function () use ($tenantId) {
            $subscription = Subscription::where('tenant_id', $tenantId)->with('plan')->first();

            if (!$subscription || !$subscription->plan) {
                return null;
            }

            return [
                'unlimited_branches' => $subscription->plan->unlimited_branches,
                'branches_limit'     => $subscription->plan->branches_limit,
                'plan_name'          => $subscription->plan->name,
            ];
        });

        if (!$subscriptionData) {
            return redirect()->back()->with('error', 'لا يوجد اشتراك نشط');
        }

        if ($subscriptionData['unlimited_branches']) {
            $request->merge([
                'subscription_limit' => null, // غير محدود
                'unlimited_branches' => true,
                'current_count'      => Branch::count(),
            ]);

            return $next($request);
        }

        $currentCount   = Branch::count();
        $limit          = $subscriptionData['branches_limit'] ?? 0;

        if ($currentCount >= $limit) {
            return redirect()->back()->with(
                'error',
                "لقد وصلت للحد الأقصى المسموح في باقتك ({$subscriptionData['plan_name']}). الحد الأقصى: {$limit} فرع"
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