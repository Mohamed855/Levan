<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Dashboard;

use App\Enums\Admin\Tickets\TicketStatus;
use App\Http\Controllers\Controller;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscriptions\Subscription;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Ticket;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $totalTenants = Tenant::query()->count();
        $activeTenants = Tenant::query()->whereHas('subscriptions', fn ($q) => $q->where('status', 'active'))->count();
        $expiringSoonSubscriptions = Subscription::query()
            ->where('status', 'active')
            ->whereBetween('ends_at', [now(), now()->addDays(30)])
            ->count();
        $pendingSubscriptions = Subscription::query()->where('status', 'pending')->count();
        $openTickets = Ticket::query()->whereIn('status', [TicketStatus::OPEN->value, TicketStatus::REOPENED->value])->count();
        $pendingTrialRequests = TrialRequest::query()->where('status', 'pending')->count();
        $pendingContactRequests = ContactRequest::query()->where('status', 'pending')->count();

        $statistics = [
            [
                'label' => 'إجمالي المستأجرين',
                'value' => $totalTenants,
                'subtitle' => "{$activeTenants} مستأجر نشط",
                'icon' => 'ti-building',
                'tone' => 'primary',
            ],
            [
                'label' => 'اشتراكات تنتهي قريباً',
                'value' => $expiringSoonSubscriptions,
                'subtitle' => 'خلال أقل من 30 يوماً',
                'icon' => 'ti-calendar-exclamation',
                'tone' => 'warning',
            ],
            [
                'label' => 'اشتراكات معلقة',
                'value' => $pendingSubscriptions,
                'subtitle' => 'بانتظار التأكيد والسداد',
                'icon' => 'ti-hourglass-low',
                'tone' => 'info',
            ],
            [
                'label' => 'تذاكر دعم مفتوحة',
                'value' => $openTickets,
                'subtitle' => 'تحتاج إلى رد ومتابعة',
                'icon' => 'ti-headset',
                'tone' => 'danger',
            ],
            [
                'label' => 'طلبات تجربة معلقة',
                'value' => $pendingTrialRequests,
                'subtitle' => 'طلبات تجربة المنصة',
                'icon' => 'ti-clock-hour-4',
                'tone' => 'info',
            ],
            [
                'label' => 'رسائل تواصل جديدة',
                'value' => $pendingContactRequests,
                'subtitle' => 'استفسارات زوار المنصة',
                'icon' => 'ti-mail-fast',
                'tone' => 'success',
            ],
        ];

        $recentTrialRequests = TrialRequest::query()->latest()->limit(5)->get();
        $recentTickets = Ticket::query()->with('tenant')->latest()->limit(5)->get();

        return view('admin.dashboard.index', [
            'statistics' => $statistics,
            'recentTrialRequests' => $recentTrialRequests,
            'recentTickets' => $recentTickets,
        ]);
    }
}
