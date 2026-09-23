<?php

namespace App\Services\Tenants\Communication\MessageLog;

use App\Exports\Tenants\Communication\MessageIndividualLogsExport;
use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\Communication\MessageLog\MessageIndividualLog;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class MessageIndividualLogService
{
    /*
    |--------------------------------------------------------------------------
    | Data Retrieval Methods
    |--------------------------------------------------------------------------
    | Methods for retrieving and filtering message individual logs data.
    */
    public function getLogsQuery(): Builder
    {
        return MessageIndividualLog::query()
            ->with([
                'customer:id,name,customer_type,identity_number,account_number',
                'company:id,name',
                'portfolio:id,name',
                'template:id,name',
                'sentBy:id,name'
            ])
            ->select([
                'id',
                'batch_log_id',
                'customer_id',
                'customer_name',
                'company_id',
                'portfolio_id',
                'phone_numbers',
                'phone_numbers_count',
                'message_content',
                'message_type',
                'platform',
                'template_id',
                'template_name',
                'status',
                'status_message',
                'error_message',
                'total_cost',
                'sent_at',
                'failed_at',
                'sent_by',
                'created_at'
            ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Filter Application
    |--------------------------------------------------------------------------
    | Apply various filters to the logs query.
    */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['message_type'])) {
            $query->where('message_type', $filters['message_type']);
        }

        if (!empty($filters['platform'])) {
            $query->where('platform', $filters['platform']);
        }

        if (!empty($filters['company_id'])) {
            $query->where('company_id', $filters['company_id']);
        }

        if (!empty($filters['portfolio_id'])) {
            $query->where('portfolio_id', $filters['portfolio_id']);
        }

        if (!empty($filters['template_id'])) {
            $query->where('template_id', $filters['template_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['search'])) {
            $searchTerm = trim($filters['search']);
            $query->where(function ($q) use ($searchTerm) {
                $q->where('customer_name', 'like', "%{$searchTerm}%")
                    ->orWhere('message_content', 'like', "%{$searchTerm}%")
                    ->orWhere('template_name', 'like', "%{$searchTerm}%")
                    ->orWhereHas('customer', function ($customerQuery) use ($searchTerm) {
                        $customerQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('company', function ($companyQuery) use ($searchTerm) {
                        $companyQuery->where('name', 'like', "%{$searchTerm}%");
                    })
                    ->orWhereHas('portfolio', function ($portfolioQuery) use ($searchTerm) {
                        $portfolioQuery->where('name', 'like', "%{$searchTerm}%");
                    });
            });
        }

        return $query;
    }

    /*
    |--------------------------------------------------------------------------
    | Statistics Generation
    |--------------------------------------------------------------------------
    | Generate statistics for the dashboard cards.
    */
    public function getStatistics(array $filters = []): array
    {
        $baseQuery = $this->getLogsQuery();
        $baseQuery = $this->applyFilters($baseQuery, $filters);

        $total = (clone $baseQuery)->count();
        $sent = (clone $baseQuery)->where('status', 'sent')->count();
        $failed = (clone $baseQuery)->where('status', 'failed')->count();
        $pending = (clone $baseQuery)->where('status', 'pending')->count();

        $totalCost = (clone $baseQuery)->where('status', 'sent')->sum('total_cost');

        return [
            'total_messages' => $total,
            'sent_messages' => $sent,
            'failed_messages' => $failed,
            'pending_messages' => $pending,
            'success_rate' => $total > 0 ? round(($sent / $total) * 100, 2) : 0,
            'total_cost' => number_format($totalCost, 2),
            'total_phones' => (clone $baseQuery)->sum('phone_numbers_count'),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Chart Data Generation
    |--------------------------------------------------------------------------
    | Generate data for dashboard charts.
    */
    public function getChartData(array $filters = []): array
    {
        $baseQuery = $this->getLogsQuery();
        $baseQuery = $this->applyFilters($baseQuery, $filters);

        // Messages by status
        $statusData = (clone $baseQuery)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Messages by company
        $companyData = (clone $baseQuery)
            ->select('company_id')
            ->with('company:id,name')
            ->get()
            ->groupBy('company.name')
            ->map(function ($items) {
                return $items->count();
            })
            ->toArray();

        // Messages by date (last 7 days)
        $dateData = (clone $baseQuery)
            ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', now()->subDays(7))
            ->groupBy('date')
            ->orderBy('date')
            ->pluck('count', 'date')
            ->toArray();

        return [
            'status' => $statusData,
            'companies' => $companyData,
            'daily' => $dateData,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Export Functionality
    |--------------------------------------------------------------------------
    | Handle exporting logs to Excel format.
    */
    public function exportLogs(array $filters = []): BinaryFileResponse
    {
        $query = $this->getLogsQuery();
        $query = $this->applyFilters($query, $filters);

        $logs = $query->orderBy('created_at', 'desc')->get();
        $fileName = 'message_individual_logs_' . now()->format('Y_m_d_H_i_s') . '.xlsx';

        return Excel::download(new MessageIndividualLogsExport($logs), $fileName);
    }

    /*
    |--------------------------------------------------------------------------
    | Form Data Preparation
    |--------------------------------------------------------------------------
    | Prepare data for form dropdowns and filters.
    */
    public function getFormData(): array
    {
        return [
            'companies' => Company::active()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'portfolios' => Portfolio::active()
                ->select('id', 'name', 'company_id')
                ->with('company:id,name')
                ->orderBy('name')
                ->get(),
            'customers' => Customer::select('id', 'name', 'company_id', 'portfolio_id')
                ->orderBy('name')
                ->get(),
            'templates' => MessageTemplate::active()
                ->select('id', 'name')
                ->orderBy('name')
                ->get(),
            'statuses' => [
                'pending' => 'معلق',
                'processing' => 'قيد المعالجة',
                'sent' => 'تم الإرسال',
                'failed' => 'فشل الإرسال',
                'cancelled' => 'ملغي'
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Single Log Retrieval
    |--------------------------------------------------------------------------
    | Retrieve a single log with all related data.
    */
    public function getLogWithDetails(int $id): MessageIndividualLog
    {
        return MessageIndividualLog::with([
            'customer:id,name,customer_type,identity_number,phone',
            'company:id,name',
            'portfolio:id,name',
            'template:id,name,content',
            'sentBy:id,name'
        ])->findOrFail($id);
    }

    /*
    |--------------------------------------------------------------------------
    | Deletion Handling
    |--------------------------------------------------------------------------
    | Handle log deletion with proper validation.
    */
    public function deleteLog(MessageIndividualLog $log): bool
    {
        return $log->delete();
    }
}
