<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Auctions;

use App\Actions\Tenant\Auctions\StoreAuctionAction;
use App\Actions\Tenant\Auctions\UpdateAuctionAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auctions\StoreAuctionRequest;
use App\Http\Requests\Tenant\Auctions\UpdateAuctionRequest;
use App\Models\Tenant\Auction;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use App\Services\Auctions\AuctionTemplateRenderer;
use App\Services\Auctions\CentralAuctionTemplateGateway;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuctionController extends Controller
{
    public function __construct(private readonly CentralAuctionTemplateGateway $templates) {}

    public function index(Request $request): View
    {
        $query = Auction::query()->latest();
        $query->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status));
        $query->when($request->string('type')->toString(), fn ($q, $type) => $q->where('type', $type));

        return view('tenant.auctions.index', [
            'auctions' => $query->paginate(20)->withQueryString(),
            'filters' => [
                'statuses' => ['in_preparation', 'pending_approval', 'active', 'completed', 'suspended'],
                'types' => ['electronic', 'in_person', 'hybrid'],
            ],
            'statistics' => [
                ['label' => 'إجمالي المزادات', 'value' => Auction::query()->count(), 'icon' => 'ti-gavel', 'tone' => 'primary'],
                ['label' => 'قيد الإعداد', 'value' => Auction::query()->where('status', 'in_preparation')->count(), 'icon' => 'ti-pencil', 'tone' => 'warning'],
                ['label' => 'بانتظار الاعتماد', 'value' => Auction::query()->where('status', 'pending_approval')->count(), 'icon' => 'ti-clock', 'tone' => 'info'],
                ['label' => 'مزادات نشطة', 'value' => Auction::query()->where('status', 'active')->count(), 'icon' => 'ti-activity', 'tone' => 'success'],
                ['label' => 'مزادات مكتملة', 'value' => Auction::query()->where('status', 'completed')->count(), 'icon' => 'ti-circle-check', 'tone' => 'danger'],
            ],
        ]);
    }

    public function create(): View { return view('tenant.auctions.create', ['auction' => new Auction(), 'templateOptions' => $this->templateOptions()] + $this->assignmentData()); }

    public function store(StoreAuctionRequest $request, StoreAuctionAction $action, WizardDraftService $drafts): RedirectResponse
    {
        $data = $this->dataWithCover($request);
        $auction = $action->execute($request->user('tenant'), $data);
        $drafts->discard($request, 'tenant.auctions.create');
        return to_route('tenant.auctions.show', $auction)->with('success', 'تم إنشاء المزاد وحفظ لقطات القوالب المنشورة.');
    }

    public function show(Auction $auction): View { return view('tenant.auctions.show', ['auction' => $auction]); }
    public function edit(Auction $auction): View { return view('tenant.auctions.edit', ['auction' => $auction->load('properties'), 'templateOptions' => $this->templateOptions()] + $this->assignmentData()); }

    public function update(UpdateAuctionRequest $request, Auction $auction, UpdateAuctionAction $action): RedirectResponse
    {
        $data = $this->dataWithCover($request);
        $action->execute($auction, $request->user('tenant'), $data);
        return to_route('tenant.auctions.show', $auction)->with('success', 'تم تحديث المزاد مع الحفاظ على توافق القوالب.');
    }

    public function preview(Auction $auction, string $type, AuctionTemplateRenderer $renderer): View
    {
        abort_unless(in_array($type, ['brochure', 'billboard', 'social'], true), 404);
        return view('tenant.auctions.preview', ['output' => $renderer->render($auction, $type), 'type' => $type, 'auction' => $auction]);
    }

    /** @return array<string, mixed> */
    private function dataWithCover(StoreAuctionRequest $request): array
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
