<?php

declare(strict_types=1);

namespace App\DataTables\Admin\ActivityLog;

use App\DataTables\Abstract\AbstractActivityLogDataTable;

class AdminActivityLogDataTable extends AbstractActivityLogDataTable
{
    protected function actionView(): string
    {
        return 'admin.activity-logs.action';
    }

    protected function logNameColumnTitle(): string
    {
        return 'القسم';
    }

    protected function buttons(): array
    {
        $selectedRowsFilter = 'function (idx, data, node) { var table = node && node.closest("table"); var hasChecked = table ? table.querySelectorAll(".row-checkbox:checked").length > 0 : false; return hasChecked ? Boolean(node.querySelector(".row-checkbox:checked")) : true; }';

        return [
            [
                'extend' => 'collection',
                'className' => 'btn btn-export',
                'text' => 'الإجراءات',
                'buttons' => [
                    [
                        'extend' => 'copy',
                        'text' => 'نسخ',
                        'exportOptions' => [
                            'columns' => [2, 3, 4, 5],
                            'rows' => $selectedRowsFilter,
                        ],
                    ],
                    [
                        'extend' => 'excel',
                        'text' => 'إكسل',
                        'exportOptions' => [
                            'columns' => [5, 4, 3, 2],
                            'rows' => $selectedRowsFilter,
                        ],
                    ],
                ],
            ],
        ];
    }
}
