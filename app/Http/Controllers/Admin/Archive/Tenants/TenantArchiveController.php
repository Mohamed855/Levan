<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Archive\Tenants;

use App\Actions\Admin\Archive\ManageCentralArchiveAction;
use App\DataTables\Admin\Archive\Tenants\TenantArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class TenantArchiveController extends Controller
{
    public function __construct(private readonly ManageCentralArchiveAction $archive)
    {
        $this->middleware('can:tenants.view')->only('index');
        $this->middleware('can:tenants.delete')->only(['restore', 'forceDelete']);
    }

    public function index(TenantArchiveDataTable $dataTable)
    {
        return $dataTable->render('admin.archives.tenants.index');
    }

    public function restore(Request $request, string|int $id): RedirectResponse|JsonResponse
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);
        $this->archive->restore($tenant);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم استرجاع المستأجر بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم استرجاع المستأجر بنجاح.');
    }

    public function forceDelete(Request $request, string|int $id): RedirectResponse|JsonResponse
    {
        $tenant = Tenant::onlyTrashed()->findOrFail($id);
        $this->archive->forceDelete($tenant);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستأجر نهائياً بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف المستأجر نهائياً بنجاح.');
    }
}
