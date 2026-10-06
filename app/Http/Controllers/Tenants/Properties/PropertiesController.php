<?php

namespace App\Http\Controllers\Tenants\Properties;

use App\DataTables\Tenants\Properties\PropertiesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Properties\PropertyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PropertiesController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService
    ) {
        $this->middleware('auth:tenant');
        $this->middleware('can:properties.view')->only(['index', 'show']);
        $this->middleware('can:properties.create')->only(['create', 'store']);
        $this->middleware('can:properties.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:properties.delete')->only(['destroy']);
    }

    public function index(PropertiesDataTable $dataTable)
    {
        $statistics = $this->propertyService->getStatistics();
        $assignments = Assignment::select(['id', 'name', 'assignment_number'])->get();

        return $dataTable->render('tenants.properties.index', [
            'totalProperties'      => $statistics['total'],
            'availableProperties'  => $statistics['available'],
            'assignedProperties'   => $statistics['assigned_to_auction'],
            'completedProperties'  => $statistics['completed'],
            'assignments'          => $assignments,
        ]);
    }

    public function create(): View
    {
        $assignments = Assignment::where('is_active', true)->select(['id', 'name', 'assignment_number'])->get();
        $generatedPropertyNumber = $this->propertyService->generatePropertyNumber();

        return view('tenants.properties.create', [
            'assignments'             => $assignments,
            'generatedPropertyNumber' => $generatedPropertyNumber,
        ]);
    }

    public function store(StorePropertyRequest $request): RedirectResponse
    {
        try {
            $this->propertyService->createProperty(
                $request->validated(),
                $request->file('main_image')
            );

            return redirect()->to(tenant_route_name('properties.index'))
                ->with('success', 'تم إضافة العقار بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة العقار');
        }
    }

    public function show(Property $property): View
    {
        $property->load(['assignment', 'auctions']);

        return view('tenants.properties.show', [
            'property' => $property,
        ]);
    }

    public function edit(Property $property): View
    {
        $assignments = Assignment::select(['id', 'name', 'assignment_number'])->get();

        return view('tenants.properties.edit', [
            'property'    => $property,
            'assignments' => $assignments,
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property): RedirectResponse
    {
        try {
            $this->propertyService->updateProperty(
                $property,
                $request->validated(),
                $request->file('main_image')
            );

            return redirect()->to(tenant_route_name('properties.index'))
                ->with('success', 'تم تحديث العقار بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث العقار');
        }
    }

    public function destroy(Property $property)
    {
        try {
            $this->propertyService->deleteProperty($property);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم نقل العقار إلى المحذوفات بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم نقل العقار إلى المحذوفات بنجاح');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف العقار',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف العقار');
        }
    }

    public function toggleStatus(UpdateResourceStatusRequest $request, Property $property): JsonResponse
    {
        try {
            $newStatus = $this->propertyService->toggleStatus(
                $property,
                $request->validated('status') === 'active',
            );

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'تم تفعيل العقار' : 'تم تعطيل العقار',
                'status'  => $newStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل الحالة',
            ], 500);
        }
    }
}
