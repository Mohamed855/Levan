<?php

namespace App\Http\Middleware\Tenancy\CheckLimits;

use Closure;
use Illuminate\Http\Request;
use Stancl\Tenancy\Facades\Tenancy;
use App\Models\Admin\Subscription\Subscription;

class CheckDomainManagement
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
                'domain_management_enabled' => $subscription->plan->domain_management_enabled,
                'plan_name'                 => $subscription->plan->name,
            ];
        });

        // التحقق من وجود اشتراك نشط
        if (!$subscriptionData) {
            return redirect()->to(tenant_route_name('dashboard'))->with('error', 'لا يوجد اشتراك نشط');
        }

        // التحقق من تفعيل ميزة إدارة النطاقات في الباقة
        if (!$subscriptionData['domain_management_enabled']) {
            return redirect()->to(tenant_route_name('dashboard'))->with('error', "ميزة إعدادات الدومين غير متاحة في باقتك ({$subscriptionData['plan_name']}). يرجى ترقية الباقة للاستفادة من هذه الميزة.");
        }

        $request->merge([
            'domain_management_enabled' => true,
            'plan_name'                 => $subscriptionData['plan_name'],
        ]);

        return $next($request);
    }
}