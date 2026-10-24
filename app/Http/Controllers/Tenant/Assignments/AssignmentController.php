<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Assignments;

use App\Actions\Tenant\Assignments\CreateAssignmentAction;
use App\Actions\Tenant\Assignments\UpdateAssignmentAction;
use App\DataTables\Tenant\Assignments\AssignmentDataTable;
use App\DataTables\Tenant\Assignments\AssignmentPropertySelectionDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Assignments\SelectedAssignmentPropertiesRequest;
use App\Http\Requests\Tenant\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenant\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenant\Assignments\UpdateAssignmentStatusRequest;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use App\Services\Tenants\Assignments\AssignmentService;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

final class AssignmentController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService,
        protected CentralCityOptionsGateway $cityOptionsGateway
    ) {
        $this->middleware('can:assignments.view')->only(['index', 'show']);
        $this->middleware('can:assignments.create')->only(['create', 'store', 'selectedProperties']);
        $this->middleware('can:auctions.create')->only(['properties']);
        $this->middleware('can:assignments.edit')->only(['edit', 'update', 'toggleStatus', 'destroy']);
    }

    public function index(AssignmentDataTable $dataTable)
    {
        $statistics = $this->assignmentService->getStatistics();

        return $dataTable->render('tenant.assignments.index', [
            'totalAssignments' => $statistics['total'],
            'activeAssignments' => $statistics['active'],
            'underReviewAssignments' => $statistics['under_review'],
            'suspendedAssignments' => $statistics['suspended'],
            'auctionedAssignments' => $statistics['auctioned'],
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function create(AssignmentPropertySelectionDataTable $dataTable)
    {
        $this->authorize('create', Assignment::class);

        $generatedAssignmentNumber = $this->assignmentService->generateAssignmentNumber();

        return $dataTable->render('tenant.assignments.create', [
            'generatedAssignmentNumber' => $generatedAssignmentNumber,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
            'propertyImageFallback' => asset('static/brand/logos/logo.svg'),
        ]);
    }

    public function store(StoreAssignmentRequest $request, CreateAssignmentAction $action): RedirectResponse
    {
        $this->authorize('create', Assignment::class);

        try {
            $assignment = $action->execute($request->validated(), (int) $request->user('tenant')->id);

            return redirect()->route('tenant.assignments.show', $assignment)
                ->with('success', 'تم إضافة الإسناد بنجاح');
        } catch (\Exception $e) {
            Log::error('Assignment creation failed.', ['exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الإسناد: ' . $e->getMessage());
        }
    }

    public function selectedProperties(SelectedAssignmentPropertiesRequest $request): JsonResponse
    {
        $fallbackImage = asset('static/brand/logos/logo.svg');
        $properties = Property::query()
            ->whereKey($request->validated('ids'))
            ->get(['id', 'property_number', 'name', 'type', 'city', 'main_image'])
            ->map(fn (Property $property): array => [
                'id' => (string) $property->id,
                'number' => $property->property_number,
                'name' => $property->name,
                'type' => $property->type_label,
                'city' => $property->city,
                'image' => $property->main_image ? tasset($property->main_image) : $fallbackImage,
            ]);

        return response()->json(['data' => $properties]);
    }

    public function properties(Assignment $assignment): JsonResponse
    {
        $this->authorize('view', $assignment);

        return response()->json([
            'data' => $assignment->properties()
                ->where('is_active', true)
                ->orderBy('property_number')
                ->get(['id', 'property_number', 'name'])
                ->map(static fn (Property $property): array => [
                    'id' => (string) $property->id,
                    'property_number' => $property->property_number,
                    'name' => $property->name,
                ]),
        ]);
    }

    public function show(Assignment $assignment): View
    {
        $this->authorize('view', $assignment);

        $assignment->load(['properties', 'auctions', 'attachments', 'assignmentImage']);

        return view('tenant.assignments.show', [
            'assignment' => $assignment,
        ]);
    }

    public function edit(Assignment $assignment): View
    {
        $this->authorize('update', $assignment);

        return view('tenant.assignments.edit', [
            'assignment' => $assignment,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function update(
        UpdateAssignmentRequest $request,
        Assignment $assignment,
        UpdateAssignmentAction $action
    ): RedirectResponse {
        $this->authorize('update', $assignment);

        try {
            $action->execute(
                $assignment,
                $request->validated(),
                $request->file('assignment_image')
            );

            return redirect()->route('tenant.assignments.show', $assignment)
                ->with('success', 'تم تحديث الإسناد بنجاح');
        } catch (\Exception $e) {
            Log::error('Assignment update failed.', ['exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الإسناد');
        }
    }

    public function toggleStatus(UpdateAssignmentStatusRequest $request, Assignment $assignment): mixed
    {
        $this->authorize('update', $assignment);
        $newStatus = $request->boolean('is_active');
        $assignment->update([
            'is_active' => $newStatus,
            'status' => $newStatus ? 'active' : 'suspended',
        ]);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $newStatus]);
        }

        return back()->with('success', 'تم تحديث حالة الإسناد بنجاح');
    }

    public function destroy(Assignment $assignment): mixed
    {
        $this->authorize('delete', $assignment);
        if ($assignment->properties()->exists() || $assignment->auctions()->exists()) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'لا يمكن حذف الإسناد لوجود عقارات أو مزادات مرتبطة به'], 422);
            }

            return back()->with('error', 'لا يمكن حذف الإسناد لوجود عقارات أو مزادات مرتبطة به');
        }

        $assignment->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم نقل الإسناد إلى المحذوفات بنجاح']);
        }

        return redirect()->route('tenant.assignments.index')->with('success', 'تم نقل الإسناد إلى المحذوفات بنجاح');
    }
}
