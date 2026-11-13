<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Auctions;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Tenants\Auctions\AuctionFilePreparationStatus;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\Auctions\Reports\AuctionMarketingCompleteness;

class AuctionsDataTable extends AbstractDataTable
{
    private string $route = 'auctions';

    protected function getTableId(): string
    {
        return 'auctions-table';
    }

    protected function resource()
    {
        return Auction::query()
            ->with(['assignment.issuingEntity', 'latestReportGeneration', 'publishedReportGeneration', 'establishmentRequest', 'properties:id', 'marketingCampaigns'])
            ->withCount('properties');
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false, 'width' => '60px', 'className' => 'text-center'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم المزاد', 'width' => '200px'],
            ['data' => 'issuing_entity', 'name' => 'assignment_id', 'title' => 'الجهة المسندة', 'width' => '150px'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة', 'width' => '120px', 'className' => 'text-center'],
            ['data' => 'start_date', 'name' => 'start_date', 'title' => 'تاريخ البداية', 'width' => '130px'],
            ['data' => 'end_date', 'name' => 'end_date', 'title' => 'تاريخ النهاية', 'width' => '130px'],
            ['data' => 'properties_count', 'name' => 'properties_count', 'title' => 'عدد العقارات', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'completion_status', 'name' => 'completion_status', 'title' => 'حالة الاكتمال', 'orderable' => false, 'searchable' => false, 'width' => '110px', 'className' => 'text-center'],
            ['data' => 'outputs', 'name' => 'outputs', 'title' => 'المخرجات', 'orderable' => false, 'searchable' => false, 'width' => '110px', 'className' => 'text-center'],
            ['data' => 'status_badge', 'name' => 'status_badge', 'title' => 'حالة المزاد', 'orderable' => false, 'searchable' => false, 'width' => '100px', 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإنشاء', 'width' => '130px', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
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
                $showUrl = tenant_route_name($this->route.'.show', $row->id);

                return '<div>
                    <a href="'.$showUrl.'" class="text-body fw-medium d-block">'.e($row->name).'</a>
                    <small class="text-muted">'.e($row->type_label).' - '.e($row->city).'</small>
                </div>';
            })
            ->addColumn('issuing_entity', fn ($row): string => $this->resolveAssignmentIssuingEntity($row))
            ->editColumn('city', fn ($row): string => $row->city ? e($row->city) : '-')
            ->addColumn('assignment', function ($row) {
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
            ->editColumn('start_date', function ($row) {
                return $this->formatDateWithSubTime($row->start_date, $row->start_time);
            })
            ->editColumn('end_date', function ($row) {
                return $this->formatDateWithSubTime($row->end_date, $row->end_time);
            })
            ->editColumn('properties_count', function ($row) {
                return '<span class="badge bg-info">'.(int) $row->properties_count.'</span>';
            })
            ->addColumn('outputs', function ($row): string {
                $published = $row->publishedReportGeneration;
                $hasPublishedFile = (bool) $published?->isReady() && (
                    filled($published->outputs_path) || filled($published->report_path)
                );

                $status = AuctionFilePreparationStatus::resolve(
                    $row->latestReportGeneration?->status,
                    $hasPublishedFile,
                );

                if ($status === AuctionFilePreparationStatus::READY && $published) {
                    $buttons = [];
                    $auctionId = $row->id ?? null;
                    if (filled($published->outputs_path)) {
                        $url = $auctionId ? tenant_route_name('auctions.reports.download', ['auction' => $auctionId, 'type' => 'outputs']) : '#';
                        $buttons[] = '<a href="'.e($url).'" class="badge bg-secondary me-1" title="تحميل مخرجات المزاد"><i class="ti ti-file-text me-1"></i>المخرجات</a>';
                    }
                    if (filled($published->report_path)) {
                        $url = $auctionId ? tenant_route_name('auctions.reports.download', ['auction' => $auctionId, 'type' => 'report']) : '#';
                        $buttons[] = '<a href="'.e($url).'" class="badge bg-secondary" title="تحميل تقرير المزاد"><i class="ti ti-file-analytics me-1"></i>التقرير</a>';
                    }

                    if (! empty($buttons)) {
                        return implode('', $buttons);
                    }
                }

                return $status->badgeHtml();
            })
            ->addColumn('status_badge', function ($row): string {
                return '<span class="badge '.$row->status_badge_class.'">'.e($row->status_label).'</span>';
            })
            ->addColumn('completion_status', function ($row): string {
                $status = $row->completion_status;

                return '<span class="badge bg-label-'.$status->color().'">'.e($status->label()).'</span>';
            })
            ->editColumn('created_at', fn ($row): string => $this->formatDate($row->created_at))
            ->addColumn('actions', function ($row) {
                return view('tenant.auctions.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'image', 'name', 'assignment', 'start_date', 'end_date', 'properties_count', 'outputs', 'status_badge', 'completion_status', 'actions'];
    }

    protected function filterableColumns(): array
    {
        return ['name', 'city', 'assignment.name', 'assignment.issuing_entity'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('status')) {
            $status = (string) $request->status;
            if ($status === 'approved') {
                $query->approved();
            } elseif ($status === 'closed') {
                $query->closed();
            } elseif ($status === 'pending') {
                $query->pending();
            }
        }
        if (in_array($request->input('completion_status'), ['complete', 'incomplete'], true)) {
            $completeIds = app(AuctionMarketingCompleteness::class)->completeAuctionIds();
            $request->input('completion_status') === 'complete' ? $query->whereIn('auctions.id', $completeIds) : $query->whereNotIn('auctions.id', $completeIds);
        }

        if ($request->filled('assignment_id')) {
            $query->where('assignment_id', $request->integer('assignment_id'));
        }

        if ($request->filled('start_date')) {
            $query->whereDate('start_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('end_date', '<=', $request->end_date);
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
            $this->getAddButton(),
        ];
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth('tenant')->user()?->can('auctions.delete')) {
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

    private function getAddButton(): array
    {
        if (auth('tenant')->user()?->can('auctions.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة مزاد',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
