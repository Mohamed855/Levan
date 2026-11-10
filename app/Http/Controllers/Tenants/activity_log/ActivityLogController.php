<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\activity_log;

use App\DataTables\Tenants\ActivityLog\TenantActivityLogDataTable;
use App\Exports\Tenants\ActivityLog\ActivityLogsExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\ActivityLog\ActivityLogIndexRequest;
use App\Http\Requests\Tenants\ActivityLog\DownloadActivityLogExportRequest;
use App\Http\Requests\Tenants\ActivityLog\ExportActivityLogsRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Shared\ActivityLog\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\Activitylog\Models\Activity;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivityLogController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | الحد الأقصى لعدد السجلات القابلة للتصدير دفعة واحدة
    |--------------------------------------------------------------------------
    */
    private const EXPORT_MAX = 20000;

    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
        $this->middleware('can:activity_log.view');
    }

    public function index(ActivityLogIndexRequest $request, TenantActivityLogDataTable $dataTable): View|JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        if ($request->ajax() || $request->wantsJson() || $request->has('draw')) {
            return $dataTable->render('tenant.activity-logs.index');
        }

        $statistics = $this->activityLogService->getStatistics();
        $users = User::select('id', 'name')->orderBy('name')->get();

        $filterOptions = [
            'users' => $users->map(fn (User $user): array => [
                'value' => $user->id,
                'label' => $user->name,
            ]),
            'log_names' => $this->activityLogService->getLogNameFilterOptions(),
        ];

        return $dataTable->render('tenant.activity-logs.index', [
            'users' => $users,
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
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
        $activity = Activity::with('causer')->findOrFail($id);
        $this->authorize('view', $activity);

        return view('tenant.activity-logs.show', $this->activityLogService->getShowViewData($activity));
    }

    /*
    |--------------------------------------------------------------------------
    | جلب معرفات كل السجلات المطابقة للفلاتر الحالية (تحديد الكل)
    |--------------------------------------------------------------------------
    */
    public function getAllIds(ActivityLogIndexRequest $request): JsonResponse
    {
        $this->authorize('viewAny', Activity::class);

        $query = Activity::query();
        $this->activityLogService->applyFilters($query, $request->validated(), true);

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
        $this->authorize('viewAny', Activity::class);

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

        $fileName = 'activity-logs-'.now()->format('Ymd-His').'.xlsx';

        Excel::store(new ActivityLogsExport($activities), 'exports/'.$fileName, 'tenant_public');
        Cache::put(
            DownloadActivityLogExportRequest::ownerKey($fileName),
            $request->user('tenant')->getKey(),
            now()->addHours(2),
        );

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
    public function downloadExportFile(DownloadActivityLogExportRequest $request, string $file): BinaryFileResponse|RedirectResponse
    {
        $disk = Storage::disk('tenant_public');
        $fileName = basename($file);
        $exportPath = 'exports/'.$fileName;
        $archivePath = 'activity-logs/'.$fileName;

        if ($disk->exists($exportPath)) {
            return response()->download($disk->path($exportPath))->deleteFileAfterSend(true);
        }

        if ($disk->exists($archivePath)) {
            return response()->download($disk->path($archivePath));
        }

        return redirect()->back()->with('error', 'الملف غير موجود أو تم حذفه.');
    }

    /*
    |--------------------------------------------------------------------------
    | عرض الملفات المصدرة (الأرشيف)
    |--------------------------------------------------------------------------
    */
    public function archives(): View
    {
        $this->authorize('viewAny', Activity::class);

        return view('tenant.activity-logs.archives', $this->activityLogService->getTenantArchivesData());
    }
}
