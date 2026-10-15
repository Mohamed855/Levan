<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\Properties;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Tenants\Properties\Property;

class PropertiesDataTable extends AbstractDataTable
{
    private string $route = 'properties';

    protected function getTableId(): string
    {
        return 'properties-table';
    }

    protected function resource()
    {
        return Property::query()
            ->with(['assignment', 'attachments']);
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'property_number', 'name' => 'property_number', 'title' => 'رقم العقار', 'width' => '100px'],
            ['data' => 'image', 'name' => 'image', 'title' => 'الصورة', 'orderable' => false, 'searchable' => false, 'width' => '70px'],
            ['data' => 'name', 'name' => 'name', 'title' => 'الاسم', 'width' => '170px'],
            ['data' => 'issuing_entity', 'name' => 'issuing_authority', 'title' => 'الجهة', 'width' => '140px'],
            ['data' => 'city', 'name' => 'city', 'title' => 'المدينة', 'width' => '110px'],
            ['data' => 'type', 'name' => 'type', 'title' => 'النوع', 'width' => '110px'],
            ['data' => 'area', 'name' => 'area', 'title' => 'المساحة (م²)', 'width' => '100px'],
            ['data' => 'deed_number', 'name' => 'deed_number', 'title' => 'رقم الصك', 'width' => '120px'],
            ['data' => 'data_status', 'name' => 'data_status', 'title' => 'حالة البيانات', 'width' => '110px', 'className' => 'text-center'],
            ['data' => 'assignment_status', 'name' => 'assignment_id', 'title' => 'حالة الارتباط', 'width' => '130px', 'className' => 'text-center'],
            ['data' => 'status', 'name' => 'status', 'title' => 'الحالة', 'width' => '90px', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', function ($row): string {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('property_number', function ($row): string {
                return $this->copyableCode((string) $row->property_number);
            })
            ->addColumn('image', function ($row): string {
                return $this->renderTableImage(
                    $row->main_image ? tasset($row->main_image) : null,
                    (string) $row->name,
                );
            })
            ->editColumn('name', function ($row): string {
                $showUrl = tenant_route_name($this->route.'.show', $row->id);

                return '<a href="'.$showUrl.'" class="text-body fw-medium">'.e($row->name).'</a>';
            })
            ->addColumn('issuing_entity', function ($row): string {
                if ($row->assignment) {
                    return e($row->assignment->issuing_entity ?? $row->assignment->name);
                }

                return e($row->issuing_authority ?: '-');
            })
            ->editColumn('city', function ($row): string {
                return e($row->city ?: '-');
            })
            ->editColumn('type', function ($row): string {
                return '<span class="badge bg-label-info">'.e($row->type_label).'</span>';
            })
            ->editColumn('area', function ($row): string {
                return number_format((float) $row->area).' م²';
            })
            ->editColumn('deed_number', function ($row): string {
                return $row->deed_number ? $this->copyableCode($row->deed_number) : '-';
            })
            ->editColumn('data_status', function ($row): string {
                return '<span class="badge '.$row->data_status_badge_class.'">'.e($row->data_status_label).'</span>';
            })
            ->addColumn('assignment_status', function ($row): string {
                if ($row->assignment) {
                    $url = tenant_route_name('assignments.show', $row->assignment_id);

                    return '<a href="'.$url.'" class="badge bg-label-primary text-decoration-none d-inline-flex align-items-center gap-1" title="'.e($row->assignment->name).'">'
                        .'<i class="ti ti-link text-primary fs-6"></i> '.e(mb_strimwidth($row->assignment->name, 0, 15, '...')).'</a>';
                }

                return '<span class="badge bg-label-danger d-inline-flex align-items-center gap-1"><i class="ti ti-link-off text-danger fs-6"></i> غير مرتبط</span>';
            })
            ->editColumn('status', function ($row): string {
                $canEdit = auth('tenant')->user()?->can('properties.edit');
                if ($canEdit) {
                    $checked = $row->is_active ? 'checked' : '';
                    $url = tenant_route_name('properties.toggle-status', $row->id);

                    return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="prop_status_'.$row->id.'" data-url="'.e($url).'" data-active-value="active" data-inactive-value="inactive" '.$checked.' aria-label="تبديل حالة العقار"></div></div>';
                }

                $statusClass = $row->is_active ? 'bg-label-success' : 'bg-label-danger';
                $statusText = $row->is_active ? 'نشط' : 'متوقف';

                return '<span class="badge '.$statusClass.'">'.$statusText.'</span>';
            })
            ->addColumn('actions', function ($row): string {
                return view('tenants.properties.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    private function copyableCode(string $code): string
    {
        $escaped = htmlspecialchars($code, ENT_QUOTES, 'UTF-8');

        return sprintf(
            '<div class="d-inline-flex align-items-center gap-1 copyable-code-wrapper"
                  data-code="%s"
                  title="اضغط للنسخ">
                <code class="copyable-code">%s</code>
                <i class="ti ti-copy text-muted copy-icon"></i>
            </div>',
            $escaped,
            $escaped
        );
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'property_number', 'image', 'name', 'type', 'deed_number', 'data_status', 'assignment_status', 'status', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('assignment_id')) {
            $query->where('assignment_id', $request->assignment_id);
        }

        if ($request->filled('assignment_status')) {
            if ($request->assignment_status === 'linked') {
                $query->whereNotNull('assignment_id');
            } elseif ($request->assignment_status === 'unlinked') {
                $query->whereNull('assignment_id');
            }
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('data_status')) {
            $query->where('data_status', $request->data_status);
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        if ($request->filled('filter_deed_number')) {
            $query->where('deed_number', 'like', '%'.$request->filter_deed_number.'%');
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

        if (auth('tenant')->user()?->can('properties.delete')) {
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
        if (auth('tenant')->user()?->can('properties.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة عقار',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".tenant_route_name($this->route.'.create')."'; }",
            ];
        }

        return [];
    }

    protected function parameters(): array
    {
        $parameters = parent::parameters();
        $parameters['responsive'] = false;
        $parameters['columnDefs'] = [$parameters['columnDefs'][0]];

        return $parameters;
    }
}
