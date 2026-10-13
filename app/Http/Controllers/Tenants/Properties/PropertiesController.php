<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Properties;

use App\Actions\Tenants\Properties\CreatePropertyAction;
use App\Actions\Tenants\Properties\UpdatePropertyAction;
use App\DataTables\Tenants\Properties\PropertiesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Properties\SearchPropertyLocationRequest;
use App\Http\Requests\Tenants\Properties\StorePropertyRequest;
use App\Http\Requests\Tenants\Properties\UpdatePropertyRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Properties\GooglePlacesService;
use App\Services\Tenants\Properties\PropertyService;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class PropertiesController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService,
        protected CentralCityOptionsGateway $cityOptionsGateway
    ) {
        $this->middleware('auth:tenant');
        $this->authorizeResource(Property::class, 'property');
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
            'totalProperties' => $statistics['total'],
            'activeProperties' => $statistics['active'],
            'completedProperties' => $statistics['completed'],
            'unlinkedProperties' => $statistics['unlinked'],
            'needsReviewProperties' => $statistics['needs_review'],
            'assignments' => $assignments,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function create(): View
    {
        $assignments = Assignment::where('is_active', true)->select(['id', 'name', 'assignment_number'])->get();
        $generatedPropertyNumber = $this->propertyService->generatePropertyNumber();

        return view('tenants.properties.create', [
            'assignments' => $assignments,
            'generatedPropertyNumber' => $generatedPropertyNumber,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function store(StorePropertyRequest $request, CreatePropertyAction $createAction): RedirectResponse
    {
        try {
            $createAction->execute(
                $request->validated(),
                (int) auth('tenant')->id()
            );

            return redirect()->to(tenant_route_name('properties.index'))
                ->with('success', 'تم إضافة العقار بنجاح');
        } catch (\Exception $e) {
            Log::error('Property creation failed.', ['exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة العقار: '.$e->getMessage());
        }
    }

    public function show(Property $property): View
    {
        $property->load(['assignment', 'auctions', 'boundaries', 'owners', 'units', 'attachments', 'creator']);

        return view('tenants.properties.show', [
            'property' => $property,
        ]);
    }

    public function edit(Property $property): View
    {
        $property->load(['boundaries', 'owners', 'units', 'attachments', 'assignment']);
        $assignments = Assignment::select(['id', 'name', 'assignment_number'])->get();

        return view('tenants.properties.edit', [
            'property' => $property,
            'assignments' => $assignments,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property, UpdatePropertyAction $updateAction): RedirectResponse
    {
        try {
            $updateAction->execute(
                $property,
                $request->validated()
            );

            return redirect()->to(tenant_route_name('properties.index'))
                ->with('success', 'تم تحديث العقار بنجاح');
        } catch (\Exception $e) {
            Log::error('Property update failed.', ['exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث العقار: '.$e->getMessage());
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
                'status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل الحالة',
            ], 500);
        }
    }

    public function searchLocation(SearchPropertyLocationRequest $request, GooglePlacesService $placesService): JsonResponse
    {
        try {
            $query = (string) $request->validated('query');
            $lat = $request->validated('latitude') !== null ? (float) $request->validated('latitude') : null;
            $lng = $request->validated('longitude') !== null ? (float) $request->validated('longitude') : null;

            $places = $placesService->search($query, $lat, $lng);

            return response()->json([
                'success' => true,
                'places' => $places,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Property location search failed: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'تعذر البحث عن الموقع حالياً: '.$e->getMessage(),
                'places' => [],
            ], 500);
        }
    }
}
