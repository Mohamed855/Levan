<?php

namespace App\Http\Controllers\Tenants\Assignments;

use App\DataTables\Tenants\Assignments\AssignmentsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Services\Tenants\Assignments\AssignmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AssignmentsController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService
    ) {
        $this->middleware('auth:tenant');
        $this->middleware('can:assignments.view')->only(['index', 'show', 'getProperties']);
        $this->middleware('can:assignments.create')->only(['create', 'store']);
        $this->middleware('can:assignments.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:assignments.delete')->only(['destroy']);
    }

    public function index(AssignmentsDataTable $dataTable)
    {
        $statistics = $this->assignmentService->getStatistics();

        return $dataTable->render('tenants.assignments.index', [
            'totalAssignments'       => $statistics['total'],
            'activeAssignments'      => $statistics['active'],
            'underReviewAssignments' => $statistics['under_review'],
            'suspendedAssignments'   => $statistics['suspended'],
            'auctionedAssignments'   => $statistics['auctioned'],
        ]);
    }

    public function create(): View
    {
        $generatedAssignmentNumber = $this->assignmentService->generateAssignmentNumber();

        return view('tenants.assignments.create', [
            'generatedAssignmentNumber' => $generatedAssignmentNumber,
        ]);
    }

    public function store(StoreAssignmentRequest $request): RedirectResponse
    {
        try {
            $this->assignmentService->createAssignment(
                $request->validated(),
                $request->file('entity_logo')
            );

            return redirect()->to(tenant_route_name('assignments.index'))
                ->with('success', 'تم إضافة الإسناد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة الإسناد');
        }
    }

    public function show(Assignment $assignment): View
    {
        $assignment->load(['properties', 'auctions']);

        return view('tenants.assignments.show', [
            'assignment' => $assignment,
        ]);
    }

    public function edit(Assignment $assignment): View
    {
        return view('tenants.assignments.edit', [
            'assignment' => $assignment,
        ]);
    }

    public function update(UpdateAssignmentRequest $request, Assignment $assignment): RedirectResponse
    {
        try {
            $this->assignmentService->updateAssignment(
                $assignment,
                $request->validated(),
                $request->file('entity_logo')
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
                'status'  => $newStatus,
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
            'success'    => true,
            'assignment' => [
                'id'                => $assignment->id,
                'assignment_number' => $assignment->assignment_number,
                'name'              => $assignment->name,
                'issuing_entity'    => $assignment->issuing_entity,
                'status_label'      => $assignment->status_label,
            ],
            'properties' => $properties,
        ]);
    }
}
