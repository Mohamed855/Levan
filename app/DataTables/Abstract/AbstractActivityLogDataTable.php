<?php

declare(strict_types=1);

namespace App\DataTables\Abstract;

use App\Services\Shared\ActivityLog\ActivityLogService;
use Spatie\Activitylog\Models\Activity;

abstract class AbstractActivityLogDataTable extends AbstractDataTable
{
    public function __construct(
        protected readonly ActivityLogService $activityLogService,
    ) {}

    abstract protected function actionView(): string;

    abstract protected function logNameColumnTitle(): string;

    protected function getTableId(): string
    {
        return 'activity-logs-table';
    }

    protected function resource()
    {
        return Activity::query()
            ->with(['causer'])
            ->select([
                'id',
                'log_name',
                'description',
                'causer_id',
                'causer_type',
                'created_at',
            ]);
    }

    protected function applyCustomFilters($query)
    {
        return $this->activityLogService->applyFilters(
            $query,
            request()->only(['user_id', 'log_name'])
        );
    }

    protected function getColumns(): array
    {
        return [
            ['data' => 'checkbox', 'name' => 'checkbox', 'title' => '<input type="checkbox" id="select-all">', 'orderable' => false, 'searchable' => false, 'className' => 'custom-checkbox text-center align-middle'],
            ['data' => 'id', 'name' => 'id', 'title' => 'ID', 'visible' => false],
            ['data' => 'log_name_formatted', 'name' => 'log_name', 'title' => $this->logNameColumnTitle(), 'orderable' => false, 'searchable' => false, 'className' => 'text-center align-middle'],
            ['data' => 'description', 'name' => 'description', 'title' => 'الوصف', 'className' => 'text-center align-middle'],
            ['data' => 'causer_name', 'name' => 'causer_name', 'title' => 'المستخدم', 'orderable' => false, 'searchable' => false, 'className' => 'text-center align-middle'],
            ['data' => 'created_at', 'name' => 'created_at', 'title' => 'التاريخ', 'className' => 'text-center align-middle', 'width' => '160px'],
            ['data' => 'actions', 'name' => 'actions', 'title' => 'الإجراءات', 'orderable' => false, 'searchable' => false, 'className' => 'text-center align-middle', 'width' => '100px'],
        ];
    }

    protected function addCustomColumns($datatable)
    {
        $datatable
            ->addColumn('checkbox', fn (Activity $row): string => $this->checkbox($row))
            ->addColumn('log_name_formatted', fn (Activity $row): string => $this->activityLogService->formatLogNameBadge($row->log_name))
            ->editColumn('description', fn (Activity $row): string => e($row->description ?: '-'))
            ->addColumn('causer_name', fn (Activity $row): string => e($row->causer?->name ?? 'النظام'))
            ->editColumn('created_at', fn (Activity $row): string => $this->formatDateTime($row->created_at))
            ->addColumn('actions', fn (Activity $row): string => view($this->actionView(), ['row' => $row])->render());
    }

    protected function rawColumns(): array
    {
        return ['checkbox', 'log_name_formatted', 'actions'];
    }

    protected function getSearchableColumns(): array
    {
        return ['description', 'log_name'];
    }

    protected function getCustomOrder(): array
    {
        return [5, 'desc'];
    }
}
