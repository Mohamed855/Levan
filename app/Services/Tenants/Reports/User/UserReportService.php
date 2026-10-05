<?php

namespace App\Services\Tenants\Reports\User;

use App\Models\Tenants\UserMangment\User\User;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\GeneralSetting\Branch\SettingsBranch;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Yajra\DataTables\Facades\DataTables;
use Carbon\Carbon;

class UserReportService
{
    /*
    |--------------------------------------------------------------------------
    | الحصول على إحصائيات تقرير المستخدمين
    |--------------------------------------------------------------------------
    */
    public function getReportStatistics(): array
    {
        $baseQuery = User::query()->where('status', 'active');

        return [
            // الإحصائيات الأساسية
            'total_users' => (clone $baseQuery)->count(),
            'active_collectors' => $this->getActiveCollectors(),
            'top_performer_this_month' => $this->getTopPerformerThisMonth(),
            'total_collections_this_month' => $this->getTotalCollectionsThisMonth(),

            // إحصائيات الأداء
            'best_performer_amount' => $this->getBestPerformerAmount(),
            'average_collections_per_user' => $this->getAverageCollectionsPerUser(),
            'users_with_collections_today' => $this->getUsersWithCollectionsToday(),
            'inactive_users_this_month' => $this->getInactiveUsersThisMonth(),

            // إحصائيات حسب الفرع
            'collections_by_branch' => $this->getCollectionsByBranch(),
            'users_performance_chart' => $this->getUsersPerformanceChart(),
            'monthly_performance_trend' => $this->getMonthlyPerformanceTrend(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على بيانات النموذج
    |--------------------------------------------------------------------------
    */
    public function getFormData(): array
    {
        return [
            'branches' => SettingsBranch::where('status', 'active')
                ->select('id', 'name')->orderBy('name')->get(),
            'users' => User::where('status', 'active')
                ->select('id', 'name', 'branch_id')->orderBy('name')->get(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على بيانات تقرير المستخدمين للـ DataTable
    |--------------------------------------------------------------------------
    */
    public function getUsersReportData(Request $request): JsonResponse
    {
        $query = User::with([
            'branch:id,name'
        ])->select([
            'id',
            'name',
            'email',
            'phone',
            'branch_id',
            'status',
            'created_at'
        ]);

        $this->applyFilters($query, $request);

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('user_info', function ($user) {
                return '<div class="fw-medium">' . e($user->name) . '</div>' .
                    '<small class="text-muted">' . e($user->email) . '</small>';
            })
            ->addColumn('contact_info', function ($user) {
                return '<span class="text-primary">' . e($user->phone ?: 'لا يوجد') . '</span>';
            })
            ->addColumn('branch_info', function ($user) {
                return '<span class="fw-medium">' . e($user->branch->name ?? 'غير محدد') . '</span>';
            })
            ->addColumn('performance_info', function ($user) {
                $performance = $this->getUserPerformance($user->id);
                return '<div class="performance-stats">' .
                    '<div class="fw-bold text-success">' . number_format($performance['total_amount'], 0) . ' ريال</div>' .
                    '<small class="text-muted">' . $performance['total_collections'] . ' تحصيل</small>' .
                    '</div>';
            })
            ->addColumn('monthly_performance', function ($user) {
                $monthly = $this->getUserMonthlyPerformance($user->id);
                return '<div class="monthly-stats">' .
                    '<div class="fw-bold text-primary">' . number_format($monthly['amount'], 0) . ' ريال</div>' .
                    '<small class="text-muted">' . $monthly['collections'] . ' تحصيل هذا الشهر</small>' .
                    '</div>';
            })
            ->addColumn('last_activity', function ($user) {
                $lastCollection = Collection::where('added_by', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->first();

                if ($lastCollection) {
                    $days = $lastCollection->created_at->diffInDays(now());
                    $color = $days <= 7 ? 'success' : ($days <= 30 ? 'warning' : 'danger');
                    return '<span class="badge bg-label-' . $color . '">' .
                        $lastCollection->created_at->locale('ar')->diffForHumans() . '</span>';
                }

                return '<span class="badge bg-secondary">لا يوجد نشاط</span>';
            })
            ->addColumn('status_info', function ($user) {
                $statusClass = $user->status === 'active' ? 'bg-label-success' : 'bg-label-danger';
                $statusLabel = $user->status === 'active' ? 'نشط' : 'غير نشط';
                return '<span class="badge ' . $statusClass . '">' . $statusLabel . '</span>';
            })
            ->addColumn('join_date', function ($user) {
                return $user->created_at->locale('ar')->translatedFormat('d F Y');
            })
            ->filterColumn('user_info', function ($query, $keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%");
            })
            ->rawColumns(['user_info', 'contact_info', 'branch_info', 'performance_info', 'monthly_performance', 'last_activity', 'status_info'])
            ->make(true);
    }

    /*
    |--------------------------------------------------------------------------
    | تطبيق الفلاتر على الاستعلام
    |--------------------------------------------------------------------------
    */
    private function applyFilters($query, Request $request): void
    {
        // فلتر الفرع
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // فلتر الحالة
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // فلتر تاريخ الانضمام
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // البحث العام
        $searchValue = $request->input('search.value');
        if (!empty($searchValue) && is_string($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('name', 'like', "%{$searchValue}%")
                    ->orWhere('email', 'like', "%{$searchValue}%")
                    ->orWhere('phone', 'like', "%{$searchValue}%");
            });
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تصدير تقرير المستخدمين إلى PDF
    |--------------------------------------------------------------------------
    */
    public function exportUsersReportToPDF(array $filters): BinaryFileResponse
    {
        try {
            Log::info('== بدء exportUsersReportToPDF ==');

            // جلب البيانات مع العلاقات
            $query = User::with(['branch:id,name']);
            $this->applyFiltersForExport($query, $filters);
            $users = $query->orderBy('name')->get();

            // حساب الإحصائيات
            $totalUsers = $users->count();
            $activeUsers = $users->where('status', 'active')->count();
            $inactiveUsers = $users->where('status', 'inactive')->count();

            // تحديد النطاق الزمني للتقرير
            $dateFrom = $filters['date_from'] ?? null;
            $dateTo = $filters['date_to'] ?? null;
            $dateRange = $this->getDateRangeText($dateFrom, $dateTo);

            // إعداد mPDF
            $mpdf = new \Mpdf\Mpdf([
                'mode'              => 'utf-8',
                'format'            => 'A4',
                'orientation'       => 'P',
                'margin_left'       => 0,
                'margin_right'      => 0,
                'margin_top'        => 0,
                'margin_bottom'     => 10,
                'margin_header'     => 0,
                'margin_footer'     => 0,
                'default_font'      => 'almarai',
                'fontDir'           => array_merge(
                    (new \Mpdf\Config\ConfigVariables())->getDefaults()['fontDir'],
                    [public_path('static/fonts/almarai')]
                ),
                'fontdata'          => array_merge(
                    (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'],
                    ['almarai' => [
                        'R' => 'Almarai-Regular.ttf',
                        'B' => 'Almarai-Bold.ttf',
                        'useOTL'    => 0xFF,
                        'useKashida' => 75,
                    ]]
                ),
                'default_font_size' => 12,
                'tempDir'           => storage_path('app/public/temp'),
            ]);

            $mpdf->SetDirectionality('rtl');
            $mpdf->SetHTMLHeader('');
            $mpdf->SetTopMargin(0);

            // إنشاء HTML
            $html = $this->generateUsersReportHTML(
                $users,
                $totalUsers,
                $activeUsers,
                $inactiveUsers,
                $dateRange,
                $this->getAppliedFiltersText($filters)
            );

            // كتابة CSS
            $mpdf->WriteHTML($this->getUsersReportCSS(), \Mpdf\HTMLParserMode::HEADER_CSS);

            // كتابة HTML
            $mpdf->WriteHTML($html);

            // تحديد اسم الملف
            $fileName = 'تقرير_المستخدمين_' . now()->format('Y_m_d_H_i_s') . '.pdf';

            // التأكد من وجود مجلد temp
            $tempDir = storage_path('app/public/temp');
            if (!file_exists($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // حفظ الملف مؤقتاً
            $tempPath = $tempDir . '/' . $fileName;
            $mpdf->Output($tempPath, 'F');

            // إرجاع BinaryFileResponse
            return response()->download($tempPath)->deleteFileAfterSend();
        } catch (\Throwable $e) {
            Log::error('exportUsersReportToPDF error', [
                'msg' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | دوال مساعدة للإحصائيات
    |--------------------------------------------------------------------------
    */

    private function getActiveCollectors(): int
    {
        return Collection::where('created_at', '>=', now()->startOfMonth())
            ->distinct('added_by')
            ->count('added_by');
    }

    private function getTopPerformerThisMonth(): array
    {
        $topPerformer = Collection::selectRaw('added_by, SUM(collected_amount) as total_amount')
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->startOfMonth())
            ->with('addedBy:id,name')
            ->groupBy('added_by')
            ->orderByDesc('total_amount')
            ->first();

        return [
            'name' => $topPerformer->addedBy->name ?? 'لا يوجد',
            'amount' => $topPerformer->total_amount ?? 0
        ];
    }

    private function getTotalCollectionsThisMonth(): float
    {
        return Collection::where('status', 'approved')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('collected_amount');
    }

    private function getBestPerformerAmount(): float
    {
        return Collection::selectRaw('SUM(collected_amount) as total')
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->startOfMonth())
            ->groupBy('added_by')
            ->orderByDesc('total')
            ->value('total') ?? 0;
    }

    private function getAverageCollectionsPerUser(): float
    {
        $activeUsers = User::where('status', 'active')->count();
        $totalCollections = Collection::where('status', 'approved')
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('collected_amount');

        return $activeUsers > 0 ? $totalCollections / $activeUsers : 0;
    }

    private function getUsersWithCollectionsToday(): int
    {
        return Collection::whereDate('created_at', today())
            ->distinct('added_by')
            ->count('added_by');
    }

    private function getInactiveUsersThisMonth(): int
    {
        $activeCollectors = Collection::where('created_at', '>=', now()->startOfMonth())
            ->distinct('added_by')
            ->pluck('added_by')
            ->toArray();

        return User::where('status', 'active')
            ->whereNotIn('id', $activeCollectors)
            ->count();
    }

    private function getCollectionsByBranch(): array
    {
        return Collection::join('users', 'collections.added_by', '=', 'users.id')
            ->join('settings_branches', 'users.branch_id', '=', 'settings_branches.id')
            ->select('settings_branches.name', DB::raw('SUM(collected_amount) as amount'))
            ->where('collections.status', 'approved')
            ->where('collections.created_at', '>=', now()->startOfMonth())
            ->groupBy('settings_branches.name')
            ->orderByDesc('amount')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }

    private function getUsersPerformanceChart(): array
    {
        return Collection::selectRaw('added_by, SUM(collected_amount) as amount')
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->startOfMonth())
            ->with('addedBy:id,name')
            ->groupBy('added_by')
            ->orderByDesc('amount')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->addedBy->name ?? 'غير محدد',
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }

    private function getMonthlyPerformanceTrend(): array
    {
        return Collection::selectRaw('DATE(created_at) as date, SUM(collected_amount) as amount')
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->startOfMonth())
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('m/d'),
                    'amount' => $item->amount
                ];
            })
            ->toArray();
    }

    private function getUserPerformance(int $userId): array
    {
        $collections = Collection::where('added_by', $userId)
            ->where('status', 'approved');

        return [
            'total_collections' => (clone $collections)->count(),
            'total_amount' => (clone $collections)->sum('collected_amount')
        ];
    }

    private function getUserMonthlyPerformance(int $userId): array
    {
        $collections = Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->startOfMonth());

        return [
            'collections' => (clone $collections)->count(),
            'amount' => (clone $collections)->sum('collected_amount')
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | دوال مساعدة للتصدير
    |--------------------------------------------------------------------------
    */

    private function applyFiltersForExport($query, array $filters): void
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) continue;

            switch ($key) {
                case 'branch_id':
                    $query->where('branch_id', $value);
                    break;
                case 'status':
                    $query->where('status', $value);
                    break;
                case 'date_from':
                    $query->whereDate('created_at', '>=', $value);
                    break;
                case 'date_to':
                    $query->whereDate('created_at', '<=', $value);
                    break;
            }
        }
    }

    private function getDateRangeText(?string $dateFrom, ?string $dateTo): string
    {
        if ($dateFrom && $dateTo) {
            return "من {$dateFrom} إلى {$dateTo}";
        } elseif ($dateFrom) {
            return "من {$dateFrom}";
        } elseif ($dateTo) {
            return "حتى {$dateTo}";
        }
        return 'جميع الفترات';
    }

    private function getAppliedFiltersText(array $filters): string
    {
        $appliedFilters = [];

        if (!empty($filters['branch_id'])) {
            $branch = SettingsBranch::find($filters['branch_id']);
            if ($branch) {
                $appliedFilters[] = "الفرع: {$branch->name}";
            }
        }

        if (!empty($filters['status'])) {
            $statusLabel = $filters['status'] === 'active' ? 'نشط' : 'غير نشط';
            $appliedFilters[] = "الحالة: {$statusLabel}";
        }

        return empty($appliedFilters) ? 'لا توجد فلاتر مطبقة' : implode(' | ', $appliedFilters);
    }

    // سيتم إضافة دوال HTML و CSS في artifact منفصل
    private function generateUsersReportHTML($users, $totalUsers, $activeUsers, $inactiveUsers, $dateRange, $appliedFilters): string
    {
        $html = '<div class="content">';

        // رأس التقرير
        $html .= '<div class="report-header">';
        $html .= '<h2>تقرير المستخدمين وأداء التحصيل</h2>';
        $html .= '<div class="header-info">';
        $html .= '<span class="date-range">الفترة: ' . $dateRange . '</span>';
        $html .= '<span class="applied-filters">الفلاتر المطبقة: ' . $appliedFilters . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        // جدول البيانات التفصيلي
        $html .= '<div class="data-section">';
        $html .= '<h3>بيانات المستخدمين وأداء التحصيل</h3>';
        $html .= '<table class="data-table"><thead><tr>';
        $html .= '<th>م</th>';
        $html .= '<th>اسم المستخدم</th>';
        $html .= '<th>البريد الإلكتروني</th>';
        $html .= '<th>الهاتف</th>';
        $html .= '<th>الفرع</th>';
        $html .= '<th>الحالة</th>';
        $html .= '<th>تحصيلات الشهر</th>';
        $html .= '<th>مبلغ الشهر</th>';
        $html .= '<th>إجمالي التحصيلات</th>';
        $html .= '<th>إجمالي المبلغ</th>';
        $html .= '</tr></thead><tbody>';

        if ($users->isEmpty()) {
            $html .= '<tr><td colspan="10" class="no-records">لا توجد بيانات للعرض</td></tr>';
        } else {
            $serialNumber = 1;
            foreach ($users as $user) {
                $performance = $this->getUserPerformance($user->id);
                $monthlyPerformance = $this->getUserMonthlyPerformance($user->id);

                $statusLabel = $user->status === 'active' ? 'نشط' : 'غير نشط';

                $html .= '<tr>';
                $html .= '<td>' . $serialNumber . '</td>';
                $html .= '<td class="text-right">' . e($user->name) . '</td>';
                $html .= '<td class="text-right">' . e($user->email) . '</td>';
                $html .= '<td>' . e($user->phone ?: '—') . '</td>';
                $html .= '<td class="text-right">' . e($user->branch->name ?? '—') . '</td>';
                $html .= '<td>' . $statusLabel . '</td>';
                $html .= '<td class="amount-cell">' . number_format($monthlyPerformance['collections']) . '</td>';
                $html .= '<td class="amount-cell">' . number_format($monthlyPerformance['amount'], 2) . '</td>';
                $html .= '<td class="amount-cell">' . number_format($performance['total_collections']) . '</td>';
                $html .= '<td class="amount-cell">' . number_format($performance['total_amount'], 2) . '</td>';
                $html .= '</tr>';

                $serialNumber++;
            }
        }

        $html .= '</tbody></table>';
        $html .= '</div>';

        // الإحصائيات في الأسفل
        $totalMonthlyAmount = $this->getTotalCollectionsThisMonth();
        $averagePerUser = $this->getAverageCollectionsPerUser();
        $topPerformer = $this->getTopPerformerThisMonth();

        // قسم أول: إحصائيات المستخدمين
        $html .= '<div class="statistics-section">';
        $html .= '<h3>إحصائيات المستخدمين</h3>';
        $html .= '<table class="statistics-table">';
        $html .= '<tr>';
        $html .= '<td class="stat-label">إجمالي المستخدمين</td>';
        $html .= '<td class="stat-label">المستخدمون النشطون</td>';
        $html .= '<td class="stat-label">نسبة النشطين</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td class="stat-value">' . number_format($totalUsers) . '</td>';
        $html .= '<td class="stat-value">' . number_format($activeUsers) . '</td>';
        $html .= '<td class="stat-value">' . ($totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0) . '%</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        // قسم ثاني: إحصائيات الأداء الشهري
        $html .= '<div class="statistics-section">';
        $html .= '<h3>إحصائيات الأداء الشهري</h3>';
        $html .= '<table class="statistics-table">';
        $html .= '<tr>';
        $html .= '<td class="stat-label">إجمالي التحصيلات</td>';
        $html .= '<td class="stat-label">أفضل محصل</td>';
        $html .= '<td class="stat-label">مبلغ أفضل محصل</td>';
        $html .= '</tr>';
        $html .= '<tr>';
        $html .= '<td class="stat-value">' . number_format($totalMonthlyAmount, 2) . ' ريال</td>';
        $html .= '<td class="stat-value">' . e($topPerformer['name']) . '</td>';
        $html .= '<td class="stat-value">' . number_format($topPerformer['amount'], 2) . ' ريال</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '</div>';

        // قسم ثالث: أعلى 10 محصلين
        $topPerformers = Collection::selectRaw('added_by, COUNT(*) as collections_count, SUM(collected_amount) as total_amount')
            ->where('status', 'approved')
            ->where('created_at', '>=', now()->startOfMonth())
            ->with('addedBy:id,name')
            ->groupBy('added_by')
            ->orderByDesc('total_amount')
            ->limit(10)
            ->get();

        if ($topPerformers->isNotEmpty()) {
            $html .= '<div class="statistics-section">';
            $html .= '<h3>أعلى المحصلين هذا الشهر</h3>';
            $html .= '<table class="statistics-table">';
            $html .= '<tr>';
            $html .= '<td class="stat-label">اسم المحصل</td>';
            $html .= '<td class="stat-label">عدد التحصيلات</td>';
            $html .= '<td class="stat-label">إجمالي المبلغ</td>';
            $html .= '</tr>';

            foreach ($topPerformers as $performer) {
                $average = $performer->collections_count > 0 ? $performer->total_amount / $performer->collections_count : 0;
                $html .= '<tr>';
                $html .= '<td class="stat-value">' . e($performer->addedBy->name ?? 'غير محدد') . '</td>';
                $html .= '<td class="stat-value">' . number_format($performer->collections_count) . '</td>';
                $html .= '<td class="stat-value">' . number_format($performer->total_amount, 2) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';
            $html .= '</div>';
        }

        // تذييل التقرير
        $html .= '<div class="report-footer">';
        $html .= '<span>تم إنشاء التقرير في: ' . now()->format('Y/m/d H:i:s') . '</span>';
        $html .= '<div style="margin-top: 10px;">';
        $html .= '<span>إجمالي المستخدمين: ' . number_format($totalUsers) . ' </span>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';

        return $html;
    }

    private function getUsersReportCSS(): string
    {
        return '
@page {
    margin: 15px;
}

body {
    font-family: almarai;
    font-size: 12px;
    direction: rtl;
    margin: 0;
    padding: 0;
    color: #333;
    line-height: 1.4;
}

.content {
    margin: 0;
}

.report-header {
    margin-bottom: 25px;
    border-bottom: 2px solid #1f2a44;
    padding-bottom: 15px;
    text-align: center;
}

.report-header h2 {
    margin: 0 0 10px 0;
    font-size: 22px;
    font-weight: 700;
    color: #1f2a44;
}

.header-info {
    font-size: 12px;
    color: #666;
}

.date-range, .applied-filters {
    display: block;
    margin: 3px 0;
}

.data-section h3, .statistics-section h3 {
    margin: 20px 0 15px 0;
    font-size: 16px;
    color: #1f2a44;
    text-align: center;
    font-weight: 700;
    border-bottom: 1px solid #1f2a44;
    padding-bottom: 8px;
}

.statistics-section {
    margin: 20px 0;
}

.statistics-table, .data-table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid #ddd;
    margin-bottom: 15px;
}

.statistics-table td, .data-table th, .data-table td {
    border: 1px solid #ddd;
    padding: 8px 6px;
    text-align: center;
    font-size: 11px;
}

.data-table th, .data-table td {
    font-size: 9px;
    padding: 6px 4px;
}

.stat-label {
    background-color: #1f2a44;
    color: white;
    font-weight: 600;
    font-size: 11px;
}

.stat-value {
    background-color: white;
    font-weight: 600;
    font-size: 11px;
    color: #1f2a44;
}

.data-table th {
    background-color: #1f2a44;
    color: #fff;
    font-weight: 600;
    font-size: 10px;
}

.data-table .text-right {
    text-align: right;
}

.data-table .amount-cell {
    font-weight: 600;
    color: #1f2a44;
}

.data-table tbody tr:nth-child(even) {
    background-color: #fff;
}

.no-records {
    text-align: center;
    padding: 20px;
    color: #777;
    font-style: italic;
}

.report-footer {
    margin-top: 30px;
    padding: 15px 0;
    border-top: 1px solid #1f2a44;
    text-align: center;
    font-size: 11px;
    color: #666;
}

.report-footer span {
    display: inline-block;
    margin: 0 20px;
}
';
    }
}
