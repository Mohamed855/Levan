<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\AuctionClosings;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionFormGeneration;
use Carbon\Carbon;

final class AuctionClosingsDataTable extends AbstractDataTable
{
    private string $route = 'auction-closings';

    protected function getTableId(): string
    {
        return 'auction-closings-table';
    }

    protected function resource()
    {
        return Auction::query()
            ->addSelect(['closing_form_id' => AuctionFormGeneration::query()->select('id')->whereColumn('auction_id', 'auctions.id')->where('type', 'closing')->where('status', 'ready')->whereNotNull('file_path')->latest('id')->limit(1)])
            ->with([
                'assignment.issuingEntity',
                'establishmentRequest',
                'properties:id',
                'closing' => fn ($q) => $q->select(['id', 'auction_id', 'saved_at'])
                    ->selectRaw('finalized_snapshot IS NOT NULL as is_finalized'),
            ])
            ->withCount([
                'properties',
                'properties as sold_properties_count' => fn ($q) => $q->where('auction_properties.result_status', 'sold'),
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
                'data' => 'properties_results',
                'name' => 'properties_results',
                'title' => 'نتائج العقارات',
                'orderable' => false,
                'searchable' => false,
                'width' => '130px',
                'className' => 'text-center',
            ],
            [
                'data' => 'closing_model',
                'name' => 'closing_model',
                'title' => 'نموذج الإقفال',
                'orderable' => false,
                'searchable' => false,
                'width' => '110px',
                'className' => 'text-center',
            ],
            [
                'data' => 'closing_status',
                'name' => 'closing_status',
                'title' => 'حالة الإقفال',
                'orderable' => false,
                'searchable' => false,
                'width' => '120px',
                'className' => 'text-center',
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
                $date = Carbon::parse($row->start_date)->format('Y/m/d');
                $time = $row->start_time ? Carbon::parse($row->start_time)->format('h:i A') : '';

                return '<div>'.$date.'</div><small class="text-muted">'.$time.'</small>';
            })
            ->editColumn('end_date', function ($row) {
                $date = Carbon::parse($row->end_date)->format('Y/m/d');
                $time = $row->end_time ? Carbon::parse($row->end_time)->format('h:i A') : '';

                return '<div>'.$date.'</div><small class="text-muted">'.$time.'</small>';
            })
            ->addColumn('properties_results', function ($row) {
                return view('components.shared.ui.progress-bar', [
                    'value' => (int) ($row->sold_properties_count ?? 0),
                    'total' => (int) ($row->properties_count ?? 0),
                ])->render();
            })
            ->addColumn('closing_model', function ($row): string {
                if (! ($row->closing_form_id ?? null) || ! auth('tenant')->user()?->can('auction_closings.view')) {
                    return '<span class="text-muted">—</span>';
                }

                return '<a href="'.e(tenant_route_name('auctions.forms.download', ['auction' => $row->id, 'type' => 'closing'])).'" class="badge bg-secondary" title="تحميل نموذج الإقفال"><i class="ti ti-file-text me-1"></i>نموذج الإقفال</a>';
            })
            ->addColumn('closing_status', function ($row) {
                if ($row->isClosed()) {
                    return '<span class="badge bg-label-success">منتهي</span>';
                }
                if ($row->closing) {
                    return '<span class="badge bg-label-info">محفوظ</span>';
                }

                return '<span class="badge bg-label-warning">لم يبدأ</span>';
            })
            ->editColumn('created_at', fn ($row): string => $row->created_at ? $row->created_at->format('Y-m-d') : '-')
            ->addColumn('actions', fn ($row) => view('tenant.auction-closings.action', ['row' => $row, 'route' => $this->route])->render());
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
            'properties_results',
            'closing_model',
            'closing_status',
            'actions',
        ];
    }

    protected function filterableColumns(): array
    {
        return ['name', 'city', 'assignment.assignment_number', 'assignment.name', 'assignment.issuing_entity'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('closing_status')) {
            $status = (string) $request->closing_status;
            if ($status === 'ended' || $status === 'closed') {
                $query->closed();
            } elseif ($status === 'not_started') {
                $query->doesntHave('closing')->open();
            } elseif ($status === 'saved') {
                $query->has('closing')->open();
            }
        }

        if ($request->filled('created_from')) {
            $query->whereDate('created_at', '>=', $request->created_from);
        }

        if ($request->filled('created_to')) {
            $query->whereDate('created_at', '<=', $request->created_to);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->string('city')->toString());
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
        return [
            'extend' => 'collection',
            'className' => 'btn btn-export',
            'text' => 'الإجراءات',
            'buttons' => [
                ['extend' => 'copy', 'text' => 'نسخ'],
                ['extend' => 'excel', 'text' => 'إكسل'],
            ],
        ];
    }
}
