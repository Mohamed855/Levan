<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Enums\Admin\PublicRequests\ContactRequestStatus;
use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Enums\Admin\Subscriptions\SubscriptionStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\DashboardBroadcastAuthRequest;
use App\Http\Requests\Shared\DashboardNotificationRequest;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Subscription\Subscription;
use App\Models\Admin\Tickets\Ticket;
use App\Services\Admin\SubscriptionManagement\SubscriptionManagementService;
use App\Services\Shared\Notifications\DashboardNotificationService;
use Illuminate\Support\Facades\Broadcast;

class DashboardNotificationController extends Controller
{
    private function user(DashboardNotificationRequest $request)
    {
        return $request->user($request->route('dashboard_scope') === 'admin' ? 'admin' : 'tenant');
    }

    public function feed(DashboardNotificationRequest $request, DashboardNotificationService $notices)
    {
        $scope = $request->route('dashboard_scope');
        $user = $this->user($request);
        $items = $user->notifications()->latest()->limit(20)->get()->map(fn ($notice) => [
            'id' => $notice->id, 'data' => $notice->data, 'read_at' => $notice->read_at?->toIso8601String(),
            'created_at' => $notice->created_at->diffForHumans(),
        ]);

        $sidebarCounts = $scope === 'admin' ? $this->getAdminSidebarCounts() : [];
        $unreadCount = $user->unreadNotifications()->count();
        $totalCount = $user->notifications()->count();

        return response()->json([
            'items' => $items,
            'count' => $unreadCount,
            'counts' => ['all' => $totalCount, 'unread' => $unreadCount, 'read' => $totalCount - $unreadCount],
            'sidebar_counts' => $sidebarCounts,
            'broadcast' => config('broadcasting.default') === 'reverb' ? [
                'channel' => $notices->channel($scope, $scope === 'tenant' ? (string) tenant('id') : null, (int) $user->id),
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => config('broadcasting.connections.reverb.options.port'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme'),
            ] : null,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function index(DashboardNotificationRequest $request)
    {
        $scope = (string) $request->route('dashboard_scope', 'admin');
        $user = $this->user($request);
        $filter = (string) $request->query('filter', 'all');

        $query = $user->notifications()->latest();
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(30)->withQueryString();

        $unreadCount = $user->unreadNotifications()->count();
        $totalCount = $user->notifications()->count();
        $readCount = $totalCount - $unreadCount;

        return view('shared.dashboard-notifications', [
            'layout' => $scope === 'admin' ? 'admin.layouts.layoutMaster' : 'tenant.layouts.layoutMaster',
            'notifications' => $notifications,
            'scope' => $scope,
            'currentFilter' => $filter,
            'totalCount' => $totalCount,
            'unreadCount' => $unreadCount,
            'readCount' => $readCount,
        ]);
    }

    public function auth(DashboardBroadcastAuthRequest $request, DashboardNotificationService $notices)
    {
        $scope = $request->route('dashboard_scope');
        $expected = 'private-'.$notices->channel($scope, $scope === 'tenant' ? (string) tenant('id') : null, (int) $this->user($request)->id);
        abort_unless(hash_equals($expected, $request->validated('channel_name')), 403);

        return Broadcast::connection('reverb')->auth($request);
    }

    public function read(DashboardNotificationRequest $request, string $notification, DashboardNotificationService $notices)
    {
        $this->user($request)->notifications()->findOrFail($notification)->markAsRead();

        return $this->changed($request, $notices);
    }

    public function readAll(DashboardNotificationRequest $request, DashboardNotificationService $notices)
    {
        $this->user($request)->unreadNotifications()->update(['read_at' => now()]);

        return $this->changed($request, $notices);
    }

    public function destroy(DashboardNotificationRequest $request, string $notification, DashboardNotificationService $notices)
    {
        $this->user($request)->notifications()->findOrFail($notification)->delete();

        return $this->changed($request, $notices);
    }

    private function changed(DashboardNotificationRequest $request, DashboardNotificationService $notices)
    {
        $scope = $request->route('dashboard_scope');
        $notices->refresh($scope, $scope === 'tenant' ? (string) tenant('id') : null, (int) $this->user($request)->id);

        return response()->json(['success' => true]);
    }

    private function getAdminSidebarCounts(): array
    {
        try {
            $expiringSubscriptions = app(SubscriptionManagementService::class)
                ->getExpiringSoonCount();

            $expiredSubscriptions = Subscription::where(
                'status',
                SubscriptionStatus::EXPIRED
            )->count();

            $totalSubscriptions = $expiringSubscriptions + $expiredSubscriptions;

            $pendingContactRequests = ContactRequest::where(
                'status',
                ContactRequestStatus::PENDING
            )->count();

            $pendingTrialRequests = TrialRequest::where(
                'status',
                TrialRequestStatus::PENDING
            )->count();

            $openTickets = Ticket::whereIn('status', ['open', 'reopened'])->count();

            cache()->put('admin:expiring_subscriptions_count', $expiringSubscriptions, 60);
            cache()->put('admin:expired_subscriptions_count', $expiredSubscriptions, 60);
            cache()->put('admin:pending_contact_requests_count', $pendingContactRequests, 60);
            cache()->put('admin:pending_trial_requests_count', $pendingTrialRequests, 60);
            cache()->put('admin:open_tickets_count', $openTickets, 60);

            return [
                'subscriptions_total' => $totalSubscriptions,
                'subscriptions_all' => 0,
                'subscriptions_pending' => 0,
                'subscriptions_expiring' => $expiringSubscriptions,
                'subscriptions_expired' => $expiredSubscriptions,
                'contact_requests_pending' => $pendingContactRequests,
                'trial_requests_pending' => $pendingTrialRequests,
                'tickets_open' => $openTickets,
            ];
        } catch (\Throwable $e) {
            return [];
        }
    }
}
