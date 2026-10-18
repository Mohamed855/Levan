<?php

namespace App\DataTables\Tenants\Domain;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Tenants\Domain;
use App\Services\shared\NameCheap\GetDomainInfoService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DomainDataTable extends AbstractDataTable
{
    private $page = 'tenants.domain_setting';

    private $route = 'domains.setting';

    protected function getTableId()
    {
        return 'domain-table';
    }

    protected function resource()
    {
        $tenantId = tenant('id');

        return tenancy()->central(function () use ($tenantId) {
            return Domain::query()->where('tenant_id', $tenantId)->orderByRaw('is_active DESC, id DESC');
        });
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'id', 'visible' => false],
            [
                'data' => 'checkbox',
                'title' => '<input type="checkbox" id="select-all">',
                'orderable' => false,
                'searchable' => false,
                'className' => 'custom-checkbox',
                'width' => '10px',
            ],
            ['data' => 'domain',          'title' => 'النطاق'],
            ['data' => 'type',            'title' => 'النوع'],
            ['data' => 'is_active',       'title' => 'الحالة',          'orderable' => false],
            ['data' => 'expires_at',      'title' => 'تاريخ الانتهاء',  'orderable' => false],
            ['data' => 'remaining_days',  'title' => 'الأيام المتبقية', 'orderable' => false],
            ['data' => 'trasnfer_status', 'title' => 'حالة النقل'],
            ['data' => 'actions',         'name' => 'actions',          'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })

            ->editColumn('type', function ($row) {
                return $this->TypeBadge($row);
            })

            ->editColumn('is_active', function ($row) {
                return $this->ActiveToggle($row);
            })

            ->editColumn('expires_at', function ($row) {
                // بس للدومينات المحجوزة أو المنقولة
                if (! in_array($row->type, ['registered', 'transferred'])) {
                    return '<span class="text-muted">غير متاح</span>';
                }

                $info = $this->getDomainInfo($row->domain);

                if (! $info || ! $info['success']) {
                    return '<span class="text-muted">غير متاح</span>';
                }

                return Carbon::parse($info['expires_at'])->format('Y-m-d');
            })

            ->editColumn('remaining_days', function ($row) {
                // بس للدومينات المحجوزة أو المنقولة
                if (! in_array($row->type, ['registered', 'transferred'])) {
                    return '<span class="text-muted">غير متاح</span>';
                }

                $info = $this->getDomainInfo($row->domain);

                return $this->RemainingDaysBadge($info);
            })

            ->editColumn('transfer_code', function ($row) {
                return $row->transfer_code
                    ? '<span class="text-dark">'.$row->transfer_code.'</span>'
                    : '<span class="text-muted">-</span>';
            })

            ->editColumn('transfer_status', function ($row) {
                return $row->transfer_status
                    ? '<span class="text-dark">'.($row->transfer_status ?: '-').'</span>'
                    : '<span class="text-muted">-</span>';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at?->format('Y-m-d');
            })

            ->editColumn('updated_at', function ($row) {
                return $row->updated_at?->format('Y-m-d H:i');
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'domain', 'type', 'is_active', 'expires_at', 'remaining_days', 'transfer_code', 'transfer_status', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['domain', 'transfer_code', 'created_at'];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }

    protected function buttons(): array
    {
        return [
            $this->getAddButton(),

        ];
    }

    private function getAddButton(): array
    {
        return [
            [
                'text' => '<i class="ti ti-world-plus me-1"></i> حجز نطاق جديد (قريبا)',
                'className' => 'btn btn-primary btn-add disabled',
                // 'action'    => "function(){ window.location.href='" . tenant_route_name('domains.newdomain') . "'; }",
            ],
            [
                'text' => '<i class="ti ti-link me-1"></i> ربط نطاق خارجي (قريبا)',
                'className' => 'btn btn-primary btn-add disabled',
                // 'action'    => "function(){ window.location.href='" . tenant_route_name('domains.link-domain') . "'; }",
            ],
            // [
            //     'text'      => '<i class="ti ti-transfer-in me-1"></i> نقل نطاق إلى مسند (قريبا)',
            //     'className' => 'btn btn-primary btn-add',
            //     // 'action'    => "function(){ window.location.href='" . tenant_route_name('domains.transfer-domain') . "'; }",
            // ]
        ];
    }

    //
    // TODO islam
    // الدالة هنا تسبب تاخير كبير في جلب البيانات يجب تخزينها في كاش او الاستغناء عنها و اضافة صفخة لتفاصيل الدومين وجلب البيانات الخاصة به

    private function getDomainInfo($domain)
    {
        try {
            $service = app(GetDomainInfoService::class);

            return $service->getDomainInfo($domain);
        } catch (\Exception $e) {
            Log::error("Failed to fetch domain info for {$domain}: ".$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function TypeBadge($row): string
    {
        $colors = [
            'registered' => 'success',
            'transferred' => 'info',
            'pending' => 'warning',
            'connected' => 'primary',
        ];

        $translations = [
            'registered' => 'دومين محجوز',
            'transferred' => 'دومين منقول',
            'pending' => 'قيد الانتظار',
            'connected' => 'دومين مربوط',
        ];

        $color = $colors[$row->type] ?? 'secondary';
        $text = $translations[$row->type] ?? $row->type;

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $color,
            $text
        );
    }

    private function ActiveToggle($row): string
    {
        $badgeClass = $row->is_active == 1 ? 'success' : 'secondary';
        $statusText = $row->is_active == 1 ? 'نشط' : 'غير نشط';

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $badgeClass,
            $statusText
        );
    }

    private function RemainingDaysBadge($info): string
    {
        if (! $info || ! $info['success'] || ! isset($info['remaining_days'])) {
            return '<span class="text-muted">غير متاح</span>';
        }

        $days = $info['remaining_days'];

        if ($days <= 0) {
            $color = 'danger';
            $text = 'منتهي';
        } elseif ($days <= 30) {
            $color = 'danger';
            $text = $days.' يوم';
        } elseif ($days <= 60) {
            $color = 'warning';
            $text = $days.' يوم';
        } else {
            $color = 'primary';
            $text = $days.' يوم';
        }

        return sprintf(
            '<span class="badge bg-%s">%s</span>',
            $color,
            $text
        );
    }
}
