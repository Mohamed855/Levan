<?php

namespace App\DataTables\Admin\Setting;


use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\ControlDomain\ControlDomain;

class SettingDomainDataTable extends AbstractDataTable
{
    private $page  = "admin.settings.domains";
    private $route = "admin.settings.domains";


    protected function getTableId()
    {
        return "domain-setting-table";
    }


    protected function resource()
    {
        return ControlDomain::query();
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
            ['data' => 'domain_extension',  'name' => 'domain_extension',   'title' => 'امتداد الدومين',   'className' => 'text-start'],
            ['data' => 'price',             'name' => 'price',              'title' => 'السعر',             'className' => 'text-start'],
            ['data' => 'is_active',         'name' => 'is_active',          'title' => 'الحالة',],
            ['data' => 'created_at',         'name' => 'created_at',          'title' => 'تاريخ الاضافة',],
            ['data' => 'actions',           'name' => 'actions',            'title' => 'الإجراءات',         'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }




    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            ->editColumn('is_active', function ($row) {
                return $row->is_active
                    ? '<span class="badge bg-success">مفعل</span>'
                    : '<span class="badge bg-danger">غير مفعل</span>';
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at?->format('Y-m-d');
            })

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'code', 'discount_type', 'is_active', 'status', 'actions'];
    }


    // protected function applyCustomFilters($query)
    // {
    //     $request = request();

    //     if ($request->filled('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     if ($request->filled('authorized_count')) {
    //         switch ($request->authorized_count) {
    //             case '0':
    //                 $query->has('authorizedPeople', '=', 0);
    //                 break;
    //             case '1':
    //                 $query->has('authorizedPeople', '=', 1);
    //                 break;
    //             case '2-5':
    //                 $query->has('authorizedPeople', '>=', 2)
    //                     ->has('authorizedPeople', '<=', 5);
    //                 break;
    //             case '6+':
    //                 $query->has('authorizedPeople', '>=', 6);
    //                 break;
    //         }
    //     }

    //     if ($request->filled('search_query')) {
    //         $searchTerm = $request->search_query;
    //         $query->where(function ($q) use ($searchTerm) {
    //             $q->where('name', 'like', "%{$searchTerm}%")
    //                 ->orWhereHas('authorizedPeople', function ($qq) use ($searchTerm) {
    //                     $qq->where('name', 'like', "%{$searchTerm}%")
    //                         ->orWhere('phone', 'like', "%{$searchTerm}%")
    //                         ->orWhere('email', 'like', "%{$searchTerm}%")
    //                         ->orWhere('position', 'like', "%{$searchTerm}%");
    //                 });
    //         });
    //     }

    //     return $query;
    // }


    // الحقول المسموح البحث فيها
    protected function getSearchableColumns(): array
    {
        return [
            'domain_extension',
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

        // if (auth()->user()?->can('حذف كوبون')) {
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
        // if (!auth()->user()?->can('إضافة كوبون')) {
        //     return [];
        // }

        return [
            'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة امتداد',
            'className' => 'btn btn-primary btn-add',
            'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
        ];
    }
}