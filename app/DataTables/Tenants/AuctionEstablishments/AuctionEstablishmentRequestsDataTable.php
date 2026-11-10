<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\AuctionEstablishments;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;

final class AuctionEstablishmentRequestsDataTable extends AbstractDataTable
{
    protected function getTableId(): string
    {
        return 'auction-establishment-requests-table';
    }

    protected function resource()
    {
        return Auction::query()->select(['id', 'assignment_id', 'name', 'city', 'cover_image', 'created_at'])
            ->with(['assignment.issuingEntity', 'properties:id', 'establishmentRequest' => fn ($query) => $query->select(['id', 'auction_id', 'submitted_at', 'approved_at', 'approval_number'])])
            ->withCount('properties');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'width' => '10px', 'className' => 'custom-checkbox'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false, 'width' => '60px', 'className' => 'text-center'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المزاد', 'width' => '200px'],
            ['data' => 'assignment_number', 'name' => 'assignment.assignment_number', 'title' => 'رقم الإسناد', 'orderable' => false, 'width' => '140px'],
            ['data' => 'issuing_entity', 'name' => 'issuing_entity', 'title' => 'الجهة المسندة', 'orderable' => false, 'searchable' => false, 'width' => '150px'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة', 'width' => '120px'],
            ['data' => 'properties_count', 'name' => 'properties_count', 'title' => 'عدد العقارات', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'request_status', 'name' => 'request_status', 'title' => 'حالة الطلب', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'approval_number', 'name' => 'establishmentRequest.approval_number', 'title' => 'رقم الموافقة', 'orderable' => false, 'width' => '140px'],
            ['data' => 'submitted_at', 'name' => 'submitted_at', 'title' => 'تاريخ تقديم الطلب', 'orderable' => true, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإنشاء', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row): string => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->addColumn('image', fn ($row): string => $this->renderTableImage($row->cover_image ? tasset($row->cover_image) : null, (string) $row->name))
            ->editColumn('name', fn ($row): string => '<a href="'.e(tenant_route_name('auctions.show', $row)).'" class="text-body fw-medium">'.e($row->name).'</a>')
            ->addColumn('assignment_number', fn ($row): string => $this->copyableCode($row->assignment?->assignment_number))
            ->addColumn('issuing_entity', fn ($row): string => (string) ($row->assignment?->issuingEntity?->name ?? $row->assignment?->issuing_entity ?? '-'))
            ->editColumn('properties_count', fn ($row): string => '<span class="badge bg-info">'.(int) $row->properties_count.'</span>')
            ->addColumn('request_status', function ($row): string {
                if (! $row->establishmentRequest) {
                    return '<span class="badge bg-label-secondary">لم يقدم</span>';
                }

                return $row->establishmentRequest->isApproved() ? '<span class="badge bg-label-success">معتمد</span>' : '<span class="badge bg-label-warning">قيد المراجعة</span>';
            })
            ->addColumn('approval_number', fn ($row): string => $this->copyableCode($row->establishmentRequest?->approval_number))
            ->addColumn('submitted_at', fn ($row): string => $this->formatDate($row->establishmentRequest?->submitted_at))
            ->orderColumn('submitted_at', function ($query, $order): void {
                $direction = strtolower((string) $order) === 'desc' ? 'desc' : 'asc';
                $subquery = AuctionEstablishmentRequest::query()
                    ->select('submitted_at')
                    ->whereColumn('auction_establishment_requests.auction_id', 'auctions.id')
                    ->limit(1);
                $query->orderBy($subquery, $direction);
            })
            ->editColumn('created_at', fn ($row): string => $this->formatDate($row->created_at))
            ->addColumn('actions', fn ($row): string => view('tenant.auction-establishment-requests.action', ['row' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'image', 'name', 'assignment_number', 'properties_count', 'request_status', 'approval_number', 'actions'];
    }

    protected function filterableColumns(): array
    {
        return ['name', 'city', 'assignment.assignment_number', 'assignment.name', 'assignment.issuing_entity', 'establishmentRequest.approval_number'];
    }

    protected function getCustomOrder(): array
    {
        return [9, 'desc'];
    }

    protected function applyCustomFilters($query)
    {
        $status = request('request_status');
        if ($status === 'not_started') {
            $query->doesntHave('establishmentRequest');
        } elseif ($status === 'approved') {
            $query->whereHas('establishmentRequest', fn ($request) => $request->whereNotNull('approved_at')->whereRaw("TRIM(COALESCE(approval_number, '')) <> ''"));
        } elseif ($status === 'under_review') {
            $query->whereHas('establishmentRequest', fn ($request) => $request->where(fn ($pending) => $pending->whereNull('approved_at')->orWhereRaw("TRIM(COALESCE(approval_number, '')) = ''")));
        }

        if (request()->filled('submitted_from')) {
            $query->whereHas('establishmentRequest', fn ($request) => $request->whereDate('submitted_at', '>=', request('submitted_from')));
        }
        if (request()->filled('submitted_to')) {
            $query->whereHas('establishmentRequest', fn ($request) => $request->whereDate('submitted_at', '<=', request('submitted_to')));
        }

        return $query;
    }

    protected function buttons(): array
    {
        return [['extend' => 'collection', 'className' => 'btn btn-export', 'text' => 'الإجراءات', 'buttons' => [['extend' => 'copy', 'text' => 'نسخ'], ['extend' => 'excel', 'text' => 'إكسل']]]];
    }
}
