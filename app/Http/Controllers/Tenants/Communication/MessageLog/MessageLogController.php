<?php

namespace App\Http\Controllers\Tenants\Communication\MessageLog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Communication\FilterMessageLogRequest;
use App\Models\Tenants\Communication\MessageLog\MessageLog;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Models\Tenants\UserMangment\User\User;
use App\Services\Tenants\Communication\MessageLog\MessageLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class MessageLogController extends Controller
{
    protected MessageLogService $messageLogService;

    public function __construct(MessageLogService $messageLogService)
    {
        $this->messageLogService = $messageLogService;
        $this->authorizeResource(MessageLog::class, 'messageLog');

        $this->middleware('can:message_logs.view')->except(['destroy', 'show']);
        $this->middleware('can:message_logs.show')->only(['show']);
        $this->middleware('can:message_logs.delete')->only(['destroy']);
    }

    /**
     * عرض قائمة سجل الرسائل
     */
    public function index(FilterMessageLogRequest $request): View|JsonResponse
    {
        if ($request->ajax()) {
            $query = MessageLog::with(['sender:id,name', 'template:id,name'])
                ->select([
                    'id',
                    'sender_id',
                    'template_id',
                    'template_name',
                    'target_model',
                    'message_content',
                    'message_type',
                    'platform',
                    'recipients_count',
                    'status',
                    'sent_count',
                    'failed_count',
                    'created_at',
                    'sent_at',
                    'failed_at',
                ]);

            // تطبيق الفلاتر
            $this->applyFilters($query, $request);

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('checkbox', function ($log) {
                    return '<input type="checkbox" class="row-checkbox" value="'.$log->id.'">';
                })
                ->addColumn('sender_name', function ($log) {
                    return $log->sender->name ?? 'النظام';
                })
                ->addColumn('message_preview', function ($log) {
                    return '<div class="text-truncate" style="max-width: 200px;" title="'.e($log->message_content).'">'
                        .e(\Str::limit(strip_tags($log->message_content), 50)).'</div>';
                })
                ->addColumn('recipients_info', function ($log) {
                    $html = '<div class="text-center">';
                    $html .= '<span class="badge bg-primary">'.$log->recipients_count.'</span>';
                    if ($log->target_model) {
                        $targetModels = [
                            'customers' => 'العملاء',
                            'users' => 'المستخدمين',
                            'employees' => 'الموظفين',
                        ];
                        $modelName = $targetModels[$log->target_model] ?? $log->target_model;
                        $html .= '<br><small class="text-muted">'.$modelName.'</small>';
                    }
                    $html .= '</div>';

                    return $html;
                })
                ->addColumn('status_badge', function ($log) {
                    return '<span class="badge bg-'.$log->status_color.'">'.$log->status_label.'</span>';
                })
                ->addColumn('success_stats', function ($log) {
                    if ($log->status === 'pending' || $log->status === 'processing') {
                        return '<span class="text-muted">-</span>';
                    }

                    $html = '<div class="text-center">';
                    if ($log->sent_count > 0) {
                        $html .= '<div class="text-success"><i class="ti ti-check"></i> '.$log->sent_count.'</div>';
                    }
                    if ($log->failed_count > 0) {
                        $html .= '<div class="text-danger"><i class="ti ti-x"></i> '.$log->failed_count.'</div>';
                    }
                    $html .= '</div>';

                    return $html;
                })
                ->addColumn('timing_info', function ($log) {
                    $html = '<div class="small">';
                    $html .= '<div class="text-muted">إنشاء: '.$log->created_at->format('m-d H:i').'</div>';
                    if ($log->sent_at) {
                        $html .= '<div class="text-success">إرسال: '.$log->sent_at->format('m-d H:i').'</div>';
                    }
                    if ($log->failed_at) {
                        $html .= '<div class="text-danger">فشل: '.$log->failed_at->format('m-d H:i').'</div>';
                    }
                    $html .= '</div>';

                    return $html;
                })
                ->addColumn('action', function ($log) {
                    return $this->getActionsColumn($log);
                })
                ->orderColumn('created_at', fn ($query, $order) => $query->orderBy('created_at', $order))
                ->orderColumn('status', fn ($query, $order) => $query->orderBy('status', $order))
                ->filterColumn('message_content', fn ($query, $keyword) => $query->where('message_content', 'like', "%{$keyword}%"))
                ->rawColumns(['checkbox', 'message_preview', 'recipients_info', 'status_badge', 'success_stats', 'timing_info', 'action'])
                ->make(true);
        }

        // الإحصائيات للعرض
        $statistics = $this->getStatistics();
        $filterData = $this->getFilterData();

        return view('tenants.communication.message_logs.index', [
            'statistics' => $statistics,
            'filterData' => $filterData,
        ]);
    }

    /**
     * عرض تفاصيل رسالة محددة
     */
    public function show(MessageLog $messageLog): View
    {
        $messageLog->load(['sender:id,name', 'template:id,name']);

        return view('tenants.communication.message_logs.show', [
            'messageLog' => $messageLog,
        ]);
    }

    /**
     * حذف رسالة من السجل
     */
    public function destroy(MessageLog $messageLog): RedirectResponse
    {
        try {
            $this->messageLogService->delete($messageLog);

            return redirect()->back()->with('success', 'تم حذف الرسالة من السجل بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الرسالة');
        }
    }

    /**
     * إحصائيات لوحة التحكم
     */
    public function dashboardStats(): JsonResponse
    {
        $stats = $this->getStatistics();

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * بيانات الرسوم البيانية
     */
    public function chartData(Request $request): JsonResponse
    {
        $period = $request->get('period', 'week');
        $chartData = $this->messageLogService->getStatsbyDate($period);

        return response()->json([
            'success' => true,
            'chart_data' => $chartData,
        ]);
    }

    /**
     * الرسائل الفاشلة
     */
    public function failedMessages(): JsonResponse
    {
        $failedMessages = MessageLog::failed()
            ->with(['sender:id,name', 'template:id,name'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(function ($message) {
                return [
                    'id' => $message->id,
                    'message_preview' => \Str::limit($message->message_content, 100),
                    'recipients_count' => $message->recipients_count,
                    'status_message' => $message->status_message,
                    'sender_name' => $message->sender->name ?? 'النظام',
                    'failed_at' => $message->failed_at?->format('Y-m-d H:i:s'),
                ];
            });

        return response()->json([
            'success' => true,
            'failed_messages' => $failedMessages,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * تطبيق الفلاتر على الاستعلام
     */
    private function applyFilters($query, FilterMessageLogRequest $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('message_type')) {
            $query->where('message_type', $request->message_type);
        }

        if ($request->filled('target_model')) {
            $query->where('target_model', $request->target_model);
        }

        if ($request->filled('sender_id')) {
            $query->where('sender_id', $request->sender_id);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $search = $request->input('search.value');
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('message_content', 'like', "%{$search}%")
                    ->orWhere('template_name', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }
    }

    /**
     * الحصول على الإحصائيات
     */
    private function getStatistics(): array
    {
        return [
            'total' => MessageLog::count(),
            'sent' => MessageLog::sent()->count(),
            'failed' => MessageLog::failed()->count(),
            'pending' => MessageLog::pending()->count(),
            'processing' => MessageLog::where('status', 'processing')->count(),
            'today' => MessageLog::whereDate('created_at', today())->count(),
            'this_week' => MessageLog::whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'success_rate' => $this->calculateSuccessRate(),
        ];
    }

    /**
     * بيانات الفلاتر
     */
    private function getFilterData(): array
    {
        return [
            'senders' => User::select('id', 'name')
                ->orderBy('name')
                ->get(),
            'templates' => MessageTemplate::select('id', 'name')
                ->orderBy('name')
                ->get(),
            'target_models' => MessageLog::select('target_model')
                ->distinct()
                ->whereNotNull('target_model')
                ->pluck('target_model')
                ->toArray(),
        ];
    }

    /**
     * حساب معدل النجاح
     */
    private function calculateSuccessRate(): float
    {
        $total = MessageLog::count();
        $sent = MessageLog::sent()->count();

        return $total > 0 ? round(($sent / $total) * 100, 2) : 0;
    }

    /**
     * عمود الإجراءات
     */
    private function getActionsColumn(MessageLog $log): string
    {
        $actions = '';

        // زر العرض
        $actions .= '<a href="'.tenant_route_name('communication.message-logs.show', $log->id).'" title="عرض" class="btn btn-icon btn-label-primary me-1">
                    <i class="ti ti-eye"></i>
                 </a>';

        // زر الحذف - نفس طريقة الشركات
        $formId = 'delete-form-'.$log->id;
        $deleteUrl = tenant_route_name('communication.message-logs.destroy', $log->id);

        $actions .= '
        <button title="حذف الرسالة" type="button" onclick="confirmDelete('.$log->id.')" class="btn btn-icon btn-label-primary">
            <i class="ti ti-trash"></i>
        </button>
        <form id="'.$formId.'" action="'.$deleteUrl.'" method="POST" style="display: none;">
            '.csrf_field().'
            '.method_field('DELETE').'
        </form>
    ';

        return $actions;
    }
}
