<?php

namespace App\DataTables\Abstract;

use Illuminate\Support\Collection;
use Yajra\DataTables\Services\DataTable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

abstract class AbstractDataTable extends DataTable
{
    /*
    |============================================================================
    |                            Abstract Methods
    |============================================================================
    */
    abstract protected function getTableId();
    abstract protected function resource();
    abstract protected function getColumns(): array;

    /*
    |============================================================================
    |                         DataTable Initialization
    |============================================================================
    */
    public function html()
    {
        return $this->builder()
            ->setTableId($this->getTableId())
            ->columns($this->getColumns())
            ->minifiedAjax(url()->current())
            ->processing(true)
            ->serverSide(true)
            ->dom($this->dom())
            ->buttons($this->buttons())
            ->parameters($this->parameters());
    }

    public function render(?string $view = null, array $data = [], array $mergeData = [])
    {
        $action = $this->request()->action;
        $actionMethod = $action === 'print' ? 'printPreview' : $action;

        if (in_array($action, $this->actions) && method_exists($this, $actionMethod)) {
            $callback = [$this, $actionMethod];

            return app()->call($callback);
        }

        if ($this->request()->ajax() || $this->request()->wantsJson() || $this->request()->has('draw')) {
            return app()->call($this->ajax(...));
        }

        return parent::render($view, $data, $mergeData);
    }

    // دالة لتطبيق الفلاتر المخصصة - يمكن للكلاسات الفرعية تخصيصها
    protected function applyCustomFilters($query)
    {
        return $query;
    }

    public function query()
    {
        $baseQuery = $this->resource();
        return $this->applyCustomFilters($baseQuery);
    }


    /*
    |============================================================================
    |                         DataTable Construction
    |============================================================================
    */
    public function dataTable($query)
    {
        if ($query instanceof Builder) {
            return $this->buildEloquentDataTable($query);
        }

        return $this->buildCollectionDataTable($query);
    }

    // معالجة البيانات اذاكانت Builder
    protected function buildEloquentDataTable($query)
    {
        $datatable = datatables()
            ->eloquent($query)
            ->addIndexColumn();

        $this->addCustomColumns($datatable);

        return $datatable
            ->rawColumns($this->rawColumns())
            ->filter(function ($instance) {
                $this->applyGlobalSearch($instance);
            });
    }

    // معالجة البيانات اذاكانت collection
    protected function buildCollectionDataTable($query)
    {
        $rows = $this->formatRows($query);
        return datatables()
            ->collection($rows)
            ->addIndexColumn()
            ->rawColumns($this->rawColumns());
    }


    /*
    |============================================================================
    |                           Global Search
    |============================================================================
    */
    //تطبيق البحث العام في جميع الأعمدة القابلة للبحث
    protected function applyGlobalSearch($instance)
    {
        if (request()->has('search') && !empty(request('search.value'))) {
            $searchValue = request('search.value');
            $searchableColumns = $this->getSearchableColumns();

            if (!empty($searchableColumns)) {
                $instance->where(function ($query) use ($searchableColumns, $searchValue) {
                    foreach ($searchableColumns as $column) {
                        $this->addSearchCondition($query, $column, $searchValue, 'or');
                    }
                });
            }
        }
    }

    protected function addSearchCondition($query, $column, $searchValue, $operator = 'or')
    {
        if (strpos($column, '.') !== false) {
            // البحث في العلاقة
            [$relation, $field] = explode('.', $column, 2);

            $method = $operator === 'or' ? 'orWhereHas' : 'whereHas';

            $query->$method($relation, function ($relationQuery) use ($field, $searchValue) {
                if (strpos($field, '.') !== false) {
                    // علاقة متداخلة (مثل department.name)
                    $this->addSearchCondition($relationQuery, $field, $searchValue, 'and');
                } else {
                    // علاقة مباشرة
                    $relationQuery->where($field, 'LIKE', "%{$searchValue}%");
                }
            });
        } else {
            // البحث في الجدول الرئيسي
            $method = $operator === 'or' ? 'orWhere' : 'where';
            $query->$method($column, 'LIKE', "%{$searchValue}%");
        }
    }


    /*
    |============================================================================
    |                          Request Filters
    |============================================================================
    */
    // لتطبيق الفلاتر من الطلب على الاستعلام
    protected function applyRequestFilters(Builder $q, array $fields): Builder
    {
        Log::info('Applying request filters', ['fields' => $fields]);
        foreach ($fields as $field) {
            if ($val = request($field)) {
                $q->where($field, $val);
            }
        }

        return $q;
    }

    /*
    |============================================================================
    |                          Helper Methods
    |============================================================================
    */
    // للتعامل مع تعديلات الحقول و  الحقول الاضافية
    
    protected function addCustomColumns($datatable) {}

    protected function formatRows(Collection $rows): Collection
    {
        return $rows;
    }

    protected function rawColumns(): array
    {
        return [];
    }

    protected function getButtons(): array
    {
        return [];
    }

    // لتحديد الحقول المسموح البحث فيها - اذا كان هناك علاقات
    protected function getSearchableColumns(): array
    {
        return $this->filterableColumns();
    }

    // لتحديد الحقول المسموح البحث فيها - اذا لم يكن هناك علاقات
    protected function filterableColumns(): array
    {
        return [];
    }

    protected function getCustomOrder(): array
    {
        return [];
    }
    /*
    |============================================================================
    |                          Default Settings Methods
    |============================================================================
    */
    protected function dom(): string
    {
        return  '<"dt-toolbar"' .
            '<"dt-toolbar-left d-flex flex-column flex-sm-row align-items-start align-items-sm-center" f l >' .
            '<"dt-toolbar-right d-flex flex-column flex-sm-row align-items-end align-items-sm-center justify-content-between" B >' .
            '>' .
            'rt' .
            '<"row mt-3"' .
            '<"d-flex flex-column flex-sm-row align-items-center justify-content-between w-100" i <"pagination-wrapper overflow-auto w-100" p >' .
            '>' .
            '>';
    }


    /*
    |--------------------------------------------------------------------------
    | Define DataTable Buttons (Placeholder)
    |--------------------------------------------------------------------------
    | This method acts as a placeholder. Child classes MUST override this
    | method to define their own complete button structure.
    */
    protected function buttons(): array
    {
        return [];
    }


    protected function parameters(): array
    {
        $params = [
            'processing' => true,
            'serverSide' => true,
            'language'   => ['url' => asset('assets/json/ar.json')],
            'pageLength' => 10,

            'orderCellsTop' => true,
            'columnDefs' => [
                [
                    'className' => 'control',
                    'orderable' => false,
                    'targets' => 0,
                    'render' => 'function(data, type, full, meta) { return ""; }'
                ]
            ],

            'responsive' => [
                'details' => [
                    'display' => 'function(row, update, render, closeCallback) {
                    if (typeof window.bootstrap !== "undefined" && window.bootstrap && typeof $.fn.dataTable.Responsive.display.modal === "function") {
                        try {
                            return $.fn.dataTable.Responsive.display.modal({
                                header: function(r) {
                                    return "التفاصيل";
                                }
                            })(row, update, render, closeCallback);
                        } catch (e) {
                            return $.fn.dataTable.Responsive.display.childRow(row, update, render, closeCallback);
                        }
                    }
                    return $.fn.dataTable.Responsive.display.childRow(row, update, render, closeCallback);
                }',
                    'type' => 'column',
                    'renderer' => 'function(api, rowIdx, columns) {
                    var data = $.map(columns, function(col, i) {
                        return col.title !== "" && col.title.indexOf("checkbox") === -1
                            ? \'<tr data-dt-row="\' + col.rowIndex + \'" data-dt-column="\' + col.columnIndex + \'"><td>\' + col.title + \':</td><td>\' + col.data + \'</td></tr>\'
                            : "";
                    }).join("");
                    return data ? $(\'<table class="table"/><tbody />\').append(data) : false;
                }'
                ]
            ],
        ];

        $customOrder = $this->getCustomOrder();
        if (!empty($customOrder)) {
            $params['order'] = [$customOrder];
        }

        return $params;
    }

    /*
    |============================================================================
    |                            Additional Methods
    |============================================================================
    */
    protected function checkbox($row): string
    {
        return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
    }

    protected function emptyDataMessage($message = null): string
    {
        return '<span class="text-muted">' . ($message ?? 'غير متوفر') . '</span>';
    }

    protected function routeName($route, $name, $class = '', $target = ""): string
    {
        return '<a href="' . $route . '" class="' . $class . '" target="' . $target . '">' . $name . '</a>';
    }
}
