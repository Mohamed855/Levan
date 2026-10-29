<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Assignments;

use App\Actions\Tenants\Assignments\CreateAssignmentAction;
use App\Actions\Tenants\Assignments\UpdateAssignmentAction;
use App\DataTables\Tenants\Assignments\AssignmentPropertySelectionDataTable;
use App\DataTables\Tenants\Assignments\AssignmentsDataTable;
use App\Enums\Tenants\Assignments\AssignmentCategory;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Assignments\SelectedAssignmentPropertiesRequest;
use App\Http\Requests\Tenants\Assignments\StoreAssignmentRequest;
use App\Http\Requests\Tenants\Assignments\UpdateAssignmentRequest;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Assignments\AssignmentService;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use App\Helpers\Tenants\Settings\SettingsHelper;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssignmentsController extends Controller
{
    public function __construct(
        protected AssignmentService $assignmentService,
        protected CentralCityOptionsGateway $cityOptionsGateway
    ) {
        $this->middleware('auth:tenant');
        $this->authorizeResource(Assignment::class, 'assignment');
        $this->middleware('can:assignments.view')->only(['index', 'show', 'getProperties', 'selectedProperties']);
        $this->middleware('can:assignments.create')->only(['create', 'store']);
        $this->middleware('can:assignments.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:assignments.delete')->only(['destroy']);
        $this->middleware('can:view,assignment')->only(['getProperties']);
    }

    public function index(AssignmentsDataTable $dataTable)
    {
        $statistics = $this->assignmentService->getStatistics();
        return $dataTable->render('tenant.assignments.index', [
            'totalAssignments' => $statistics['total'],
            'activeAssignments' => $statistics['active'],
            'underReviewAssignments' => $statistics['under_review'],
            'suspendedAssignments' => $statistics['suspended'],
            'auctionedAssignments' => $statistics['auctioned'],
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
            'statusOptions' => ['active' => 'نشط', 'inactive' => 'متوقف'],
        ]);
    }

    public function create(AssignmentPropertySelectionDataTable $dataTable)
    {
        $generatedAssignmentNumber = $this->assignmentService->generateAssignmentNumber();
        $tenantImage = SettingsHelper::get('image');
        $initialCategory = old('category', AssignmentCategory::LANDS->value);
        $dataTable->forCategory($initialCategory);

        return $dataTable->render('tenant.assignments.create', [
            'generatedAssignmentNumber' => $generatedAssignmentNumber,
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
            'propertyImageFallback' => $tenantImage ? tasset($tenantImage) : asset('static/brand/logos/logo.svg'),
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
        $tenantImage = SettingsHelper::get('image');
        $fallbackImage = $tenantImage ? tasset($tenantImage) : asset('static/brand/logos/logo.svg');
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

    public function edit(Assignment $assignment, AssignmentPropertySelectionDataTable $dataTable)
    {
        $tenantImage = SettingsHelper::get('image');
        $fallbackImage = $tenantImage ? tasset($tenantImage) : asset('static/brand/logos/logo.svg');

        $dataTable->forAssignment($assignment->id);
        $dataTable->forCategory($assignment->category instanceof \BackedEnum ? $assignment->category->value : (string) $assignment->category);

        return $dataTable->render('tenant.assignments.edit', [
            'assignment' => $assignment->load(['properties', 'attachments', 'assignmentImage']),
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
            'propertyImageFallback' => $fallbackImage,
        ]);
    }

    public function update(
        UpdateAssignmentRequest $request,
        Assignment $assignment,
        UpdateAssignmentAction $action,
    ): RedirectResponse {
        try {
            $action->execute(
                $assignment,
                $request->validated(),
                $request->file('assignment_image'),
                $request->file('assignment_letter'),
                $request->file('general_documents', []),
                $request->file('additional_attachments', []),
            );

            return redirect()->to(tenant_route_name('assignments.index'))
                ->with('success', 'تم تحديث الإسناد بنجاح');
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Assignment update failed.', ['exception' => $e]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث الإسناد');
        }
    }

    public function destroy(TenantAuthenticatedRequest $request, Assignment $assignment)
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
            ->select(['id', 'property_number', 'name', 'type', 'city', 'district', 'area', 'deed_number', 'estimated_value', 'data_status', 'status', 'main_image'])
            ->get()
            ->map(function ($prop) {
                $isSold = $prop->status === 'sold' || \Illuminate\Support\Facades\DB::table('auction_properties')
                    ->where('property_id', $prop->id)
                    ->where('result_status', 'sold')
                    ->exists();

                $activeAuction = \Illuminate\Support\Facades\DB::table('auction_properties')
                    ->join('auctions', 'auctions.id', '=', 'auction_properties.auction_id')
                    ->where('auction_properties.property_id', $prop->id)
                    ->whereIn('auctions.status', ['active', 'in_preparation', 'pending_approval'])
                    ->whereNull('auctions.deleted_at')
                    ->select('auctions.id', 'auctions.name')
                    ->first();

                $prop->is_sold = $isSold;
                $prop->is_in_active_auction = (bool) $activeAuction;
                $prop->active_auction_name = $activeAuction?->name;
                $prop->is_selectable = ! $isSold && ! $activeAuction;
                $prop->image_url = $prop->main_image ? tasset($prop->main_image) : asset('static/brand/logos/logo.svg');
                $prop->type_label = $prop->type_label;

                return $prop;
            });

        return response()->json([
            'success' => true,
            'assignment' => [
                'id' => $assignment->id,
                'assignment_number' => $assignment->assignment_number,
                'name' => $assignment->name,
                'issuing_entity' => $assignment->issuing_entity,
                'city' => $assignment->city,
                'category' => $assignment->category instanceof \BackedEnum ? $assignment->category->value : (string) $assignment->category,
                'status_label' => $assignment->status_label,
                'start_date' => $assignment->start_date?->format('Y-m-d'),
                'end_date' => $assignment->end_date?->format('Y-m-d'),
            ],
            'properties' => $properties,
        ]);
    }
}
