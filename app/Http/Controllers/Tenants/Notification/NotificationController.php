<?php

namespace App\Http\Controllers\Tenants\Notification;

use App\DataTables\Tenants\Notifications\NotificationsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Notification\DeleteNotificationRequest;
use App\Http\Requests\Tenants\Notification\MarkAllNotificationsReadRequest;
use App\Http\Requests\Tenants\Notification\MarkNotificationReadRequest;
use App\Http\Requests\Tenants\Notification\MassDeleteNotificationsRequest;

class NotificationController extends Controller
{
    
    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function index(NotificationsDataTable $dataTable)
    {
        return $dataTable->render('tenants.notifications.index');
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function allPage(NotificationsDataTable $dataTable)
    {
        return $dataTable->render('tenants.notifications.all');
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
            'count' => auth('tenant')->user()->unreadNotifications()->count()
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
        $notification = auth('tenant')->user()->notifications()->findOrFail($request->validated('id'));
        $notification->markAsRead();

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
        auth('tenant')->user()->unreadNotifications->markAsRead();

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
        $notification = auth('tenant')->user()->notifications()->findOrFail($request->validated('id'));
        $notification->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الإشعار']);
    }

    public function massDelete(MassDeleteNotificationsRequest $request)
    {
        $deleted = auth('tenant')->user()
            ->notifications()
            ->whereIn('id', $request->validated('ids'))
            ->delete();

        return response()->json([
            'success' => true,
            'message' => $deleted > 0 ? 'تم حذف الإشعارات المحددة' : 'لا توجد إشعارات قابلة للحذف',
            'deleted_count' => $deleted,
        ]);
    }
}
