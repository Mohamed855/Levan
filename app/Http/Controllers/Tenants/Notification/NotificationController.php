<?php

namespace App\Http\Controllers\Tenants\Notification;

use Illuminate\Http\Request;
use App\DataTables\Tenants\Notifications\NotificationsDataTable;
use App\Http\Controllers\Controller;

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
            'count' => auth()->user()->unreadNotifications->count()
        ]);
    }
    
    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function markAsRead($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->markAsRead();

        return response()->json(['message' => 'تم تحديد الإشعار كمقروء']);
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications->markAsRead();

        return response()->json(['message' => 'تم تحديد جميع الإشعارات كمقروءة']);
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function destroy($id)
    {
        $notification = auth()->user()->notifications()->findOrFail($id);
        $notification->delete();

        return response()->json(['message' => 'تم حذف الإشعار']);
    }
}