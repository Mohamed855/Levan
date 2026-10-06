<?php

namespace App\Http\Controllers\Tenants\activity_log;

use App\Exports\Tenants\ActivityLog\ActivityLogsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\ActivityLog\ExportActivityLogsRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Traits\ActivityLogPresenterTrait;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

class ActivityLogController extends Controller
{
    use ActivityLogPresenterTrait;

    /*
    |--------------------------------------------------------------------------
    | الحد الأقصى لعدد السجلات القابلة للتصدير دفعة واحدة
    |--------------------------------------------------------------------------
    */
    private const EXPORT_MAX = 20000;

    public function __construct()
    {
        $this->middleware('can:activity_log.view');
    }


    public function index(Request $request): View|JsonResponse
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
                    'created_at'
                ]);

            // تطبيق الفلاتر
            $this->applyFilters($query, $request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($activity) {
                    return '<input type="checkbox" class="row-checkbox" value="' . $activity->id . '">';
                })
                ->addColumn('log_name_formatted', function ($activity) {
                    return '<span class="badge bg-primary">' . $this->activityLogNameLabel($activity->log_name) . '</span>';
                })
                ->addColumn('causer_name', function ($activity) {
                    return $activity->causer ? $activity->causer->name : 'النظام';
                })
                ->addColumn('subject_info', function ($activity) {
                    if ($activity->log_name === 'auth') {
                        $identifier = e(data_get($activity->properties, 'identifier', 'غير معروف'));

                        if ($activity->subject) {
                            return '<span class="badge bg-label-warning me-1">محاولة دخول</span><span class="text-info">' . e($activity->subject->name) . ' (' . $identifier . ')</span>';
                        }

                        return '<span class="badge bg-label-warning me-1">محاولة دخول</span><span class="text-muted">' . $identifier . '</span>';
                    }

                    if (!$activity->subject) {
                        return '<span class="text-muted">محذوف</span>';
                    }

                    $subjectType = class_basename($activity->subject_type);
                    $arabicType = $this->activitySubjectTypeMap()[$subjectType] ?? $subjectType;

                    return '<span class="text-info">' . $arabicType . ' #' . $activity->subject_id . '</span>';
                })

                ->editColumn('created_at', function ($row) {
                    return Carbon::parse($row->created_at)->locale('ar')->diffForHumans();
                })


                ->addColumn('action', function ($activity) {
                    return $this->getActionsColumn($activity);
                })
                ->orderColumn('created_at', fn($query, $order) => $query->orderBy('created_at', $order))
                ->rawColumns(['checkbox', 'log_name_formatted', 'subject_info', 'created_at', 'action'])
                ->make(true);
        }

        // الإحصائيات للعرض
        $statistics = $this->getStatistics();
        $users = User::select('id', 'name')->orderBy('name')->get();

        return view('tenants.activity_logs.index', [
            'users' => $users,
            'statistics' => $statistics
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Activity Details
    |--------------------------------------------------------------------------
    | Display the specified activity log details.
    */
    public function show($id): View
    {
        $activity = Activity::with('causer', 'subject')->findOrFail($id);

        // تحضير البيانات للعرض
        $activityData = $this->prepareActivityData($activity);

        return view('tenants.activity_logs.show', [
            'activity' => $activity,
            'activityData' => $activityData
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تحضير بيانات النشاط للعرض
    |--------------------------------------------------------------------------
    */
    private function prepareActivityData($activity): array
    {
        $subjectType = class_basename($activity->subject_type ?? '');
        $arabicSubjectType = $this->activitySubjectTypeMap()[$subjectType] ?? $subjectType;

        $eventMap = [
            'created' => 'إنشاء',
            'updated' => 'تحديث',
            'deleted' => 'حذف',
            'restored' => 'استعادة'
        ];
        $arabicEvent = $eventMap[$activity->event] ?? ($activity->event ?? 'غير محدد');

        $fieldTranslations = [
            'name' => 'الاسم',
            'email' => 'البريد الإلكتروني',
            'phone' => 'الهاتف',
            'position' => 'المنصب',
            'status' => 'الحالة',
            'logo_path' => 'الشعار',
            'company_id' => 'الشركة',
            'contract_number' => 'رقم العقد',
            'start_date' => 'تاريخ البداية',
            'duration_months' => 'المدة بالأشهر',
            'end_date' => 'تاريخ النهاية',
            'auto_renewal' => 'التجديد التلقائي',
            'commission_type' => 'نوع العمولة',
            'notes' => 'الملاحظات',
            'created_at' => 'تاريخ الإنشاء',
            'updated_at' => 'تاريخ التحديث',
            'due_date'             => 'تاريخ الاستحقاق',
            'due_amount'           => 'مبلغ الاستحقاق',
            'contact_method_id'    => 'طريقة التواصل',
            'contact_result_id'    => 'نتيجة الاتصال',
            'customer_status_id'   => 'حالة العميل',
            'next_contact_datetime' => 'موعد التواصل القادم',
            'contacted_person'     => 'الشخص الذي تم التواصل معه',
            'settlement_amount'    => 'مبلغ التسوية',
            'settlement_type'      => 'نوع التسوية',
            'settlement_date'      => 'تاريخ التسوية',
            'phone'                => 'رقم الهاتف',
            'is_primary'           => 'رقم أساسي؟',

        ];

        return [
            'subject_type_arabic' => $arabicSubjectType,
            'event_arabic' => $arabicEvent,
            'log_name_arabic' => $this->activityLogNameLabel($activity->log_name),
            'field_translations' => $fieldTranslations,
            'subject_display' => $this->activitySubjectDisplay($activity),
            'user_name' => $activity->causer->name ?? 'النظام',
            'formatted_date' => $activity->created_at ? $activity->created_at->format('Y-m-d H:i:s') : 'غير محدد',
            'connection_info' => $this->getActivityConnectionInfo($activity),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر على الاستعلام
    |--------------------------------------------------------------------------
    */
    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('user_id')) {
            $query->where('causer_id', $request->user_id);
        }

        if ($request->filled('log_name')) {
            $query->where('log_name', $request->log_name);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $search = $request->input('search.value', $request->input('search'));
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%");
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    |  الحصول على الإحصائيات
    |--------------------------------------------------------------------------
    */
    private function getStatistics(): array
    {
        return [
            'total' => Activity::count(),
            'today' => Activity::whereDate('created_at', today())->count(),
            'this_week' => Activity::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek()
            ])->count(),
            'this_month' => Activity::whereMonth('created_at', now()->month)->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | عمود الإجراءات
    |--------------------------------------------------------------------------
    */
    private function getActionsColumn(Activity $activity): string
    {
        $showUrl = tenant_route_name('activity-logs.show', $activity->id);

        return '<a href="' . $showUrl . '" title="عرض التفاصيل" class="btn btn-icon btn-label-primary">
                <i class="ti ti-logs"></i>
            </a>';
    }

    /*
    |--------------------------------------------------------------------------
    | جلب معرفات كل السجلات المطابقة للفلاتر الحالية (تحديد الكل)
    |--------------------------------------------------------------------------
    */
    public function getAllIds(Request $request): JsonResponse
    {
        $query = Activity::query();
        $this->applyFilters($query, $request);

        $total = (clone $query)->count();
        $ids = $query->orderByDesc('created_at')->limit(self::EXPORT_MAX)->pluck('id');

        return response()->json([
            'success' => true,
            'ids' => $ids,
            'total' => $total,
            'truncated' => $total > self::EXPORT_MAX,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير السجلات المحددة إلى ملف Excel (يُخزَّن الملف ويُعاد اسمه للتنزيل)
    |--------------------------------------------------------------------------
    */
    public function export(ExportActivityLogsRequest $request): JsonResponse
    {
        $ids = collect(json_decode($request->validated('ids'), true) ?: [])
            ->filter()
            ->take(self::EXPORT_MAX)
            ->values();

        if ($ids->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'لا توجد سجلات محددة للتصدير',
            ], 422);
        }

        $activities = Activity::with('causer', 'subject')
            ->whereIn('id', $ids)
            ->orderByDesc('created_at')
            ->get();

        $fileName = 'سجل-النشاطات-' . now()->format('Y-m-d-His') . '.xlsx';

        Excel::store(new ActivityLogsExport($activities), 'exports/' . $fileName, 'tenant_public');

        return response()->json([
            'success' => true,
            'file_name' => $fileName,
            'count' => $activities->count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | تحميل ملف التصدير المُجهَّز مسبقًا (يُحذف بعد الإرسال)
    |--------------------------------------------------------------------------
    */
    public function downloadExportFile(string $file): BinaryFileResponse|RedirectResponse
    {
        $disk = Storage::disk('tenant_public');
        $relative = 'exports/' . basename($file);

        if (!$disk->exists($relative)) {
            return redirect()->back()->with('error', 'الملف غير موجود أو تم حذفه.');
        }

        return response()->download($disk->path($relative))->deleteFileAfterSend(true);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض الملفات المصدرة (الأرشيف)
    |--------------------------------------------------------------------------
    */
    public function archives(): View
    {
        $disk = Storage::disk('tenant_public');
        $files = $disk->files('activity-logs');

        $archives = collect($files)->map(function ($file) use ($disk) {
            $lastModified = $disk->lastModified($file);

            return [
                'name' => basename($file),
                'path' => $file,
                'size' => $this->formatBytes($disk->size($file)),
                'date' => Carbon::createFromTimestamp($lastModified)->format('Y-m-d H:i'),
                'month' => Carbon::createFromTimestamp($lastModified)->format('F Y'),
            ];
        })->sortByDesc('date')->values();

        return view('tenants.activity_logs.archives', compact('archives'));
    }

    /*
    |--------------------------------------------------------------------------
    | تحميل ملف مصدر
    |--------------------------------------------------------------------------
    */
    public function downloadFile($file): StreamedResponse
    {
        $filePath = 'activity-logs/' . $file;
        $disk = Storage::disk('tenant_public');

        if (!$disk->exists($filePath)) {
            abort(404, 'الملف غير موجود');
        }

        return $disk->download($filePath);
    }

    /*
    |--------------------------------------------------------------------------
    | تحويل حجم الملف إلى صيغة مقروءة
    |--------------------------------------------------------------------------
    */
    private function formatBytes($bytes, $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
