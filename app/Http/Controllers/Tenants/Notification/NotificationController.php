<?php

namespace App\Http\Controllers\Tenants\Notification;

use App\Actions\Tenants\Notification\ManageUserNotificationsAction;
use App\DataTables\Tenants\Notifications\NotificationsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Notification\DeleteNotificationRequest;
use App\Http\Requests\Tenants\Notification\MarkAllNotificationsReadRequest;
use App\Http\Requests\Tenants\Notification\MarkNotificationReadRequest;
use App\Http\Requests\Tenants\Notification\MassDeleteNotificationsRequest;

class NotificationController extends Controller
{
    public function __construct(private readonly ManageUserNotificationsAction $notifications) {}

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function index(NotificationsDataTable $dataTable)
    {
        return $dataTable->render('tenant.notifications.index');
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function allPage(NotificationsDataTable $dataTable)
    {
        return $dataTable->render('tenant.notifications.all');
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function unreadCount()
    {
        return response()->json([
            'count' => auth('tenant')->user()->unreadNotifications()->count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function markAsRead(MarkNotificationReadRequest $request)
    {
        $this->notifications->markAsRead($request->user('tenant'), $request->validated('id'));

        return response()->json(['success' => true, 'message' => 'تم تحديد الإشعار كمقروء']);
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function markAllAsRead(MarkAllNotificationsReadRequest $request)
    {
        $this->notifications->markAllAsRead($request->user('tenant'));

        return response()->json(['success' => true, 'message' => 'تم تحديد جميع الإشعارات كمقروءة']);
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function destroy(DeleteNotificationRequest $request)
    {
        $this->notifications->delete($request->user('tenant'), $request->validated('id'));

        return response()->json(['success' => true, 'message' => 'تم حذف الإشعار']);
    }

    public function massDelete(MassDeleteNotificationsRequest $request)
    {
        $deleted = $this->notifications->deleteMany($request->user('tenant'), $request->validated('ids'));

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? 'تم حذف الإشعارات المحددة' : 'لا توجد إشعارات قابلة للحذف',
            'deleted_count' => $deleted,
        ]);
    }
}
