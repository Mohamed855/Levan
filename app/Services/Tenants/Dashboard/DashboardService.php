<?php

declare(strict_types=1);

namespace App\Services\Tenants\Dashboard;

use App\Enums\Tenants\Properties\PropertyDataStatus;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Properties\PropertyService;
use App\Services\Tenants\Shared\CentralSubscriptionContextGateway;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Activity;

class DashboardService
{
    public function __construct(
        private readonly CentralSubscriptionContextGateway $subscriptionGateway,
        private readonly PropertyService $propertyService
    ) {}

    /** @return array{stats:array<string,mixed>,tenant:mixed,subscription:null} */
    public function getFoundationDashboardData(): array
    {
        $subscription = $this->subscriptionGateway->current();

        return [
            'stats' => [
                'total_users' => User::query()->count(),
                'active_users' => User::query()->where('status', 'active')->count(),
                'total_teams' => 0,
                ...$subscription,
            ],
            'tenant' => tenant(),
            'subscription' => null,
        ];
    }

    public function getDashboardData(): array
    {
        $subscription = $this->subscriptionGateway->current();

        return [
            // مؤشرات الأداء الرئيسية (6 كروت)
            'kpis' => $this->getKPIs($subscription),

            // الرسوم البيانية
            'auctionActivity' => $this->getAuctionActivity('1month'),
            'propertyStatus' => $this->getPropertyStatusDistribution(),

            // العمليات والأحداث الأخيرة
            'recentActivities' => $this->getRecentActivities(5),
            'latestAuctions' => $this->getLatestAuctions(5),

            // سياق المنشأة والاشتراك
            'subscription' => $subscription,
            'tenant' => tenant(),
        ];
    }

    public function getAuctionActivityByRange(string $range = '1month'): array
    {
        return $this->getAuctionActivity($range);
    }

    private function getKPIs(array $subscription): array
    {
        $activeAuctionsCount = Auction::query()
            ->where('is_active', true)
            ->count();

        $upcomingAuctionsCount = Auction::query()
            ->where('is_active', true)
            ->whereDate('start_date', '>', now()->toDateString())
            ->count();

        $totalProperties = Property::query()->count();

        // العقارات المباعة من جدول الربط auction_properties
        $soldPropertiesCount = DB::table('auction_properties')
            ->where('result_status', 'sold')
            ->distinct('property_id')
            ->count('property_id');

        $activeUsers = User::query()->where('status', 'active')->count();
        $totalSeats = (int) ($subscription['total_seats'] ?? 0);

        return [
            'total_auctions' => Auction::query()->count(),
            'active_auctions' => $activeAuctionsCount,
            'upcoming_auctions' => $upcomingAuctionsCount,
            'total_properties' => $totalProperties,
            'sold_properties' => $soldPropertiesCount,
            'active_users' => $activeUsers,
            'total_seats' => $totalSeats,
            'total_assignments' => Assignment::query()->count(),
        ];
    }

    public function getAuctionActivity(string $range = '1month'): array
    {
        [$startDate, $interval] = match ($range) {
            '3months' => [now()->subMonths(3)->startOfDay(), 'month'],
            '6months' => [now()->subMonths(6)->startOfDay(), 'month'],
            '1year'   => [now()->subYear()->startOfDay(), 'month'],
            default   => [now()->subMonth()->startOfDay(), 'day'],
        };

        $points = [];
        $cursor = clone $startDate;
        $now = now();

        if ($interval === 'day') {
            $auctions = Auction::query()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', $startDate)
                ->groupBy('date')
                ->pluck('count', 'date');

            while ($cursor->lte($now)) {
                $dateKey = $cursor->format('Y-m-d');
                $label = $cursor->locale('ar')->translatedFormat('j M');

                $points[] = [
                    'label' => $label,
                    'count' => (int) ($auctions[$dateKey] ?? 0),
                ];

                $cursor->addDay();
            }
        } else {
            $monthlyAuctions = Auction::query()
                ->selectRaw('DATE_FORMAT(created_at, "%Y-%m") as month, COUNT(*) as count')
                ->where('created_at', '>=', $startDate)
                ->groupBy('month')
                ->pluck('count', 'month');

            while ($cursor->format('Y-m') <= $now->format('Y-m')) {
                $monthKey = $cursor->format('Y-m');
                $label = $cursor->locale('ar')->translatedFormat('M Y');

                $points[] = [
                    'label' => $label,
                    'count' => (int) ($monthlyAuctions[$monthKey] ?? 0),
                ];

                $cursor->addMonth();
            }
        }

        return $points;
    }

    public function getPropertyStatusDistribution(): array
    {
        $statistics = $this->propertyService->getStatistics();
        $total = $statistics['total'];

        $statuses = [
            [
                'key' => 'total',
                'label' => 'إجمالي العقارات',
                'color' => 'primary',
                'count' => $statistics['total'],
            ],
            [
                'key' => 'active',
                'label' => 'عقار نشط',
                'color' => 'success',
                'count' => $statistics['active'],
            ],
            [
                'key' => 'completed',
                'label' => 'مكتملة البيانات',
                'color' => 'info',
                'count' => $statistics['completed'],
            ],
            [
                'key' => 'unlinked',
                'label' => 'غير مرتبطة بإسناد',
                'color' => 'warning',
                'count' => $statistics['unlinked'],
            ],
            [
                'key' => 'needs_review',
                'label' => 'تحتاج للمراجعة',
                'color' => 'danger',
                'count' => $statistics['needs_review'],
            ],
        ];

        return collect($statuses)->map(function ($item) use ($total) {
            $count = $item['count'];

            return [
                'key' => $item['key'],
                'label' => $item['label'],
                'color' => $item['color'],
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        })->values()->toArray();
    }

    public function getRecentActivities(int $limit = 5): Collection
    {
        $eventMap = [
            'created' => 'إضافة',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'restored' => 'استعادة',
            'login' => 'تسجيل دخول',
            'logout' => 'تسجيل خروج',
        ];

        $logMap = [
            'auctions' => 'المزادات',
            'properties' => 'العقارات',
            'assignments' => 'الإسنادات',
            'users' => 'المستخدمين',
            'roles' => 'الأدوار',
            'settings' => 'الإعدادات',
            'auth' => 'تسجيل الدخول',
            'tickets' => 'الدعم الفني',
            'reports' => 'التقارير',
        ];

        return Activity::query()
            ->with(['causer', 'subject'])
            ->latest()
            ->limit($limit)
            ->get()
            ->map(function ($activity) use ($eventMap, $logMap) {
                $activity->formatted_event = $eventMap[$activity->event] ?? ($activity->description ?? $activity->event ?? 'نشاط');
                $activity->formatted_module = $logMap[$activity->log_name] ?? ($activity->log_name ?? '');
                $activity->causer_name = $activity->causer ? $activity->causer->name : 'النظام';
                $activity->time_ago = $activity->created_at ? Carbon::parse($activity->created_at)->locale('ar')->diffForHumans() : '-';

                return $activity;
            });
    }

    public function getLatestAuctions(int $limit = 5): Collection
    {
        return Auction::query()
            ->withCount('properties')
            ->latest()
            ->limit($limit)
            ->get();
    }
}
