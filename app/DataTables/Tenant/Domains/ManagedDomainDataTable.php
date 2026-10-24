<?php

declare(strict_types=1);

namespace App\DataTables\Tenant\Domains;

use App\DataTables\Abstract\AbstractDataTable;
use App\Services\Tenant\Shared\CentralManagedDomainGateway;

final class ManagedDomainDataTable extends AbstractDataTable
{
    protected function getTableId(): string { return 'tenant-managed-domains-table'; }
    protected function resource() { return app(CentralManagedDomainGateway::class)->queryForCurrentTenant(); }
    protected function getColumns(): array
    {
        return [
            ['data' => 'domain', 'name' => 'domain', 'title' => 'النطاق'],
            ['data' => 'type', 'name' => 'type', 'title' => 'النوع', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'expires_at', 'name' => 'expires_at', 'title' => 'الانتهاء', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false],
        ];
    }
    protected function addCustomColumns($dataTable): void
    {
        $dataTable
            ->editColumn('domain', fn ($row) => '<span class="font-monospace">'.e($row->domain).'</span>')
            ->editColumn('type', fn ($row) => '<span class="badge bg-label-info">'.e(match ($row->type) {
                'linked' => 'نطاق مرتبط', 'platform_subdomain' => 'نطاق فرعي للمنصة', 'registered' => 'مسجل', 'transfer' => 'نقل', default => $row->type,
            }).'</span>')
            ->editColumn('status', fn ($row) => '<span class="badge bg-'.($row->is_active ? 'success' : 'warning').'">'.e(match ($row->status) {
                'active' => 'نشط', 'pending_dns' => 'بانتظار DNS', 'pending_provisioning' => 'بانتظار التهيئة', 'pending_transfer' => 'طلب نقل', 'pending_registration' => 'طلب تسجيل', default => $row->status,
            }).'</span>')
            ->editColumn('expires_at', fn ($row) => $row->expires_at?->format('Y-m-d') ?? '-')
            ->addColumn('actions', fn ($row) => view('tenant.domains.action', compact('row'))->render());
    }
    protected function applyCustomFilters($query) { if (request()->filled('status')) { $query->where('status', request('status')); } return $query; }
    protected function rawColumns(): array { return ['domain', 'type', 'status', 'actions']; }
    protected function getSearchableColumns(): array { return ['domain']; }
    protected function getCustomOrder(): array { return [0, 'asc']; }
    protected function buttons(): array { return []; }
}
