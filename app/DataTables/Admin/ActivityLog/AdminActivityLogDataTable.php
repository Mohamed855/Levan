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
        return $this->actionsCollectionButton([
            $this->copyActionButton(),
            [
                'extend' => 'excel',
                'text' => 'إكسل',
                'exportOptions' => [
                    'columns' => [5, 4, 3, 2],
                    'rows' => $this->selectedRowsFilterCallback(),
                ],
            ],
        ]);
    }
}
