<?php

namespace App\Http\Controllers\Tenants\Archive\Property;

use App\Actions\Tenants\Properties\ForceDeletePropertyAction;
use App\Actions\Tenants\Properties\RestorePropertyAction;
use App\DataTables\Tenants\Properties\PropertyArchiveDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Archive\ArchiveItemIdRequest;
use App\Models\Tenants\Properties\Property;

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
        return $dataTable->render('tenant.archive.properties.index');
    }

    public function restore(ArchiveItemIdRequest $request, RestorePropertyAction $action)
    {
        $property = Property::onlyTrashed()->findOrFail($request->validated('id'));
        $this->authorize('restore', $property);

        try {
            $action->execute($property);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'تم استعادة العقار بنجاح']);
            }

            return redirect()->back()->with('success', 'تم استعادة العقار بنجاح');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'حدث خطأ أثناء استعادة العقار'], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء استعادة العقار');
        }
    }

    public function forceDelete(ArchiveItemIdRequest $request, ForceDeletePropertyAction $action)
    {
        $property = Property::onlyTrashed()->findOrFail($request->validated('id'));
        $this->authorize('forceDelete', $property);

        try {
            $action->execute($property);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف العقار نهائياً بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم حذف العقار نهائياً بنجاح');
        } catch (\LogicException $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
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
