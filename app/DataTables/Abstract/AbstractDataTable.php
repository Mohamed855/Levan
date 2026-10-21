<?php

declare(strict_types=1);

namespace App\DataTables\Abstract;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Services\DataTable;

abstract class AbstractDataTable extends DataTable
{
    /*
    |============================================================================
    |                            Abstract Methods
    |============================================================================
    */
    abstract protected function getTableId(): string;

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

        if (in_array($action, $this->actions, true) && method_exists($this, $actionMethod)) {
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

    // معالجة البيانات اذا كانت Builder
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

    // معالجة البيانات اذا كانت collection
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
    // تطبيق البحث العام في جميع الأعمدة القابلة للبحث
    protected function applyGlobalSearch($instance): void
    {
        if (request()->has('search') && ! empty(request('search.value'))) {
            $searchValue = request('search.value');
            $searchableColumns = $this->getSearchableColumns();

            if (! empty($searchableColumns)) {
                $instance->where(function ($query) use ($searchableColumns, $searchValue) {
                    foreach ($searchableColumns as $column) {
                        $this->addSearchCondition($query, $column, $searchValue, 'or');
                    }
                });
            }
        }
    }

    protected function addSearchCondition($query, $column, $searchValue, $operator = 'or'): void
    {
        if (str_contains($column, '.')) {
            // البحث في العلاقة
            [$relation, $field] = explode('.', $column, 2);

            $method = $operator === 'or' ? 'orWhereHas' : 'whereHas';

            $query->$method($relation, function ($relationQuery) use ($field, $searchValue) {
                if (str_contains($field, '.')) {
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
    protected function addCustomColumns($datatable): void {}

    protected function formatRows(Collection $rows): Collection
    {
        return $rows;
    }

    protected function rawColumns(): array
    {
        return [];
    }

    protected function renderTableImage(?string $src, string $alt = '', int $size = 44): string
    {
        return view('components.shared.data-table.image', [
            'src' => $src,
            'alt' => $alt,
            'size' => $size,
        ])->render();
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
        return '<"dt-toolbar"'.
            '<"dt-toolbar-left d-flex flex-wrap align-items-center gap-2" f l >'.
            '<"dt-toolbar-right d-flex flex-wrap align-items-center gap-2" B >'.
            '>'.
            'r<"table-responsive"t>'.
            '<"row mt-2 align-items-center"'.
            '<"d-flex flex-wrap align-items-center justify-content-between gap-2 w-100" i <"pagination-wrapper" p > >'.
            '>';
    }

    protected function buttons(): array
    {
        return [];
    }

    protected function parameters(): array
    {
        $params = [
            'processing' => true,
            'serverSide' => true,
            'language' => [
                'url' => asset('static/i18n/ar.json'),
                'processing' => 'جارٍ تحميل البيانات…',
                'emptyTable' => 'لا توجد بيانات لعرضها.',
                'zeroRecords' => 'لا توجد نتائج مطابقة.',
            ],
            'pageLength' => 10,
            'orderCellsTop' => true,
            'columnDefs' => [
                [
                    'targets' => '_all',
                    'defaultContent' => '-',
                    'render' => 'function(data, type, full, meta) {
                        if (type === "display") {
                            if (data === null || data === undefined || data === "" || (typeof data === "string" && data.trim() === "")) {
                                return "-";
                            }
                        }
                        return data;
                    }',
                ],
            ],
            'responsive' => false,
        ];

        $customOrder = $this->getCustomOrder();
        if (! empty($customOrder)) {
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
        return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
    }

    protected function emptyDataMessage($message = '-'): string
    {
        return '<span class="text-muted">'.($message ?? '-').'</span>';
    }

    protected function routeName($route, $name, $class = '', $target = ''): string
    {
        return '<a href="'.$route.'" class="'.$class.'" target="'.$target.'">'.$name.'</a>';
    }
}
