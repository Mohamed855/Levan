<?php
namespace App\DataTables\Admin\ContentManagement\Client;

use App\DataTables\Abstract\AbstractDataTable;
use App\Models\Admin\ContentManagement\Client;

class ClientDataTable extends AbstractDataTable
{
    private $page  = "admin.content_management.client";
    private $route = "admin.content_management.client";


    // table id
    protected function getTableId()
    {
        return "client-table";
    }


    protected function resource()
    {
        return Client::query();
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
            ['data' => 'name',     'name' => 'name',   'title' => 'اسم العميل',   'className' => 'text-start'],
            ['data' => 'logo',     'name' => 'logo',   'title' => 'الشعار',          'className' => 'text-start'],
            ['data' => 'status',         'name' => 'status',          'title' => 'الحالة',],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'تاريخ الإنشاء', 'className' => 'text-start'],
            ['data' => 'actions',           'name' => 'actions',            'title' => 'الإجراءات',      'orderable' => false, 'searchable' => false, 'width' => '120px'],
        ];
    }




    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', function ($row) {
                return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
            })

            // ->editColumn('code', function ($row) {
            //     return $this->copyableCode($row);
            // })


            // ->editColumn('discount_type', function ($row) {
            //     return $this->DiscountTypeBadge($row);
            // })

            ->editColumn('logo', function ($row) {
                if ($row->logo) {
                    return '<img src="' . asset('storage/' . $row->logo) . '" alt="' . $row->name . '" style="max-width: 60px; max-height: 40px; border-radius: 4px; object-fit: cover;">';
                }
                return '<span class="badge bg-secondary">بدون شعار</span>';
            })

            ->editColumn('status', function ($row) {
                $badgeClass = $row->status ? 'bg-success' : 'bg-danger';
                $badgeText = $row->status ? 'نشط' : 'غير نشط';
                
                return sprintf(
                    '<span class="status-badge badge %s" data-id="%d" data-name="%s" style="cursor: pointer; padding: 6px 12px; font-weight: 600; user-select: none;" title="اضغط لتغيير الحالة">%s</span>',
                    $badgeClass,
                    $row->id,
                    htmlspecialchars($row->name),
                    $badgeText
                );
            })

            ->editColumn('created_at', function ($row) {
                return $row->created_at ? $row->created_at->format('d/m/Y') : '-';
            })

            ->addColumn('actions', function ($row) {
                return view($this->page . '.action', ['row' => $row, 'route' => $this->route])->render();
            });
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'code', 'discount_type', 'logo', 'status', 'created_at', 'actions'];
    }
    
     protected function applyCustomFilters($query)
    {
        $request = request();
   
        if ($request->filled('status')) {
            $val = $request->status;
            if ($val === 'active' || $val === '1' || $val === 1) {
                $query->where('status', 1);
            } elseif ($val === 'inactive' || $val === '0' || $val === 0) {
                $query->where('status', 0);
            } else {
                $query->where('status', $val);
            }
        }

        return $query;
    }


    // الحقول المسموح البحث فيها
    protected function getSearchableColumns(): array
    {
        return [
            'name',
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
                ['extend' => 'excel', 'text' => 'إكسل' , 'exportOptions' => [
                    'columns' => [6, 5, 4, 3, 2]
                ]],
        ];

            $buttons[] = [
                'text'      => 'حذف المحدد',
                'className' => 'btn btn-default btn-delete-selected',
                'action'    => "function() { handleMassDelete(); }",
            ];

        return [
            'extend'    => 'collection',
            'className' => 'btn btn-export',
            'text'      => 'الإجراءات',
            'buttons'   => $buttons,
        ];
    }

    private function getAddButton(): array
    {
        return [
            'text'      => '<i class="fas fa-plus-circle me-1"></i> إضافة عميل',
            'className' => 'btn btn-primary btn-add',
            'action'    => "function(){ window.location.href='" . route($this->route . '.create') . "'; }",
        ];
    }


    /*
    |============================================================================
    |                            Private Helper Methods
    |============================================================================
    */
    // private function copyableCode($row): string
    // {
    //     return sprintf(
    //         '<div class="d-flex align-items-center gap-2">
    //             <code class="copyable-code"
    //                   data-code="%s"
    //                   style="cursor: pointer; user-select: none; padding: 4px 8px; background: #f5f5f5; border-radius: 4px; font-weight: 600;"
    //                   title="اضغط للنسخ">
    //                 %s
    //             </code>
    //             <i class="ti ti-copy text-muted" style="font-size: 16px;"></i>
    //         </div>',
    //         $row->code,
    //         $row->code
    //     );
    // }


    // private function DiscountTypeBadge($row): string
    // {
    //     return sprintf(
    //         '<span class="badge  bg-%s">%s</span>',
    //         $row->discount_type->color(),
    //         $row->discount_type->label()
    //     );
    // }
}
