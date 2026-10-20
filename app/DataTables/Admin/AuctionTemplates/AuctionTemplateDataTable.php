<?php

declare(strict_types=1);

namespace App\DataTables\Admin\AuctionTemplates;

use App\DataTables\Abstract\AbstractDataTable;
use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;

class AuctionTemplateDataTable extends AbstractDataTable
{
    private string $page = 'admin.auction-templates';

    private string $route = 'admin.auction-templates';

    protected function getTableId(): string
    {
        return 'auction-templates-table';
    }

    protected function resource()
    {
        return AuctionTemplate::query()->with('creator');
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('is_active')) {
            $val = $request->is_active;
            if ($val === 'active' || $val === '1' || $val === 1) {
                $query->where('is_active', true);
            } elseif ($val === 'inactive' || $val === '0' || $val === 0) {
                $query->where('is_active', false);
            }
        }

        return $query;
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox'],
            ['data' => 'name', 'name' => 'name', 'title' => 'اسم القالب', 'className' => 'text-start'],
            ['data' => 'type', 'name' => 'type', 'title' => 'النوع', 'className' => 'text-center'],
            ['data' => 'file', 'name' => 'file_path', 'title' => 'الملف', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة', 'className' => 'text-center'],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')

            ->editColumn('name', function ($row) {
                $showUrl = route($this->route.'.show', $row->id);
                $desc = $row->description ? '<small class="text-muted d-block text-truncate" style="max-width: 250px;">'.e($row->description).'</small>' : '';

                return '<div><a href="'.$showUrl.'" class="text-body fw-medium d-block">'.e($row->name).'</a>'.$desc.'</div>';
            })

            ->editColumn('type', function ($row) {
                $type = $row->type instanceof AuctionTemplateType ? $row->type : AuctionTemplateType::tryFrom((string) $row->type);
                $label = $type ? $type->label() : (string) $row->type;
                $color = $type ? $type->color() : 'secondary';
                $icon = $type ? $type->icon() : 'ti-file';

                return '<span class="badge bg-label-'.e($color).'"><i class="ti '.e($icon).' me-1"></i>'.e($label).'</span>';
            })

            ->addColumn('file', function ($row) {
                if (! $row->file_url) {
                    return '<span class="text-muted">-</span>';
                }

                return '<a href="'.e($row->file_url).'" target="_blank" class="btn btn-icon btn-sm btn-label-primary" title="عرض ملف PDF">'
                    .'<i class="ti ti-file-type-pdf fs-5"></i>'
                    .'</a>';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at ? '<span class="text-muted font-monospace">'.e($row->created_at->format('Y-m-d')).'</span>' : '-';
            })

            ->editColumn('is_active', function ($row) {
                if (! auth()->user()?->can('auction_templates.edit')) {
                    return $row->is_active
                        ? '<span class="badge bg-success">مفعل</span>'
                        : '<span class="badge bg-danger">غير مفعل</span>';
                }

                $checked = $row->is_active ? 'checked' : '';
                $url = route($this->route.'.toggle-status', $row);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة القالب">
                        </div>
                    </div>';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', [
                    'row' => $row,
                    'route' => $this->route,
                ])->render();
            });
    }

    protected function rawColumns(): array
    {
        return [
            'checkbox',
            'name',
            'type',
            'file',
            'created_at',
            'is_active',
            'actions',
        ];
    }

    protected function getSearchableColumns(): array
    {
        return [
            'name',
            'description',
        ];
    }

    protected function getCustomOrder(): array
    {
        return [4, 'desc'];
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

        if (auth()->user()?->can('auction_templates.delete')) {
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
        if (auth()->user()?->can('auction_templates.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة قالب جديد',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
