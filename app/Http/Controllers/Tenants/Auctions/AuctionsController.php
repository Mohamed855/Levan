<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Auctions;

use App\Actions\Tenants\Auctions\CreateAuctionAction;
use App\Actions\Tenants\Auctions\UpdateAuctionAction;
use App\DataTables\Tenants\Auctions\AuctionsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenants\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\UpdateResourceStatusRequest;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\AuctionService;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuctionsController extends Controller
{
    public function __construct(
        protected AuctionService $auctionService,
        protected CentralCityOptionsGateway $cityOptionsGateway,
        protected CentralAuctionTemplatesGateway $templatesGateway
    ) {
        $this->middleware('auth:tenant');
        $this->authorizeResource(Auction::class, 'auction');
        $this->middleware('can:auctions.view')->only(['index', 'show']);
        $this->middleware('can:auctions.create')->only(['create', 'store']);
        $this->middleware('can:auctions.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:auctions.delete')->only(['destroy']);
    }

    public function index(AuctionsDataTable $dataTable)
    {
        $statistics = $this->auctionService->getStatistics();

        return $dataTable->render('tenants.auctions.index', [
            'totalAuctions' => $statistics['total'],
            'activeAuctions' => $statistics['active'],
            'inPreparationAuctions' => $statistics['in_preparation'],
            'pendingAuctions' => $statistics['pending_approval'],
            'needsReviewAuctions' => $statistics['needs_review'],
            'assignments' => Assignment::query()->select(['id', 'name', 'assignment_number'])->orderBy('name')->get(),
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ]);
    }

    public function create(): View
    {
        $assignments = Assignment::where('is_active', true)
            ->select(['id', 'name', 'assignment_number', 'issuing_entity'])
            ->get();

        return view('tenants.auctions.create', [
            'assignments'        => $assignments,
            'cityOptions'        => $this->cityOptionsGateway->optionsByName(),
            'brochureTemplates'  => $this->templatesGateway->activeByType('brochure'),
            'billboardTemplates' => $this->templatesGateway->activeByType('billboard'),
            'socialTemplates'    => $this->templatesGateway->activeByType('social'),
        ]);
    }

    public function store(StoreAuctionRequest $request, CreateAuctionAction $action): RedirectResponse
    {
        try {
            $action->execute(
                $request->validated(),
                (int) $request->user('tenant')?->getAuthIdentifier(),
                null,
                $request->file('attachments', [])
            );

            return redirect()->to(tenant_route_name('auctions.index'))
                ->with('success', 'تم إنشاء المزاد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إنشاء المزاد: '.$e->getMessage());
        }
    }

    public function show(Auction $auction): View
    {
        $auction->load(['assignment', 'properties', 'attachments']);

        return view('tenants.auctions.show', [
            'auction' => $auction,
        ]);
    }

    public function edit(Auction $auction): View
    {
        $auction->load(['assignment', 'properties', 'attachments']);
        $assignments = Assignment::select(['id', 'name', 'assignment_number', 'issuing_entity'])->get();
        $properties = Property::query()
            ->where('assignment_id', $auction->assignment_id)
            ->select(['id', 'property_number', 'name'])
            ->orderBy('property_number')
            ->get();

        return view('tenants.auctions.edit', [
            'auction'            => $auction,
            'assignments'        => $assignments,
            'properties'         => $properties,
            'cityOptions'        => $this->cityOptionsGateway->optionsByName(),
            'brochureTemplates'  => $this->templatesGateway->activeByType('brochure'),
            'billboardTemplates' => $this->templatesGateway->activeByType('billboard'),
            'socialTemplates'    => $this->templatesGateway->activeByType('social'),
        ]);
    }

    public function update(
        UpdateAuctionRequest $request,
        Auction $auction,
        UpdateAuctionAction $action,
    ): RedirectResponse {
        try {
            $action->execute(
                $auction,
                $request->validated(),
                null,
                $request->file('attachments', [])
            );

            return redirect()->to(tenant_route_name('auctions.index'))
                ->with('success', 'تم تحديث المزاد بنجاح');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث المزاد: '.$e->getMessage());
        }
    }

    public function destroy(TenantAuthenticatedRequest $request, Auction $auction)
    {
        try {
            $this->auctionService->deleteAuction($auction);

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم نقل المزاد إلى المحذوفات بنجاح',
                ]);
            }

            return redirect()->back()->with('success', 'تم نقل المزاد إلى المحذوفات بنجاح');
        } catch (\Exception $e) {
            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء حذف المزاد',
                ], 500);
            }

            return redirect()->back()->with('error', 'حدث خطأ أثناء حذف المزاد');
        }
    }

    public function toggleStatus(UpdateResourceStatusRequest $request, Auction $auction): JsonResponse
    {
        try {
            $newStatus = $this->auctionService->toggleStatus(
                $auction,
                $request->validated('status') === 'active',
            );

            return response()->json([
                'success' => true,
                'message' => $newStatus ? 'تم تفعيل المزاد' : 'تم تعطيل المزاد',
                'status' => $newStatus,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تعديل الحالة',
            ], 500);
        }
    }
}
