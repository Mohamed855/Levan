<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Properties;

use App\Actions\Tenant\Properties\SavePropertyAction;
use App\DataTables\Tenant\Properties\PropertyDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Properties\StorePropertyRequest;
use App\Http\Requests\Tenant\Properties\UpdatePropertyRequest;
use App\Http\Requests\Tenant\Properties\UpdatePropertyStatusRequest;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use App\Services\Tenants\Properties\PropertyService;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class PropertyController extends Controller
{
    public function __construct(
        protected PropertyService $propertyService,
        protected CentralCityOptionsGateway $cityOptionsGateway
    ) {
        $this->middleware('can:properties.view')->only(['index', 'show']);
        $this->middleware('can:properties.create')->only(['create', 'store']);
        $this->middleware('can:properties.edit')->only(['edit', 'update', 'toggleStatus', 'destroy']);
    }

    public function index(PropertyDataTable $dataTable)
    {
        $statistics = $this->propertyService->getStatistics();
        $assignments = Assignment::select(['id', 'name', 'assignment_number'])->get();

        return $dataTable->render('tenant.properties.index', [
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
        $this->authorize('create', Property::class);

        $assignments = Assignment::where('is_active', true)->select(['id', 'name', 'assignment_number'])->get();
        $generatedPropertyNumber = $this->propertyService->generatePropertyNumber();

        return view('tenant.properties.create', [
            'assignments' => $assignments,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
            'generatedPropertyNumber' => $generatedPropertyNumber,
        ]);
    }

    public function store(StorePropertyRequest $request, SavePropertyAction $action): RedirectResponse
    {
        $this->authorize('create', Property::class);

        $property = $action->create($request->user('tenant'), $request->validated());

        return redirect()->route('tenant.properties.show', $property)->with('success', 'تم إنشاء العقار بنجاح');
    }

    public function show(Property $property): View
    {
        $this->authorize('view', $property);

        return view('tenant.properties.show', [
            'property' => $property->load(['assignment', 'boundaries', 'owners', 'units', 'attachments', 'auctions']),
        ]);
    }

    public function edit(Property $property): View
    {
        $this->authorize('update', $property);

        $assignments = Assignment::where('is_active', true)->orWhere('id', $property->assignment_id)->select(['id', 'name', 'assignment_number'])->get();

        return view('tenant.properties.edit', [
            'property' => $property->load(['assignment', 'boundaries', 'owners', 'units', 'attachments']),
            'assignments' => $assignments,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function update(UpdatePropertyRequest $request, Property $property, SavePropertyAction $action): RedirectResponse
    {
        $this->authorize('update', $property);

        $action->update($property, $request->user('tenant'), $request->validated());

        return redirect()->route('tenant.properties.show', $property)->with('success', 'تم تحديث العقار بنجاح');
    }

    public function toggleStatus(UpdatePropertyStatusRequest $request, Property $property): mixed
    {
        $this->authorize('update', $property);
        $newStatus = $request->boolean('is_active');
        $property->update(['is_active' => $newStatus]);
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $newStatus]);
        }

        return back()->with('success', 'تم تحديث حالة العقار بنجاح');
    }

    public function destroy(Property $property): mixed
    {
        $this->authorize('delete', $property);
        $this->propertyService->deleteProperty($property);
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم نقل العقار إلى سلة المهملات']);
        }

        return redirect()->route('tenant.properties.index')->with('success', 'تم نقل العقار إلى سلة المهملات بنجاح');
    }
}
