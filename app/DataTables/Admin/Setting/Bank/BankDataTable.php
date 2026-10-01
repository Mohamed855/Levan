<?php

namespace App\DataTables\Admin\Setting\Bank;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\Bank\Bank;

class BankDataTable extends AbstractDataTable
{
    private $page  = "admin.settings.banks";
    private $route = "admin.settings.banks";


    // table id
    protected function getTableId()
    {
        return "banks-table";
    }


    protected function resource()
    {
        return Bank::query();
    }



    protected function getColumns(): array
    {
        return [
            ['data' => '', 'orderable' => false, 'searchable' => false],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            [
                'data'       => 'checkbox',
                'name'       => 'checkbox',
                'title'      => '<span class="custom-checkbox-header"><input type="checkbox" id="select-all"></span>',
                'orderable'  => false,
                'searchable' => false,
                'width' => '10px',
                'className' => 'custom-checkbox',
                'titleAttr' => 'تحديد الكل',
            ],
            ['data'  => 'icon',          'name' => 'icon',          'title'    => 'الشعار',     'orderable' => false, 'searchable' => false, 'className' => 'text-center', 'width' => '80px'],
            ['data'  => 'bank_name',     'name' => 'bank_name',     'title'    => 'اسم البنك',  'className' => 'text-start'],
            ['data'  => 'account_name',  'name' => 'account_name',  'title'    => 'اسم الحساب', 'className' => 'text-start'],
            ['data'  => 'account_number', 'name' => 'account_number', 'title'   => 'رقم الحساب', 'className' => 'text-start'],
            ['data'  => 'iban',          'name' => 'iban',           'title'   => 'رقم الحساب البنكي الدولي', 'className' => 'text-start'],
            ['data'  => 'is_active',     'name' => 'is_active',     'title' => 'الحالة'],

            ['data'  => 'actions',       'name' => 'actions',        'title'   => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }




    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            ->editColumn('icon', function ($row) {
                if (!$row->icon) {
                    return '<span class="badge bg-label-secondary">
                                <i class="ti ti-photo-off"></i>
                                لا يوجد
                            </span>';
                }

                $imagePath = asset('storage/' . $row->icon);

                return sprintf(
                    '<div class="bank-icon-wrapper">
                        <img src="%s"
                             alt="%s"
                             class="bank-icon"
                             style="max-width: 60px; max-height: 40px; object-fit: contain; border-radius: 4px;"
                             onerror="this.onerror=null; this.parentElement.innerHTML=\'<span class=\\\'badge bg-label-warning\\\'>خطأ</span>\';">
                    </div>',
                    $imagePath,
                    htmlspecialchars($row->bank_name)
                );
            })

            ->editColumn('is_active', function ($row) {
                $checked = $row->is_active ? 'checked' : '';
                $url = route('admin.settings.banks.toggle-status', $row->id);

                return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_' . $row->id . '" data-url="' . e($url) . '" ' . $checked . ' aria-label="تبديل حالة البنك"></div></div>';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'icon', 'is_active', 'actions'];
    }

    protected function applyCustomFilters($query)
    {
        $request = request();

        if ($request->filled('is_active')) {
            $val = $request->is_active;
            if ($val === 'active' || $val === '1' || $val === 1) {
                $query->where('is_active', 1);
            } elseif ($val === 'inactive' || $val === '0' || $val === 0) {
                $query->where('is_active', 0);
            } else {
                $query->where('is_active', $val);
            }
        }

        return $query;
    }


    // الحقول المسموح البحث فيها
    protected function getSearchableColumns(): array
    {
        return [
            'bank_name',
            'account_name',
            'account_number',
            'iban'
        ];
    }


    protected function getCustomOrder(): array
    {
        return [1, 'desc'];
    }


    // Buttons
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

        // if (auth()->user()?->can('versions.delete')) {
        $buttons[] = [
            'text'      => 'حذف المحدد',
            'className' => 'btn btn-default btn-delete-selected',
            'action'    => "function() { handleMassDelete(); }",
        ];
        // }

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        // if (!auth()->user()?->can('إضافة بنك')) {
        //     return [];
        // }

        return [
            'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة بنك',
            'className' => 'btn btn-primary btn-add',
            'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
        ];
    }


    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    private function StageBadge($stage): string
    {
        return sprintf(
            '<span class="badge  bg-%s">%s</span>',
            $stage->color(),
            $stage->label()
        );
    }
}
