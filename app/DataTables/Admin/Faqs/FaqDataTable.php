<?php

declare(strict_types=1);

namespace App\DataTables\Admin\Faqs;

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
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox', 'width' => '10px'],
            ['data' => 'question', 'name' => 'question', 'title' => 'السؤال'],
            ['data' => 'answer', 'name' => 'answer', 'title' => 'الإجابة'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإضافة'],
            ['data' => 'is_active', 'name' => 'is_active', 'title' => 'الحالة', 'className' => 'text-center'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }

    protected function addCustomColumns($datatable): void
    {
        $datatable
            ->addColumn('checkbox', fn ($row) => '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">')
            ->editColumn('question', fn ($row) => '<span class="fw-bold text-heading">'.e($row->question).'</span>')
            ->editColumn('answer', fn ($row) => '<span class="text-muted">'.e(Str::limit($row->answer, 80)).'</span>')
            ->editColumn('created_at', fn ($row) => $row->created_at?->format('Y-m-d') ?? '-')
            ->editColumn('is_active', function ($row) {
                if (! auth('admin')->user()?->can('faq.update')) {
                    return $row->is_active ? '<span class="badge bg-success">نشط</span>' : '<span class="badge bg-danger">معطل</span>';
                }

                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.faqs.toggle-status', $row->id);

                return '
                    <div class="d-flex justify-content-center">
                        <div class="form-check form-switch mb-0">
                            <input type="checkbox" class="form-check-input js-table-status-toggle" data-url="'.e($url).'" '.$checked.' aria-label="تبديل حالة السؤال">
                        </div>
                    </div>';
            })
            ->addColumn('actions', function ($row) {
                return view($this->page.'.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

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

    protected function rawColumns(): array
    {
        return ['checkbox', 'question', 'answer', 'is_active', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['question', 'answer'];
    }

    protected function getCustomOrder(): array
    {
        return [3, 'desc'];
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
            ['extend' => 'excel', 'text' => 'إكسل', 'exportOptions' => [
                'columns' => [1, 2, 3],
            ]],
        ];

        if (auth('admin')->user()?->can('faq.update')) {
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
        if (auth('admin')->user()?->can('faq.create')) {
            return [
                'text' => '<i class="ti ti-plus me-1"></i> إضافة سؤال شاسع',
                'className' => 'btn btn-primary btn-add',
                'action' => "function(){ window.location.href='".route($this->route.'.create')."'; }",
            ];
        }

        return [];
    }
}
