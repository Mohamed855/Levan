<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Faq;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Faq;
use Illuminate\Support\Str;

class FaqDataTable extends AbstractDataTable
{
    private string $page = 'admin.faqs';

    private string $route = 'admin.faqs';

    protected function getTableId(): string
    {
        return 'faqs-table';
    }

    protected function resource()
    {
        return Faq::query();
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
                'data' => 'question',
                'name' => 'question',
                'title' => 'السؤال',
                'className' => 'text-center',
            ],
            [
                'data' => 'answer',
                'name' => 'answer',
                'title' => 'الإجابة',
                'className' => 'text-center',
                'orderable' => false,
            ],
            [
                'data' => 'created_at',
                'name' => 'created_at',
                'title' => 'تاريخ الإنشاء',
                'className' => 'text-center',
                'width' => '130px',
            ],
            [
                'data' => 'is_active',
                'name' => 'is_active',
                'title' => 'التفعيل',
                'className' => 'text-center',
                'width' => '100px',
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

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
            })
            ->editColumn('answer', function ($row) {
                return Str::limit(strip_tags((string) $row->answer), 100);
            })
            ->editColumn('created_at', fn ($row): string => $this->formatDate($row->created_at))
            ->editColumn('is_active', function ($row) {
                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.faqs.toggle-status', $row->id);

                return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة السؤال"></div></div>';
            })
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'is_active', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        $status = $request->input('is_active', $request->input('status'));
        if ($status !== null && $status !== '') {
            if ($status === 'active' || $status === '1' || $status === 1) {
                $query->where('is_active', 1);
            } elseif ($status === 'inactive' || $status === '0' || $status === 0) {
                $query->where('is_active', 0);
            } else {
                $query->where('is_active', $status);
            }
        }

        return $query;
    }

    protected function getSearchableColumns(): array
    {
        return [
            'question',
            'answer',
        ];
    }

    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }

    protected function buttons(): array
    {
        $buttons = [
            $this->getActionsDropdownButton(),
        ];

        $add = $this->getAddButton();
        if (! empty($add)) {
            $buttons[] = $add;
        }

        return $buttons;
    }

    private function getActionsDropdownButton(): array
    {
        $buttons = [
            ['extend' => 'copy', 'text' => 'نسخ'],
            ['extend' => 'excel', 'text' => 'إكسل'],
        ];

        if (auth()->user()?->can('faqs.delete')) {
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
        if (auth()->user()?->can('faqs.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة سؤال',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
