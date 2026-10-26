<?php

namespace App\Http\Controllers\Admin\ActivityLog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ActivityLog\AdminActivityLogIndexRequest;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Traits\ActivityLogPresenterTrait;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Spatie\Activitylog\Models\Activity;
use Yajra\DataTables\Facades\DataTables;

class AdminActivityLogController extends Controller
{
    use ActivityLogPresenterTrait;

    public function __construct()
    {
        $this->middleware('can:activity_log.view');
    }

    public function index(AdminActivityLogIndexRequest $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = Activity::with('causer', 'subject')
                ->select([
                    'id',
                    'log_name',
                    'description',
                    'subject_id',
                    'subject_type',
                    'causer_id',
                    'causer_type',
                    'properties',
                    'created_at',
                ]);

            // تطبيق الفلاتر
            $this->applyFilters($query, $request->validated());

            return DataTables::of($query)
                ->addColumn('checkbox', function ($activity) {
                    return '<input type="checkbox" class="row-checkbox" value="'.$activity->id.'">';
                })

                ->addColumn('log_name_formatted', function ($activity) {
                    return '<span class="badge bg-primary">'.$this->activityLogNameLabel($activity->log_name).'</span>';
                })

                ->addColumn('causer_name', function ($activity) {
                    return $activity->causer ? $activity->causer->name : 'النظام';
                })

                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->locale('ar')->diffForHumans();
                })

                ->addColumn('action', function ($activity) {
                    return $this->getActionsColumn($activity);
                })

                ->orderColumn('created_at', fn ($query, $order) => $query->orderBy('created_at', $order))
                ->rawColumns(['checkbox', 'log_name_formatted',  'created_at', 'action'])
                ->make(true);
        }

        // الإحصائيات للعرض
        $statistics = $this->getStatistics();
        $users = AdminUser::select('id', 'name')->orderBy('name')->get();

        $filterOptions = [
            'users' => $users->map(fn ($user): array => [
                'value' => $user->id,
                'label' => $user->name,
            ]),
        ];

        return view('admin.activity-logs.index', [
            'users' => $users,
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function show($id): View
    {
        $activity = Activity::with('causer', 'subject')->findOrFail($id);
        $activityData = $this->prepareActivityData($activity);
        $arabicEvent = $activityData['event_arabic'];

        return view('admin.activity-logs.show', compact('activity', 'arabicEvent', 'activityData'));
    }

    /*
    |============================================================================
    |============================================================================
    |                          Private Methods
    |============================================================================
    |============================================================================
    */
    private function prepareActivityData($activity): array
    {
        $eventMap = [
            'created' => 'إضافة',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'restored' => 'استعادة',
        ];
        $arabicEvent = $eventMap[$activity->event] ?? ($activity->event ?? 'غير محدد');

        return [
            'event_arabic' => $arabicEvent,
            'log_name_arabic' => $this->activityLogNameLabel($activity->log_name),
            'subject_display' => $this->activitySubjectDisplay($activity),
            'connection_info' => $this->getActivityConnectionInfo($activity),
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['user_id'])) {
            $query->where('causer_id', $filters['user_id']);
        }

        if (isset($filters['log_name'])) {
            $query->where('log_name', $filters['log_name']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $search = $filters['search']['value'] ?? null;
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%");
            });
        }
    }

    private function getStatistics(): array
    {
        return [
            'total' => Activity::count(),
            'today' => Activity::whereDate('created_at', today())->count(),
            'this_week' => Activity::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'this_month' => Activity::whereMonth('created_at', now()->month)->count(),
        ];
    }

    private function getActionsColumn(Activity $activity): string
    {
        $showUrl = route('admin.activity-logs.show', $activity->id);

        return '<a href="'.$showUrl.'" title="عرض التفاصيل" class="btn btn-icon btn-label-primary">
                <i class="ti ti-eye"></i>
            </a>';
    }
}
