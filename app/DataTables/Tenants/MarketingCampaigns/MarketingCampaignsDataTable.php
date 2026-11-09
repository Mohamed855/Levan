<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\MarketingCampaigns;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

final class MarketingCampaignsDataTable extends AbstractDataTable
{
    private string $route = 'marketing-campaigns';

    protected function getTableId(): string
    {
        return 'marketing-campaigns-table';
    }

    protected function resource()
    {
        return Auction::query()
            ->select('auctions.*')
            ->with([
                'marketingCampaigns' => fn ($query) => $query->withCount('evidence'),
                'assignment.issuingEntity',
                'establishmentRequest',
                'properties:id',
            ])
            ->withCount([
                'properties',
                'marketingCampaigns as marketing_campaigns_count' => fn ($q) => $q->select(DB::raw('count(distinct channel)')),
            ]);
    }

    protected function getColumns(): array
    {
        return [
            [
                'data' => 'checkbox',
                'name' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
            ],
            [
                'data' => 'image',
                'name' => 'image',
                'title' => 'الصورة',
                'orderable' => false,
                'searchable' => false,
                'width' => '60px',
                'className' => 'text-center',
            ],
            [
                'data' => 'name',
                'name' => 'name',
                'title' => 'اسم المزاد',
                'width' => '200px',
            ],
            [
                'data' => 'assignment_number',
                'name' => 'assignment.assignment_number',
                'title' => 'رقم الإسناد',
                'orderable' => false,
                'className' => 'text-center',
                'width' => '140px',
            ],
            [
                'data' => 'issuing_entity',
                'name' => 'assignment_id',
                'title' => 'الجهة المسندة',
                'width' => '150px',
            ],
            [
                'data' => 'city',
                'name' => 'city',
                'title' => 'المدينة',
                'width' => '120px',
                'className' => 'text-center',
            ],
            [
                'data' => 'start_date',
                'name' => 'start_date',
                'title' => 'تاريخ البداية',
                'width' => '130px',
                'className' => 'text-center',
            ],
            [
                'data' => 'end_date',
                'name' => 'end_date',
                'title' => 'تاريخ النهاية',
                'width' => '130px',
                'className' => 'text-center',
            ],
            [
                'data' => 'properties_count',
                'name' => 'properties_count',
                'title' => 'عدد العقارات',
                'width' => '90px',
                'className' => 'text-center',
            ],
            [
                'data' => 'completion_status',
                'name' => 'completion_status',
                'title' => 'حالة الاكتمال',
                'orderable' => false,
                'searchable' => false,
                'width' => '110px',
                'className' => 'text-center',
            ],
            [
                'data' => 'marketing_campaigns_count',
                'name' => 'marketing_campaigns_count',
                'title' => 'عدد الحملات',
                'searchable' => false,
                'className' => 'text-center',
                'width' => '110px',
            ],
            [
                'data' => 'evidence_count',
                'name' => 'evidence_count',
                'title' => 'مرفقات الحملات',
                'orderable' => false,
                'searchable' => false,
                'className' => 'text-center',
                'width' => '120px',
            ],
            [
                'data' => 'created_at',
                'name' => 'created_at',
                'title' => 'تاريخ الإنشاء',
                'width' => '130px',
                'className' => 'text-center',
            ],
            [
                'data' => 'actions',
                'name' => 'actions',
                'title' => 'الإجراءات',
                'orderable' => false,
                'searchable' => false,
                'width' => '120px',
                'className' => 'text-center',
            ],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                if ($row->is_finalized || $row->isClosed()) {
                    return '<input type="checkbox" class="row-checkbox" disabled title="لا يمكن حذف حملات مزاد مقفل أو منتهٍ">';
                }

                $hasCampaigns = ($row->marketing_campaigns_count ?? 0) > 0 || ($row->marketingCampaigns && $row->marketingCampaigns->isNotEmpty());
                if (! $hasCampaigns) {
                    return '<input type="checkbox" class="row-checkbox" disabled title="لا توجد حملات تسويقية لهذا المزاد">';
                }

                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->addColumn('image', function ($row): string {
                $image = $row->cover_image ? tasset($row->cover_image) : null;

                return $this->renderTableImage($image, (string) $row->name);
            })
            ->editColumn('name', function ($row) {
                $showUrl = tenant_route_name('auctions.show', $row->id);

                return '<div>
                    <a href="'.$showUrl.'" class="text-body fw-medium d-block">'.e($row->name).'</a>
                    <small class="text-muted">'.e($row->type_label).' - '.e($row->city).'</small>
                </div>';
            })
            ->addColumn('assignment_number', function ($row): string {
                return $this->copyableCode($row->assignment?->assignment_number);
            })
            ->addColumn('issuing_entity', function ($row) {
                if (! $row->assignment) {
                    return '-';
                }

                $entityName = $row->assignment->issuingEntity?->name
                    ?? $row->assignment->issuing_entity
                    ?? $row->assignment->name;

                if (blank($entityName)) {
                    return '-';
                }

                return '<a href="'.tenant_route_name('assignments.show', $row->assignment_id).'" class="text-body fw-medium" title="'.e((string) $row->assignment->name).'">'.e((string) $entityName).'</a>';
            })
            ->editColumn('city', fn ($row): string => $row->city ? e($row->city) : '-')
            ->editColumn('start_date', function ($row) {
                if (! $row->start_date) {
                    return '-';
                }
                $date = Carbon::parse($row->start_date)->format('Y/m/d');
                $time = $row->start_time ? Carbon::parse($row->start_time)->format('h:i A') : '';

                return '<div>'.$date.'</div><small class="text-muted">'.$time.'</small>';
            })
            ->editColumn('end_date', function ($row) {
                if (! $row->end_date) {
                    return '-';
                }
                $date = Carbon::parse($row->end_date)->format('Y/m/d');
                $time = $row->end_time ? Carbon::parse($row->end_time)->format('h:i A') : '';

                return '<div>'.$date.'</div><small class="text-muted">'.$time.'</small>';
            })
            ->editColumn('properties_count', function ($row) {
                return '<span class="badge bg-info">'.(int) $row->properties_count.'</span>';
            })
            ->addColumn('completion_status', function ($row): string {
                $status = $row->completion_status;

                return '<span class="badge bg-label-'.$status->color().'">'.e($status->label()).'</span>';
            })
            ->editColumn('marketing_campaigns_count', function ($row) {
                $count = $row->marketingCampaigns ? $row->marketingCampaigns->pluck('channel')->filter()->unique()->count() : (int) ($row->marketing_campaigns_count ?? 0);

                return '<span class="badge bg-label-primary">'.$count.'</span>';
            })
            ->addColumn('evidence_count', function ($row) {
                $total = $row->marketingCampaigns ? $row->marketingCampaigns->sum('evidence_count') : 0;

                return '<span class="badge bg-label-info">'.$total.' مرفق</span>';
            })
            ->editColumn('created_at', fn ($row): string => $row->created_at ? $row->created_at->format('Y-m-d') : '-')
            ->addColumn('actions', fn ($row) => view('tenant.marketing-campaigns.action', ['row' => $row, 'route' => $this->route])->render());
    }

    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'image',
            'name',
            'assignment_number',
            'issuing_entity',
            'start_date',
            'end_date',
            'properties_count',
            'completion_status',
            'marketing_campaigns_count',
            'evidence_count',
            'actions',
        ];
    }

    protected function filterableColumns(): array
    {
        return ['name', 'city', 'assignment.assignment_number', 'assignment.name', 'assignment.issuing_entity'];
    }

    protected function applyCustomFilters($query)
    {
        if (request()->filled('auction_id')) {
            $query->where('auctions.id', (int) request('auction_id'));
        }
        if (request()->filled('channel')) {
            $query->whereHas('marketingCampaigns', fn ($q) => $q->where('channel', request('channel')));
        }
        if (in_array(request()->input('completion_status'), ['complete', 'incomplete'], true)) {
            $completeIds = app(\App\Services\Tenants\Auctions\Reports\AuctionMarketingCompleteness::class)->completeAuctionIds();
            request()->input('completion_status') === 'complete' ? $query->whereIn('auctions.id', $completeIds) : $query->whereNotIn('auctions.id', $completeIds);
        }
        if (request()->filled('created_from')) {
            $query->whereDate('auctions.created_at', '>=', request('created_from'));
        }
        if (request()->filled('created_to')) {
            $query->whereDate('auctions.created_at', '<=', request('created_to'));
        }
        if (request()->filled('city')) {
            $query->where('city', request('city'));
        }

        return $query;
    }

    protected function buttons(): array
    {
        return [
            $this->getActionsDropdownButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth('tenant')->user()?->can('marketing_campaigns.delete')) {
            $buttons[] = [
                'text' => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action' => 'function() { handleMassDelete(); }',
            ];
        }

        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => $buttons,
        ];
    }
}
