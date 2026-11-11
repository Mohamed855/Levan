<?php

declare(strict_types=1);

namespace App\DataTables\Tenants\ActivityLog;

use App\DataTables\Abstract\AbstractActivityLogDataTable;

class TenantActivityLogDataTable extends AbstractActivityLogDataTable
{
    protected function actionView(): string
    {
        return 'tenant.activity-logs.action';
    }

    protected function logNameColumnTitle(): string
    {
        return 'نوع النشاط';
    }

    protected function buttons(): array
    {
        return $this->actionsCollectionButton([
            $this->copyActionButton(),
            [
                'text' => 'إكسل',
                'action' => 'function() { handleExportActivityLogs(); }',
            ],
        ]);
    }
}
