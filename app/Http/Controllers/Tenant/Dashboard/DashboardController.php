<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Auction;
use App\Models\Tenant\Property;
use App\Services\Tenants\Shared\CentralBillingGateway;
use App\Services\Tenants\Shared\CentralTicketGateway;
use Illuminate\View\View;

final class DashboardController extends Controller
{
    public function __invoke(
        CentralBillingGateway $billingGateway,
        CentralTicketGateway $ticketGateway
    ): View {
        $activeAssignments = Assignment::query()->where('is_active', true)->count();
        $totalProperties = Property::query()->count();
        $unlinkedProperties = Property::query()->whereNull('assignment_id')->count();
        $ongoingAuctions = Auction::query()->where('is_active', true)->count();
        $openTickets = $ticketGateway->ticketsQueryForCurrentTenant()
            ->whereIn('status', ['open', 'reopened', 'in_progress'])
            ->count();

        $subscription = $billingGateway->currentSubscription();

        $statistics = [
            [
                'label' => 'الإسنادات النشطة',
                'value' => $activeAssignments,
                'subtitle' => 'إسنادات سارية بالمنشأة',
                'icon' => 'ti-file-description',
                'tone' => 'primary',
            ],
            [
                'label' => 'العقارات المسجلة',
                'value' => $totalProperties,
                'subtitle' => "{$unlinkedProperties} عقار غير مربوط بإسناد",
                'icon' => 'ti-building-estate',
                'tone' => 'success',
            ],
            [
                'label' => 'المزادات الجارية',
                'value' => $ongoingAuctions,
                'subtitle' => 'مزادات نشطة حالياً',
                'icon' => 'ti-gavel',
                'tone' => 'info',
            ],
            [
                'label' => 'تذاكر الدعم المفتوحة',
                'value' => $openTickets,
                'subtitle' => 'قيد المعالجة والمتابعة',
                'icon' => 'ti-headset',
                'tone' => 'warning',
            ],
        ];

        $recentAssignments = Assignment::query()->withCount('properties', 'auctions')->latest()->limit(5)->get();
        $recentAuctions = Auction::query()->with('assignment')->latest()->limit(5)->get();

        return view('tenant.dashboard.index', [
            'statistics' => $statistics,
            'subscription' => $subscription,
            'recentAssignments' => $recentAssignments,
            'recentAuctions' => $recentAuctions,
        ]);
    }
}
