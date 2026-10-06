<?php

namespace App\Http\Controllers\Tenants\Archive\Property;

use App\DataTables\Tenants\Properties\PropertyArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Models\Tenants\Properties\Property;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class PropertyArchiveController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:tenant');
        $this->middleware('can:properties_archive.view')->only(['index']);
        $this->middleware('can:properties_archive.restore')->only(['restore']);
        $this->middleware('can:properties_archive.force_delete')->only(['forceDelete']);
    }

    public function index(PropertyArchiveDataTable $dataTable)
    {
        return $dataTable->render('tenants.archive.properties.index', [
            'totalArchived' => Property::onlyTrashed()->count(),
        ]);
    }

    public function restore(int $id): RedirectResponse
    {
        try {
            $property = Property::onlyTrashed()->findOrFail($id);
            $property->restore();

            return redirect()->back()->with('success', 'تم استعادة العقار بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء استعادة العقار');
        }
    }

    public function forceDelete(int $id)
    {
        try {
            $property = Property::onlyTrashed()->findOrFail($id);
            $property->forceDelete();

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف العقار نهائياً بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم حذف العقار نهائياً بنجاح');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف العقار نهائياً',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف العقار نهائياً');
        }
    }
}
