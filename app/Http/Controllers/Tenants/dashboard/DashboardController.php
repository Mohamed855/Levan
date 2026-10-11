<?php

namespace App\Http\Controllers\Tenants\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Display tenant dashboard with core foundation metrics.
     */
    public function index(Request $request = null)
    {
        $tenant = tenant();
        $subscription = $tenant?->lastSubscription;

        $statusLabel = 'غير نشط';
        if ($subscription?->status) {
            $statusLabel = $subscription->status instanceof \App\Enums\Admin\Subscriptions\SubscriptionStatus
                ? $subscription->status->label()
                : (string) $subscription->status;
        }

        $stats = [
            'total_users'         => User::count(),
            'active_users'        => User::where('status', 'active')->count(),
            'total_teams'         => Team::count(),
            'plan_name'           => $subscription?->plan?->name ?? 'غير محدد',
            'subscription_status' => $statusLabel,
            'next_payment_date'   => $subscription?->next_payment_date ?? null,
            'total_seats'         => $subscription?->total_users ?? 0,
        ];

        return view('tenants.dashboard.index', compact('stats', 'tenant', 'subscription'));
    }
}
