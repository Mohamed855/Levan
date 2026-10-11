<?php

namespace App\Http\Controllers\Tenants\Assignments;

use App\Actions\Tenants\Assignments\CreateAssignmentAction;
use App\DataTables\Tenants\Assignments\AssignmentPropertySelectionDataTable;
use App\DataTables\Tenants\Assignments\AssignmentsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Assignments\SelectedAssignmentPropertiesRequest;
use App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Assignments\AssignmentService;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class AssignmentsController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService,
        protected CentralCityOptionsGateway $cityOptionsGateway
    ) {
        $this->middleware('auth:tenant');
        $this->authorizeResource(Assignment::class, 'assignment');
        $this->middleware('can:assignments.view')->only(['index', 'show', 'getProperties']);
        $this->middleware('can:assignments.create')->only(['create', 'store']);
        $this->middleware('can:assignments.create')->only(['selectedProperties']);
        $this->middleware('can:assignments.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:assignments.delete')->only(['destroy']);
        $this->middleware('can:view,assignment')->only(['getProperties']);
    }

    public function index(AssignmentsDataTable $dataTable)
    {
        $statistics = $this->assignmentService->getStatistics();

        return $dataTable->render('tenants.assignments.index', [
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
        $generatedAssignmentNumber = $this->assignmentService->generateAssignmentNumber();

        return $dataTable->render('tenants.assignments.create', [
            'generatedAssignmentNumber' => $generatedAssignmentNumber,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function store(StoreAssignmentRequest $request, CreateAssignmentAction $action): RedirectResponse
    {
        try {
            $action->execute($request->validated(), (int) $request->user('tenant')->getAuthIdentifier());

            return redirect()->to(tenant_route_name('assignments.index'))
                ->with('success', 'تم إضافة الإسناد بنجاح');
        } catch (\Exception $e) {
            Log::error('Assignment creation failed.', ['exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الإسناد');
        }
    }

    public function selectedProperties(SelectedAssignmentPropertiesRequest $request): JsonResponse
    {
        $properties = Property::query()
            ->whereKey($request->validated('ids'))
            ->whereNull('assignment_id')
            ->get(['id', 'property_number', 'name', 'main_image'])
            ->map(fn (Property $property): array => [
                'id' => (string) $property->id,
                'number' => $property->property_number,
                'name' => $property->name,
                'image' => $property->main_image ? tasset($property->main_image) : asset('assets/img/illustrations/page-pricing-standard.png'),
            ]);

        return response()->json(['data' => $properties]);
    }

    public function show(Assignment $assignment): View
    {
        $assignment->load(['properties', 'auctions', 'attachments', 'assignmentImage']);

        return view('tenants.assignments.show', [
            'assignment' => $assignment->load('assignmentImage'),
        ]);
    }

    public function edit(Assignment $assignment): View
    {
        return view('tenants.assignments.edit', [
            'assignment' => $assignment,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        try {
            $this->assignmentService->updateAssignment(
                $assignment,
                $request->validated(),
                $request->file('assignment_image')
            );

            return redirect()->to(tenant_route_name('assignments.index'))
                ->with('success', 'تم تحديث الإسناد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الإسناد');
        }
    }

    public function destroy(Assignment $assignment)
    {
        try {
            $this->assignmentService->deleteAssignment($assignment);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم نقل الإسناد إلى المحذوفات بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم نقل الإسناد إلى المحذوفات بنجاح');
        } catch (\LogicException $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الإسناد',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الإسناد');
        }
    }

    public function toggleStatus(UpdateResourceStatusRequest $request, Assignment $assignment): JsonResponse
    {
        try {
            $newStatus = $this->assignmentService->toggleStatus(
                $assignment,
                $request->validated('status') === 'active',
            );

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'تم تفعيل الإسناد' : 'تم تعطيل الإسناد',
                'status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل الحالة',
            ], 500);
        }
    }

    public function getProperties(Assignment $assignment): JsonResponse
    {
        $properties = $assignment->properties()
            ->select(['id', 'property_number', 'name', 'type', 'city', 'district', 'area', 'deed_number', 'estimated_value', 'data_status', 'main_image'])
            ->get();

        return response()->json([
            'success' => true,
            'assignment' => [
                'id' => $assignment->id,
                'assignment_number' => $assignment->assignment_number,
                'name' => $assignment->name,
                'issuing_entity' => $assignment->issuing_entity,
                'status_label' => $assignment->status_label,
            ],
            'properties' => $properties,
        ]);
    }
}
