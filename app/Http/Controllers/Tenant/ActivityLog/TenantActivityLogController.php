<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\ActivityLog;

use App\DataTables\Tenant\ActivityLog\TenantActivityLogDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenant\ActivityLog;
use Illuminate\View\View;

final class TenantActivityLogController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:activity_log.view');
    }

    public function index(TenantActivityLogDataTable $dataTable)
    {
        return $dataTable->render('tenant.activity-log.index', [
            'stats' => $dataTable->getStats(),
        ]);
    }

    public function show(int $id): View
    {
        $log = ActivityLog::query()->with('causer')->findOrFail($id);

        return view('tenant.activity-log.show', [
            'log' => $log,
        ]);
    }
}
