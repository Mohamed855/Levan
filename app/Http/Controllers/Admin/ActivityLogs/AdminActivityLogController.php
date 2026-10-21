<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\ActivityLogs;

use App\DataTables\Admin\ActivityLogs\ActivityLogDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\ActivityLog;
use App\Models\Admin\AdminUser;
use App\Traits\ActivityLogPresenterTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminActivityLogController extends Controller
{
    use ActivityLogPresenterTrait;

    public function __construct()
    {
        $this->middleware('can:activity_log.view');
    }

    public function index(ActivityLogDataTable $dataTable)
    {
        $statistics = [
            'total'      => ActivityLog::query()->count(),
            'today'      => ActivityLog::query()->whereDate('created_at', today())->count(),
            'this_week'  => ActivityLog::query()->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => ActivityLog::query()->whereMonth('created_at', now()->month)->count(),
            'by_system'  => ActivityLog::query()->whereNull('causer_id')->count(),
        ];

        $users = AdminUser::query()->select('id', 'name')->orderBy('name')->get();
        $logNames = ActivityLog::query()->whereNotNull('log_name')->distinct()->pluck('log_name');

        $filterOptions = [
            'users' => $users->map(fn ($user): array => [
                'value' => (string) $user->id,
                'label' => $user->name,
            ])->toArray(),
            'log_names' => $logNames->map(fn ($logName): array => [
                'value' => $logName,
                'label' => $this->activityLogNameLabel($logName),
            ])->toArray(),
        ];

        return $dataTable->render('admin.activity-logs.index', compact('statistics', 'filterOptions'));
    }

    public function show(ActivityLog $activityLog): View
    {
        $activityLog->load(['causer', 'subject']);
        $activityData = $this->prepareActivityData($activityLog);

        return view('admin.activity-logs.show', compact('activityLog', 'activityData'));
    }

    public function destroy(Request $request, ActivityLog $activityLog): RedirectResponse|JsonResponse
    {
        $activityLog->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف سجل النشاط بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف سجل النشاط بنجاح.');
    }

    private function prepareActivityData(ActivityLog $activity): array
    {
        $eventMap = [
            'created'  => 'إضافة',
            'updated'  => 'تحديث',
            'deleted'  => 'حذف',
            'restored' => 'استعادة',
            'login'    => 'تسجيل دخول',
            'logout'   => 'تسجيل خروج',
        ];

        $arabicEvent = $eventMap[$activity->event] ?? ($activity->event ?? 'عملية نظام');

        return [
            'event_arabic'    => $arabicEvent,
            'log_name_arabic' => $this->activityLogNameLabel($activity->log_name),
            'subject_display' => $this->activitySubjectDisplay($activity),
            'connection_info' => $this->getActivityConnectionInfo($activity),
        ];
    }
}
