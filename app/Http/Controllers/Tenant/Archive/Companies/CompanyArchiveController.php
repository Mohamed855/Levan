<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Archive\Companies;

use App\Actions\Tenant\Archive\ManageTenantArchiveAction;
use App\DataTables\Tenant\Archive\Companies\CompanyArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class CompanyArchiveController extends Controller
{
    public function __construct(private readonly ManageTenantArchiveAction $archive)
    {
        $this->middleware('can:archives.view')->only('index');
        $this->middleware('can:archives.restore')->only('restore');
        $this->middleware('can:archives.force_delete')->only('forceDelete');
    }

    public function index(CompanyArchiveDataTable $dataTable)
    {
        return $dataTable->render('tenant.archives.companies.index');
    }

    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $this->archive->restore($company);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم استرجاع الشركة بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم استرجاع الشركة بنجاح.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $company = Company::onlyTrashed()->findOrFail($id);
        $this->archive->forceDelete($company);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الشركة نهائياً بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف الشركة نهائياً بنجاح.');
    }
}
