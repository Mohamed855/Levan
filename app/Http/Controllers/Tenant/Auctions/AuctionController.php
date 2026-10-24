<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Auctions;

use App\Actions\Tenant\Auctions\StoreAuctionAction;
use App\Actions\Tenant\Auctions\UpdateAuctionAction;
use App\DataTables\Tenant\Auctions\AuctionDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenant\Auctions\UpdateAuctionRequest;
use App\Http\Requests\Tenant\Auctions\UpdateAuctionStatusRequest;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Auction;
use App\Models\Tenant\Property;
use App\Services\Auctions\AuctionTemplateRenderer;
use App\Services\Auctions\AuctionSnapshotPdfExporter;
use App\Services\Auctions\CentralAuctionTemplateGateway;
use App\Services\Shared\Wizard\WizardDraftService;
use App\Services\Tenants\Auctions\AuctionService;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

final class AuctionController extends Controller
{
    public function __construct(
        private readonly CentralAuctionTemplateGateway $templates,
        private readonly AuctionService $auctionService,
        private readonly CentralCityOptionsGateway $cityOptionsGateway
    ) {
        $this->middleware('can:auctions.view')->only(['index', 'show', 'preview', 'download']);
        $this->middleware('can:auctions.create')->only(['create', 'store']);
        $this->middleware('can:auctions.edit')->only(['edit', 'update', 'toggleStatus', 'destroy']);
    }

    public function index(AuctionDataTable $dataTable)
    {
        $statistics = $this->auctionService->getStatistics();

        return $dataTable->render('tenant.auctions.index', [
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
        $this->authorize('create', Auction::class);

        return view('tenant.auctions.create', [
            'auction' => new Auction(),
            'templateOptions' => $this->templateOptions(),
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ] + $this->assignmentData());
    }

    public function store(StoreAuctionRequest $request, StoreAuctionAction $action, WizardDraftService $drafts): RedirectResponse
    {
        $this->authorize('create', Auction::class);

        $data = $this->dataWithCover($request);
        $auction = $action->execute($request->user('tenant'), $data);
        $drafts->discard($request, 'tenant.auctions.create');

        return redirect()->route('tenant.auctions.show', $auction)
            ->with('success', 'تم إنشاء المزاد وحفظ لقطات القوالب المنشورة بنجاح.');
    }

    public function show(Auction $auction): View
    {
        $this->authorize('view', $auction);

        return view('tenant.auctions.show', [
            'auction' => $auction->load(['assignment', 'properties', 'attachments']),
        ]);
    }

    public function edit(Auction $auction): View
    {
        $this->authorize('update', $auction);

        return view('tenant.auctions.edit', [
            'auction' => $auction->load('properties'),
            'templateOptions' => $this->templateOptions(),
            'cityOptions' => $this->cityOptionsGateway->optionsByName(),
        ] + $this->assignmentData());
    }

    public function update(UpdateAuctionRequest $request, Auction $auction, UpdateAuctionAction $action): RedirectResponse
    {
        $this->authorize('update', $auction);

        $data = $this->dataWithCover($request);
        $action->execute($auction, $request->user('tenant'), $data);

        return redirect()->route('tenant.auctions.show', $auction)
            ->with('success', 'تم تحديث المزاد مع الحفاظ على توافق القوالب.');
    }

    public function toggleStatus(UpdateAuctionStatusRequest $request, Auction $auction): mixed
    {
        $this->authorize('update', $auction);
        $newStatus = $request->boolean('is_active');
        $auction->update(['is_active' => $newStatus, 'status' => $newStatus ? 'active' : 'suspended']);
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'is_active' => $newStatus]);
        }

        return back()->with('success', 'تم تحديث حالة المزاد بنجاح');
    }

    public function destroy(Auction $auction): mixed
    {
        $this->authorize('delete', $auction);
        $this->auctionService->deleteAuction($auction);
        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم نقل المزاد إلى المحذوفات بنجاح']);
        }

        return redirect()->route('tenant.auctions.index')->with('success', 'تم نقل المزاد إلى المحذوفات بنجاح');
    }

    public function preview(Auction $auction, string $type, AuctionTemplateRenderer $renderer): View
    {
        $this->authorize('view', $auction);
        abort_unless(in_array($type, ['brochure', 'billboard', 'social'], true), 404);

        return view('tenant.auctions.preview', [
            'output' => $renderer->render($auction, $type),
            'type' => $type,
            'auction' => $auction,
        ]);
    }

    public function download(Auction $auction, string $type, AuctionSnapshotPdfExporter $exporter): Response
    {
        $this->authorize('view', $auction);
        abort_unless(in_array($type, ['brochure', 'billboard', 'social'], true), 404);

        return response($exporter->make($auction, $type), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="auction-%d-%s.pdf"', $auction->id, $type),
        ]);
    }

    /** @return array<string, mixed> */
    private function dataWithCover(StoreAuctionRequest|UpdateAuctionRequest $request): array
    {
        $data = $request->validated();
        if ($request->hasFile('cover_image')) {
            $data['cover_image'] = $request->file('cover_image')->store('auctions/covers', 'public');
        } else {
            unset($data['cover_image']);
        }

        return $data;
    }

    /** @return array<string,mixed> */
    private function assignmentData(): array
    {
        return [
            'assignments' => Assignment::query()->where('is_active', true)->orderBy('assignment_number')->get(['id', 'assignment_number', 'name']),
            'properties' => Property::query()->where('is_active', true)->orderBy('property_number')->get(['id', 'assignment_id', 'property_number', 'name']),
        ];
    }

    /** @return array<string, array<int, array{value:int,label:string}>> */
    private function templateOptions(): array
    {
        return collect(['brochure', 'billboard', 'social'])->mapWithKeys(function (string $type): array {
            $options = $this->templates->publishedByType($type)->map(fn ($version) => [
                'value' => $version->id,
                'label' => "{$version->template->name} — إصدار {$version->version}",
            ])->all();

            return [$type => $options];
        })->all();
    }
}
