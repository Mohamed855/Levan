<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\ActivityLog;

use App\DataTables\Admin\ActivityLog\AdminActivityLogDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityLog\AdminActivityLogIndexRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Services\Shared\ActivityLog\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;

class AdminActivityLogController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
        $this->middleware('can:activity_log.view');
    }

    public function index(AdminActivityLogIndexRequest $request, AdminActivityLogDataTable $dataTable): View|JsonResponse
    {
        if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
            return $dataTable->render('admin.activity-logs.index');
        }

        $statistics = $this->activityLogService->getStatistics();
        $users = AdminUser::select('id', 'name')->orderBy('name')->get();

        $filterOptions = [
            'users' => $users->map(fn (AdminUser $user): array => [
                'value' => $user->id,
                'label' => $user->name,
            ]),
            'log_names' => $this->activityLogService->getLogNameFilterOptions(),
        ];

        return $dataTable->render('admin.activity-logs.index', [
            'users' => $users,
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function show($id): View
    {
        $activity = Activity::with('causer')->findOrFail($id);

        return view('admin.activity-logs.show', $this->activityLogService->getShowViewData($activity));
    }
}
