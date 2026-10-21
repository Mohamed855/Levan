<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Archive\Properties;

use App\Actions\Tenant\Archive\ManageTenantArchiveAction;
use App\DataTables\Tenant\Archive\Properties\PropertyArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenant\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

final class PropertyArchiveController extends Controller
{
    public function __construct(private readonly ManageTenantArchiveAction $archive)
    {
        $this->middleware('can:archives.view')->only('index');
        $this->middleware('can:archives.restore')->only('restore');
        $this->middleware('can:archives.force_delete')->only('forceDelete');
    }

    public function index(PropertyArchiveDataTable $dataTable)
    {
        return $dataTable->render('tenant.archives.properties.index');
    }

    public function restore(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $property = Property::onlyTrashed()->findOrFail($id);
        $this->archive->restore($property);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم استرجاع العقار بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم استرجاع العقار بنجاح.');
    }

    public function forceDelete(Request $request, int $id): RedirectResponse|JsonResponse
    {
        $property = Property::onlyTrashed()->findOrFail($id);

        if ($property->auctions()->exists()) {
            $msg = 'لا يمكن الحذف النهائي للعقار لوجود مزادات مرتبطة به.';
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => $msg], 422);
            }

            return redirect()->back()->with('error', $msg);
        }

        $this->archive->forceDelete($property);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف العقار نهائياً بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف العقار نهائياً بنجاح.');
    }
}
