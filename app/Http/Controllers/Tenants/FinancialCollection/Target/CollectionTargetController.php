<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\Target;

use App\Data\Tenants\FinancialCollection\Target\CreateCollectionTargetDTO;
use App\Data\Tenants\FinancialCollection\Target\UpdateCollectionTargetDTO;
use App\DataTables\Tenants\FinancialCollection\Target\CollectionTargetsDataTable;
use App\DataTables\Tenants\FinancialCollection\Target\UserTargetsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\FinancialCollection\CollectionTarget\StoreCollectionTargetRequest;
use App\Http\Requests\Tenants\FinancialCollection\CollectionTarget\UpdateCollectionTargetRequest;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Services\Tenants\FinancialCollection\Target\CollectionTargetService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CollectionTargetController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Class Properties
    |--------------------------------------------------------------------------
    */
    protected CollectionTargetService $collectionTargetService;

    /*
    |--------------------------------------------------------------------------
    | Constructor
    |--------------------------------------------------------------------------
    */
    public function __construct(CollectionTargetService $collectionTargetService)
    {
        $this->collectionTargetService = $collectionTargetService;
        $this->authorizeResource(CollectionTarget::class, 'collection_target');
        $this->middleware('can:collection_targets.view')->only(['index', 'showUserDetails']);
        $this->middleware('can:collection_targets.create')->only(['create', 'store']);
        $this->middleware('can:collection_targets.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:collection_targets.delete')->only(['destroy', 'deleteSelected']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Collection Targets List
    |--------------------------------------------------------------------------
    */
    public function index(CollectionTargetsDataTable $dataTable)
    {
        try {
            $statistics = $this->collectionTargetService->getTargetsStatistics();

            return $dataTable->render('tenants.financial_collection.collection_targets.index', [
                'statistics' => $statistics,
                'users' => $this->collectionTargetService->getActiveUsers(),
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ في تحميل البيانات: '.$e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show User Target Details Page WITH DataTable
    |--------------------------------------------------------------------------
    */
    public function showUserDetails(int $userId, UserTargetsDataTable $dataTable)
    {
        try {
            $details = $this->collectionTargetService->getUserTargetDetailsWithProgress($userId);

            if (! $details['user']) {
                return redirect()
                    ->to(tenant_route_name('financial-collections.collection-targets.index'))
                    ->with('error', 'المستخدم غير موجود');
            }

            // تعيين معرف المستخدم للـ DataTable
            $dataTable->setUserId($userId);

            return $dataTable->render('tenants.financial_collection.collection_targets.show', [
                'user' => $details['user'],
                'currentTarget' => $details['current_target'],
                'statistics' => $details['statistics'],
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->to(tenant_route_name('financial-collections.collection-targets.index'))
                ->with('error', 'حدث خطأ في تحميل التفاصيل: '.$e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    */
    public function create(): View|RedirectResponse
    {
        try {
            $users = $this->collectionTargetService->getActiveUsers();
            $teams = Team::where('status', 'active')->orderBy('name')->get();
            $months = $this->collectionTargetService->getAvailableMonths();
            $years = $this->collectionTargetService->getAvailableYears();

            return view(
                'tenants.financial_collection.collection_targets.create',
                compact('users', 'teams', 'months', 'years')
            );
        } catch (\Exception $e) {
            return redirect()
                ->to(tenant_route_name('financial-collections.collection-targets.index'))
                ->with('error', 'حدث خطأ أثناء تحميل صفحة الإضافة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Collection Target
    |--------------------------------------------------------------------------
    */
    public function store(StoreCollectionTargetRequest $request)
    {
        // return $request;
        try {
            $dto = CreateCollectionTargetDTO::fromArray($request->validated());
            $this->collectionTargetService->createCollectionTarget($dto);

            return redirect()
                ->to(tenant_route_name('financial-collections.collection-targets.index'))
                ->with('success', 'تم إضافة هدف التحصيل بنجاح');
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->validator);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة هدف التحصيل. يرجى المحاولة مرة أخرى.');
        }
    }

    public function edit(CollectionTarget $collection_target)
    {
        $collectionTarget = $collection_target;
        try {
            $users = $this->collectionTargetService->getActiveUsers();
            $teams = Team::where('status', 'active')->orderBy('name')->get();
            $months = $this->collectionTargetService->getAvailableMonths();
            $years = $this->collectionTargetService->getAvailableYears();

            return view(
                'tenants.financial_collection.collection_targets.edit',
                compact('collectionTarget', 'users', 'months', 'years', 'teams')
            );
        } catch (\Exception $e) {
            return redirect()
                ->to(tenant_route_name('financial-collections.collection-targets.index'))
                ->with('error', 'حدث خطأ أثناء تحميل صفحة التعديل: '.$e->getMessage());
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Update Collection Target
    |--------------------------------------------------------------------------
    */
    public function update(UpdateCollectionTargetRequest $request, CollectionTarget $collection_target): RedirectResponse
    {
        $collectionTarget = $collection_target;
        try {
            $dto = UpdateCollectionTargetDTO::fromArray($request->validated());
            $this->collectionTargetService->updateCollectionTarget($collectionTarget, $dto);

            // العودة إلى صفحة تفاصيل المستخدم
            return redirect()
                ->to(tenant_route_name('financial-collections.collection-targets.user-details', $collectionTarget->user_id))
                ->with('success', 'تم تحديث هدف التحصيل بنجاح');
        } catch (ValidationException $e) {
            return redirect()
                ->back()
                ->withInput()
                ->withErrors($e->validator);
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث هدف التحصيل. يرجى المحاولة مرة أخرى.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Collection Target
    |--------------------------------------------------------------------------
    */
    public function destroy(CollectionTarget $collection_target): RedirectResponse
    {
        $collectionTarget = $collection_target;
        try {
            $userId = $collectionTarget->user_id;
            $this->collectionTargetService->deleteCollectionTarget($collectionTarget);

            return redirect()
                ->to(tenant_route_name('financial-collections.collection-targets.user-details', $userId))
                ->with('success', 'تم حذف هدف التحصيل بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء حذف هدف التحصيل. يرجى المحاولة مرة أخرى.');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Collection Target Status
    |--------------------------------------------------------------------------
    */
    public function toggleStatus(CollectionTarget $collection_target): JsonResponse
    {
        $collectionTarget = $collection_target;
        try {
            $updatedTarget = $this->collectionTargetService->toggleCollectionTargetStatus($collectionTarget);
            $statusText = $updatedTarget->status === 'active' ? 'نشط' : 'غير نشط';
            $statusClass = $updatedTarget->status === 'active' ? 'bg-label-success' : 'bg-label-danger';

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'status' => $updatedTarget->status,
                'new_status' => $updatedTarget->status,
                'status_text' => $statusText,
                'status_class' => $statusClass,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة',
            ], 500);
        }
    }
}
