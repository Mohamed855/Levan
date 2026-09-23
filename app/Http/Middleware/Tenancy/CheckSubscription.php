<?php

namespace App\Http\Middleware\Tenancy;

use App\Enums\Admin\Billing\BillStatus;
use Carbon\Carbon;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stancl\Tenancy\Facades\Tenancy;

class CheckSubscription
{
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check()) {
            return $next($request);
        }

        $tenant = tenant();

        $subscription = cache()->remember(
            "tenant:{$tenant->id}:subscription",
            now()->addMinutes(5),
            fn() => Tenancy::central(fn() => $tenant->lastSubscription)
        );


        // لا يوجد اشتراك
        if (!$subscription) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(tenant_route_name('login'))->withErrors([
                'email' => 'يرجى التواصل مع الإدارة لتفعيل الاشتراك.',
            ]);
        }

        // فترة تجربة منتهية
        if ($subscription->is_trial && now()->gt(Carbon::parse($subscription->trial_end_date))) {
            if (Auth::id() === 1) {
                return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'انتهت فترة التجربة. يرجى تجديد الاشتراك لمتابعة استخدام الخدمات.');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(tenant_route_name('login'))->withErrors([
                'email' => 'انتهت فترة التجربة. يرجى التواصل مع الإدارة لتفعيل الاشتراك.',
            ]);
        }


        // الاشتراك معلق بانتظار دفع الفاتورة
        if ($subscription->is_suspended) {

            $pendingBill = $tenant->bills()->whereIn('status', [BillStatus::UNPAID, BillStatus::PENDING_VERIFICATION, BillStatus::CANCELLED])->latest()->first();

            if ($pendingBill) {
                return redirect()->to(tenant_route_name('bills.show', $pendingBill))->with('warning', 'تم تعليق اشتراكك. يرجى سداد الفاتورة أدناه او الانتظار حتي مراجعة الفاتورة .');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(tenant_route_name('login'))->withErrors([
                'email' => 'تم تعليق الوصول  . يرجى التواصل مع الإدارة.',
            ]);
        }

        // الاشتراك منتهي
        if ($subscription->is_expired) {

            if (Auth::id() === 1) {
                return redirect()->to(tenant_route_name('subscriptions.index'))->with('error', 'انتهت صلاحية الاشتراك. يرجى تجديد الاشتراك لمتابعة استخدام الخدمات.');
            }

            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(tenant_route_name('login'))->withErrors([
                'email' => 'تم تعليق الوصول بسبب انتهاء صلاحية الاشتراك. يرجى التواصل مع الإدارة.',
            ]);
        }


        if ($subscription->is_canceled) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->to(tenant_route_name('login'))->withErrors([
                'email' => 'تم الغاء اشتراكك يرجى التواصل مع الدعم الفني.',
            ]);
        }

        return $next($request);
    }
}