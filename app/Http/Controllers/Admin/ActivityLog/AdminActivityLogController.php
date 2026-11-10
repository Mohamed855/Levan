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
        if ($request->ajax() || $request->wantsJson()) {
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
            'log_names' => collect($this->activityLogNameMap())->map(fn ($label, $value): array => [
                'value' => $value,
                'label' => $label,
            ])->values()->all(),
        ];

        return view('admin.activity-logs.index', [
            'users' => $users,
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function show($id): View
    {
        $activity = Activity::with('causer')->findOrFail($id);
        if ($activity->subject_type && class_exists($activity->subject_type)) {
            $activity->load('subject');
        }
        $activityData = $this->prepareActivityData($activity);
        $arabicEvent = $activityData['event_arabic'];
        $changes = $this->prepareChangesData($activity);

        return view('admin.activity-logs.show', compact('activity', 'arabicEvent', 'activityData', 'changes'));
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
        $arabicEvent = $this->activityEventMap()[$activity->event] ?? ($activity->event ?? 'غير محدد');

        return [
            'event_arabic' => $arabicEvent,
            'event_badge_class' => $this->activityEventBadgeClass($activity->event),
            'log_name_arabic' => $this->activityLogNameLabel($activity->log_name),
            'subject_display' => $this->activitySubjectDisplay($activity),
            'connection_info' => $this->getActivityConnectionInfo($activity),
            'user_name' => $activity->causer->name ?? 'النظام',
            'formatted_date' => system_datetime($activity->created_at, 'غير محدد'),
        ];
    }

    private function applyFilters($query, array $filters): void
    {
        if (isset($filters['user_id']) && $filters['user_id'] !== '') {
            $query->where('causer_id', $filters['user_id']);
        }

        if (isset($filters['log_name']) && $filters['log_name'] !== '') {
            $query->where('log_name', $filters['log_name']);
        }

        $from = $filters['date_from'] ?? $filters['created_from'] ?? null;
        if (! empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }

        $to = $filters['date_to'] ?? $filters['created_to'] ?? null;
        if (! empty($to)) {
            $query->whereDate('created_at', '<=', $to);
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

        return '<a href="'.$showUrl.'" title="عرض التفاصيل" class="btn btn-icon btn-primary">
                <i class="ti ti-eye"></i>
            </a>';
    }
}
