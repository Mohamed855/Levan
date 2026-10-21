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
        $this->middleware('auth:tenant');
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
        $generatedAssignmentNumber = $this->assignmentService->generateAssignmentNumber();

        return $dataTable->render('tenant.assignments.create', [
            'generatedAssignmentNumber' => $generatedAssignmentNumber,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
            'propertyImageFallback' => asset('static/brand/logos/logo.svg'),
        ]);
    }

    public function store(StoreAssignmentRequest $request, CreateAssignmentAction $action): RedirectResponse
    {
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

    public function show(Assignment $assignment): View
    {
        $assignment->load(['properties', 'auctions', 'attachments', 'assignmentImage']);

        return view('tenant.assignments.show', [
            'assignment' => $assignment,
        ]);
    }

    public function edit(Assignment $assignment): View
    {
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

    public function toggleStatus(Assignment $assignment): mixed
    {
        $newStatus = ! $assignment->is_active;
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
