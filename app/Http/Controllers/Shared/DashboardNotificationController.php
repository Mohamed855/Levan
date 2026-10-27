<?php

declare(strict_types=1);

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Http\Requests\Shared\DashboardBroadcastAuthRequest;
use App\Http\Requests\Shared\DashboardNotificationRequest;
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

        return response()->json([
            'items' => $items, 'count' => $user->unreadNotifications()->count(),
            'broadcast' => [
                'channel' => $notices->channel($scope, $scope === 'tenant' ? (string) tenant('id') : null, (int) $user->id),
                'key' => config('broadcasting.connections.reverb.key'),
                'host' => config('broadcasting.connections.reverb.options.host'),
                'port' => config('broadcasting.connections.reverb.options.port'),
                'scheme' => config('broadcasting.connections.reverb.options.scheme'),
            ],
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
}
