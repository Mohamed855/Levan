<?php

namespace App\DataTables\Tenants\FinancialCollection\CollectionProcess;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\FinancialCollection\CollectionProcess\CollectionProcessSession;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class CollectionSessionsDataTable extends AbstractDataTable
{
    protected function getTableId()
    {
        return 'collection-sessions-table';
    }

    protected function resource()
    {
        return CollectionProcessSession::query()
            ->select([
                'collection_process_sessions.id',
                'collection_process_sessions.user_id',
                'collection_process_sessions.date',
                'collection_process_sessions.is_active',
                'collection_process_sessions.first_entry_time',
                'collection_process_sessions.total_work_minutes',
                'collection_process_sessions.total_follows',
                'collection_process_sessions.total_activities',
            ])
            ->with([
                'user:id,name,employee_number,image',
                'workPeriods' => function ($q) {
                    $q->select('id', 'session_id', 'entry_time', 'exit_time', 'updated_at')->orderBy('entry_time', 'asc');
                },
            ]);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'DT_RowIndex',      'name' => 'DT_RowIndex',            'title' => '#',              'orderable' => false, 'searchable' => false, 'width' => '6%'],
            ['data' => 'user_name',        'name' => 'user.name',              'title' => 'الموظف'],
            ['data' => 'employee_number',  'name' => 'user.employee_number',   'title' => 'الرقم الوظيفي',   'className' => 'text-center'],
            ['data' => 'status_badge',     'name' => 'is_active',              'title' => 'الحالة',          'className' => 'text-center'],
            ['data' => 'first_entry',      'name' => 'first_entry_time',       'title' => 'أول دخول',        'className' => 'text-center'],
            ['data' => 'activity_text',    'name' => 'activity_text',          'title' => 'تاريخ آخر نشاط',        'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'working_hours',    'name' => 'total_work_minutes',     'title' => 'ساعات العمل',     'className' => 'text-center'],
            ['data' => 'total_follows',    'name' => 'total_follows',          'title' => 'المتابعات',       'className' => 'text-center'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('user_name', function ($session) {
                $name = $session->user->name ?? 'غير محدد';
                $userId = $session->user_id;
                $profileUrl = tenant_route_name('users.show', $userId);

                return '<a href="'.$profileUrl.'" class="text-decoration-none text-dark fw-semibold">'
                    .e($name)
                    .'</a>';
            })

            ->addColumn('employee_number', function ($session) {
                return e($session->user->employee_number ?? 'غير محدد');
            })

            ->addColumn('status_badge', function ($session) {
                if ($session->is_active) {
                    return '<span class="badge bg-label-success">متواجد الآن</span>';
                }

                return '<span class="badge bg-secondary">غير متواجد</span>';
            })

            // أول دخول HH:mm أو "-"
            ->addColumn('first_entry', function ($session) {
                return $session->first_entry_time ? Carbon::parse($session->first_entry_time)->format('H:i') : '-';
            })

            // آخر نشاط: وقت آخر متابعة محفوظة (updated_at) إذا كان داخل الفترة المفتوحة، أو "خروج HH:mm" لآخر خروج، أو "-"
            ->addColumn('activity_text', function ($session) {
                if ($session->is_active) {
                    $open = $session->workPeriods->whereNull('exit_time')->first();
                    if ($open) {
                        return Carbon::parse($open->updated_at)->format('H:i');
                    }

                    return '-';
                }

                $last = $session->workPeriods->sortByDesc('exit_time')->first();
                if ($last && $last->exit_time) {
                    return 'خروج '.Carbon::parse($last->exit_time)->format('H:i');
                }

                return '-';
            })

            // ساعات العمل بصيغة HH:MM (مع احتساب الفترة المفتوحة إذا كان نشطًا)
            ->addColumn('working_hours', function ($session) {
                $total = (int) $session->total_work_minutes;
                if ($session->is_active) {
                    $open = $session->workPeriods->whereNull('exit_time')->first();
                    if ($open) {
                        $total += Carbon::parse($open->entry_time)->diffInMinutes(now());
                    }
                }
                $hours = floor($total / 60);
                $minutes = $total % 60;

                return sprintf('%02d:%02d', $hours, $minutes);
            })

            ->editColumn('total_follows', fn ($s) => (int) $s->total_follows)

            ->orderColumn('user_name', fn ($q, $o) => $q->join('users as u', 'u.id', '=', 'collection_process_sessions.user_id')->orderBy('u.name', $o))
            ->orderColumn('employee_number', fn ($q, $o) => $q->join('users as u2', 'u2.id', '=', 'collection_process_sessions.user_id')->orderBy('u2.employee_number', $o))
            ->orderColumn('first_entry', fn ($q, $o) => $q->orderBy('collection_process_sessions.first_entry_time', $o))
            ->orderColumn('working_hours', fn ($q, $o) => $q->orderBy('collection_process_sessions.total_work_minutes', $o));
    }

    protected function applyCustomFilters($query)
    {
        $request = request();
        $date = $request->input('date', today()->toDateString());
        $status = $request->input('status');

        $query->whereDate('collection_process_sessions.date', $date);

        // تطبيق فلترة الفريق/الصلاحيات تلقائياً
        $accessibleUserIds = $this->getAccessibleUserIds();
        if ($accessibleUserIds !== null) {
            $query->whereIn('collection_process_sessions.user_id', $accessibleUserIds);
        }

        if ($status === 'active') {
            $query->where('collection_process_sessions.is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('collection_process_sessions.is_active', false);
        }

        return $query;
    }

    protected function rawColumns(): array
    {
        return ['user_name', 'status_badge'];
    }

    protected function getSearchableColumns(): array
    {
        return ['user.name', 'user.employee_number'];
    }

    protected function parameters(): array
    {
        $params = parent::parameters();
        // المتواجدون الآن أولاً، ثم الأكثر ساعات عمل
        $params['order'] = [[3, 'desc'], [6, 'desc']];

        return $params;
    }

    /*
    |--------------------------------------------------------------------------
    | نطاق المستخدمين المسموح برؤيتهم حسب صلاحيات المستخدم الحالي
    |--------------------------------------------------------------------------
    */
    private function getAccessibleUserIds(): ?array
    {
        $user = Auth::user();

        if ($user->canManageTeam()) {
            return $user->teamMemberIds();
        }

        if ($user->can('dashboard.view')) {
            return null;
        }

        return [$user->id];
    }
}
