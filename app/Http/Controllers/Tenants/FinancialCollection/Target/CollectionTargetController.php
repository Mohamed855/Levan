<?php

namespace App\Http\Controllers\Tenants\FinancialCollection\Target;

use App\DataTables\Tenants\FinancialCollection\Target\CollectionTargetsDataTable;
use App\DataTables\Tenants\FinancialCollection\Target\UserTargetsDataTable;
use App\Data\Tenants\FinancialCollection\Target\CreateCollectionTargetDTO;
use App\Data\Tenants\FinancialCollection\Target\UpdateCollectionTargetDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\FinancialCollection\CollectionTarget\StoreCollectionTargetRequest;
use App\Http\Requests\Tenants\FinancialCollection\CollectionTarget\UpdateCollectionTargetRequest;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Services\Tenants\FinancialCollection\Target\CollectionTargetService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

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
        $this->middleware('can:أهداف التحصيل')->only(['index', 'showUserDetails']);
        $this->middleware('can:إضافة هدف')->only(['create', 'store', 'getUsersByBranch']);
        $this->middleware('can:تعديل هدف')->only(['edit', 'update', 'toggleStatus', 'getUsersByBranch']);
        $this->middleware('can:حذف هدف')->only(['destroy', 'deleteSelected']);
    }

    /*
    |--------------------------------------------------------------------------
    | Display Collection Targets List
    |--------------------------------------------------------------------------
    */
    public function index(CollectionTargetsDataTable $dataTable)
    {
        try {
            $branches = $this->collectionTargetService->getActiveBranches();
            $statistics = $this->collectionTargetService->getTargetsStatistics();

            return $dataTable->render('tenants.financial_collection.collection_targets.index', [
                'branches' => $branches,
                'statistics' => $statistics,
            ]);
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ في تحميل البيانات: ' . $e->getMessage());
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

            if (!$details['user']) {
                return redirect()
                    ->to(tenant_route_name('financial-collections.collection-targets.index'))
                    ->with('error', 'المستخدم غير موجود');
            }

            // تعيين معرف المستخدم للـ DataTable
            $dataTable->setUserId($userId);

            return $dataTable->render('tenants.financial_collection.collection_targets.show', [
                'user' => $details['user'],
                'currentTarget' => $details['current_target'],
                'statistics' => $details['statistics']
            ]);
        } catch (\Exception $e) {
            return redirect()
                ->to(tenant_route_name('financial-collections.collection-targets.index'))
                ->with('error', 'حدث خطأ في تحميل التفاصيل: ' . $e->getMessage());
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
            $branches = $this->collectionTargetService->getActiveBranches();
            $months = $this->collectionTargetService->getAvailableMonths();
            $years = $this->collectionTargetService->getAvailableYears();
            return view(
                'tenants.financial_collection.collection_targets.create',
                compact('branches', 'months', 'years')
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

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    */
   public function edit(CollectionTarget $collectionTarget)
{
    try {
        $branches = $this->collectionTargetService->getActiveBranches();
        
        // جلب المستخدمين بناءً على الفرع
        $users = $this->collectionTargetService->getUsersByBranch($collectionTarget->branch_id);
        
        $months = $this->collectionTargetService->getAvailableMonths();
        $years = $this->collectionTargetService->getAvailableYears();
        
        // جلب المجموعات من جدول teams بناءً على الـ IDs المخزنة في team_ids
        $teamIds = $collectionTarget->team_ids ?? []; // هذا array مثل [1, 2, 3]
        
        // جلب المجموعات من جدول teams
        $teams = Team::whereIn('id', $teamIds)
                    ->where('branch_id', $collectionTarget->branch_id)
                    ->get();
        
        return view(
            'tenants.financial_collection.collection_targets.edit',
            compact('collectionTarget', 'branches', 'users', 'months', 'years', 'teams')
        );
    } catch (\Exception $e) {
        return redirect()
            ->to(tenant_route_name('financial-collections.collection-targets.index'))
            ->with('error', 'حدث خطأ أثناء تحميل صفحة التعديل: ' . $e->getMessage());
    }
}
    /*
    |--------------------------------------------------------------------------
    | Update Collection Target
    |--------------------------------------------------------------------------
    */
    public function update(UpdateCollectionTargetRequest $request, CollectionTarget $collectionTarget): RedirectResponse
    {
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
    public function destroy(CollectionTarget $collectionTarget): RedirectResponse
    {
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
    public function toggleStatus(CollectionTarget $collectionTarget): JsonResponse
    {
        try {
            $updatedTarget = $this->collectionTargetService->toggleCollectionTargetStatus($collectionTarget);
            $statusText = $updatedTarget->status === 'active' ? 'نشط' : 'غير نشط';
            $statusClass = $updatedTarget->status === 'active' ? 'bg-label-success' : 'bg-label-danger';

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'new_status' => $updatedTarget->status,
                'status_text' => $statusText,
                'status_class' => $statusClass
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Users By Branch (AJAX)
    |--------------------------------------------------------------------------
    */
    public function getUsersByBranch(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'branch_id' => 'nullable|integer|exists:branches,id'
            ]);

            $branchId = $request->get('branch_id');
            $users = $this->collectionTargetService->getUsersByBranch($branchId);

            return response()->json([
                'success' => true,
                'users' => $users->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'display_name' => $user->name
                    ];
                })
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'بيانات غير صحيحة',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المستخدمين'
            ], 500);
        }
    }

    public function getTeamsByBranch(Request $request): JsonResponse
{
    try {
        $request->validate([
            'branch_id' => 'required|integer|exists:branches,id'
        ]);

        $branchId = $request->get('branch_id');
        
        // جلب المجموعات من المستخدمين الموجودين في هذا الفرع
        $teams = Team::whereHas('branch', function($query) use ($branchId) {
            $query->where('branch_id', $branchId);
        })
        ->select('id', 'name')
        ->orderBy('name')
        ->get();

        return response()->json([
            'success' => true,
            'teams' => $teams->map(function ($team) {
                return [
                    'id' => $team->id,
                    'name' => $team->name
                ];
            })
        ]);
    } catch (ValidationException $e) {
        return response()->json([
            'success' => false,
            'message' => 'بيانات غير صحيحة',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'حدث خطأ أثناء جلب المجموعات'
        ], 500);
    }
}

}
