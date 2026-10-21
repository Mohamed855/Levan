<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Notifications;

use App\Actions\Tenant\Notifications\ManageUserNotificationsAction;
use App\DataTables\Tenant\Notifications\TenantNotificationDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Notifications\DeleteNotificationRequest;
use App\Http\Requests\Tenant\Notifications\MarkNotificationReadRequest;
use App\Http\Requests\Tenant\Notifications\MassDeleteNotificationsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TenantNotificationController extends Controller
{
    public function __construct(
        private readonly ManageUserNotificationsAction $notifications
    ) {
        $this->middleware('auth:tenant');
    }

    public function index(TenantNotificationDataTable $dataTable)
    {
        return $dataTable->render('tenant.notifications.index', [
            'stats' => $dataTable->getStats(),
        ]);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $user = $request->user('tenant');

        return response()->json([
            'count' => $user ? $user->unreadNotifications()->count() : 0,
        ]);
    }

    public function markAsRead(MarkNotificationReadRequest $request): JsonResponse
    {
        $this->notifications->markAsRead($request->user('tenant'), $request->validated('id'));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد الإشعار كمقروء.',
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $this->notifications->markAllAsRead($request->user('tenant'));

        return response()->json([
            'success' => true,
            'message' => 'تم تحديد جميع الإشعارات كمقروءة.',
        ]);
    }

    public function destroy(DeleteNotificationRequest $request): JsonResponse
    {
        $this->notifications->delete($request->user('tenant'), $request->validated('id'));

        return response()->json([
            'success' => true,
            'message' => 'تم حذف الإشعار بنجاح.',
        ]);
    }

    public function massDelete(MassDeleteNotificationsRequest $request): JsonResponse
    {
        $deleted = $this->notifications->deleteMany($request->user('tenant'), $request->validated('ids'));

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? "تم حذف {$deleted} إشعار بنجاح." : 'لا توجد إشعارات محددة للحذف.',
            'deleted_count' => $deleted,
        ]);
    }
}
