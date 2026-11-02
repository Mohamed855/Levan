<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Assignments;

use App\DataTables\Tenants\Assignments\IssuingEntitiesDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\IssuingEntities\StoreIssuingEntityRequest;
use App\Http\Requests\Tenants\IssuingEntities\UpdateIssuingEntityRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\Assignments\IssuingEntity;
use App\Services\Tenants\Assignments\IssuingEntityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use LogicException;

class IssuingEntitiesController extends Controller
{
    public function __construct(
        protected IssuingEntityService $issuingEntityService
    ) {
        $this->middleware('auth:tenant');
        $this->authorizeResource(IssuingEntity::class, 'issuing_entity');
        $this->middleware('can:issuing_entities.view')->only(['index']);
        $this->middleware('can:issuing_entities.create')->only(['create', 'store']);
        $this->middleware('can:issuing_entities.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:issuing_entities.delete')->only(['destroy']);
    }

    public function index(IssuingEntitiesDataTable $dataTable): mixed
    {
        $statistics = $this->issuingEntityService->getStatistics();

        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
        ];

        return $dataTable->render('tenant.issuing-entities.index', [
            'statistics' => $statistics,
            'filterOptions' => $filterOptions,
        ]);
    }

    public function create(): View
    {
        return view('tenant.issuing-entities.create', [
            'issuingEntity' => new IssuingEntity(['is_active' => true]),
        ]);
    }

    public function store(StoreIssuingEntityRequest $request): RedirectResponse
    {
        $this->issuingEntityService->create(
            $request->validated(),
            (int) $request->user('tenant')->getAuthIdentifier()
        );

        return redirect()->to(tenant_route_name('issuing-entities.index'))
            ->with('success', 'تمت إضافة الجهة المسندة بنجاح.');
    }

    public function edit(IssuingEntity $issuingEntity): View
    {
        return view('tenant.issuing-entities.edit', [
            'issuingEntity' => $issuingEntity,
        ]);
    }

    public function update(UpdateIssuingEntityRequest $request, IssuingEntity $issuingEntity): RedirectResponse
    {
        $this->issuingEntityService->update(
            $issuingEntity,
            $request->validated()
        );

        return redirect()->to(tenant_route_name('issuing-entities.index'))
            ->with('success', 'تم تحديث الجهة المسندة بنجاح.');
    }

    public function destroy(TenantAuthenticatedRequest $request, IssuingEntity $issuingEntity)
    {
        try {
            $this->issuingEntityService->delete($issuingEntity);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم حذف الجهة المسندة بنجاح.',
                ]);
            }

            return redirect()->back()->with('success', 'تم حذف الجهة المسندة بنجاح.');
        } catch (LogicException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()->with('error', $e->getMessage());
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف الجهة المسندة.',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف الجهة المسندة.');
        }
    }

    public function toggleStatus(UpdateResourceStatusRequest $request, IssuingEntity $issuingEntity): JsonResponse
    {
        try {
            $status = $this->issuingEntityService->toggleStatus(
                $issuingEntity,
                $request->validated('status') === 'active'
            );

            return response()->json([
                'success' => true,
                'message' => $status ? 'تم تفعيل الجهة المسندة بنجاح' : 'تم تعطيل الجهة المسندة بنجاح',
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل حالة الجهة المسندة.',
            ], 500);
        }
    }
}
