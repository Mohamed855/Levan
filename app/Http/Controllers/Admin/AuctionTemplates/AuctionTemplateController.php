<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AuctionTemplates;

use App\Actions\Admin\AuctionTemplates\CreateAuctionTemplateAction;
use App\Actions\Admin\AuctionTemplates\PublishAuctionTemplateVersionAction;
use App\Actions\Admin\AuctionTemplates\UpdateAuctionTemplateAction;
use App\DataTables\Admin\AuctionTemplates\AuctionTemplateDataTable;
use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuctionTemplates\StoreAuctionTemplateRequest;
use App\Http\Requests\Admin\AuctionTemplates\UpdateAuctionTemplateRequest;
use App\Http\Requests\Admin\AuctionTemplates\UpdateAuctionTemplateStatusRequest;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Models\Admin\AuctionTemplates\AuctionTemplateVersion;
use App\Services\Admin\AuctionTemplates\AuctionTemplateService;
use App\Support\Auctions\TemplateSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuctionTemplateController extends Controller
{
    private string $page = 'admin.auction-templates';

    public function __construct(
        private readonly AuctionTemplateService $service
    ) {
        $this->middleware('auth:admin');
        $this->authorizeResource(AuctionTemplate::class, 'auction_template');
        $this->middleware('can:auction_templates.view')->only(['index', 'show', 'showFields']);
        $this->middleware('can:auction_templates.create')->only(['create', 'store']);
        $this->middleware('can:auction_templates.edit')->only(['edit', 'update', 'toggleStatus', 'publish']);
        $this->middleware('can:auction_templates.delete')->only(['destroy']);
    }

    public function index(AuctionTemplateDataTable $dataTable)
    {
        $statistics = $this->service->getStatistics();
        $typeOptions = AuctionTemplateType::options();
        $typeFilterOptions = array_map(
            static fn (AuctionTemplateType $type): array => ['value' => $type->value, 'label' => $type->label()],
            AuctionTemplateType::cases()
        );

        $statusFilterOptions = [
            ['value' => '1', 'label' => 'مفعل'],
            ['value' => '0', 'label' => 'معطل'],
        ];

        return $dataTable->render($this->page.'.index', compact('statistics', 'typeOptions', 'typeFilterOptions', 'statusFilterOptions'));
    }

    public function create(): View
    {
        return view($this->page.'.create', [
            'template' => new AuctionTemplate,
            'typeOptions' => AuctionTemplateType::options(),
            'fieldGuide' => $this->service->getFieldGuide(),
        ]);
    }

    public function store(
        StoreAuctionTemplateRequest $request,
        CreateAuctionTemplateAction $action,
        TemplateSchema $schema
    ): RedirectResponse {
        $data = $request->validated();
        $data['page_backgrounds'] = $request->file('page_backgrounds', []);
        if (isset($data['schema'])) {
            $data['schema'] = $schema->validate($data['schema'], (string) $data['type']);
        }
        $data['is_active'] = $request->boolean('is_active', true);

        $template = $action->execute(
            $request->user('admin'),
            $data,
            $request->file('file')
        );

        return redirect()
            ->route('admin.auction-templates.show', $template->id)
            ->with('success', 'تم حفظ قالب المزاد بنجاح.');
    }

    public function show(AuctionTemplate $auctionTemplate): View
    {
        $auctionTemplate->load(['versions.creator', 'creator']);
        $fieldGuide = $this->service->getFieldGuide();
        $typeValue = is_object($auctionTemplate->type) ? $auctionTemplate->type->value : (string) $auctionTemplate->type;
        $typeGuide = $fieldGuide[$typeValue] ?? null;

        return view($this->page.'.show', [
            'template' => $auctionTemplate,
            'typeGuide' => $typeGuide,
        ]);
    }

    public function edit(AuctionTemplate $auctionTemplate): View
    {
        $auctionTemplate->load('versions');

        return view($this->page.'.edit', [
            'template' => $auctionTemplate,
            'typeOptions' => AuctionTemplateType::options(),
            'fieldGuide' => $this->service->getFieldGuide(),
        ]);
    }

    public function update(
        UpdateAuctionTemplateRequest $request,
        AuctionTemplate $auctionTemplate,
        UpdateAuctionTemplateAction $action,
        TemplateSchema $schema
    ): RedirectResponse {
        $data = $request->validated();
        $data['page_backgrounds'] = $request->file('page_backgrounds', []);
        $typeValue = is_object($auctionTemplate->type) ? $auctionTemplate->type->value : (string) $auctionTemplate->type;
        if (isset($data['schema'])) {
            $data['schema'] = $schema->validate($data['schema'], $typeValue);
        }
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $action->execute(
            $auctionTemplate,
            $request->user('admin'),
            $data,
            $request->file('file')
        );

        return redirect()
            ->route('admin.auction-templates.show', $auctionTemplate->id)
            ->with('success', 'تم تحديث القالب وحفظ نسخة جديدة من المخطط.');
    }

    public function publish(
        AuctionTemplate $auctionTemplate,
        AuctionTemplateVersion $version,
        PublishAuctionTemplateVersionAction $action
    ): RedirectResponse {
        $action->execute($auctionTemplate, $version);

        return redirect()->back()->with('success', 'تم نشر النسخة بنجاح وهي جاهزة للاستخدام.');
    }

    public function destroy(AdminAuthenticatedRequest $request, AuctionTemplate $auctionTemplate): JsonResponse|RedirectResponse
    {
        $this->service->delete($auctionTemplate);

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف قالب المزاد بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف قالب المزاد بنجاح.');
    }

    public function toggleStatus(UpdateAuctionTemplateStatusRequest $request, AuctionTemplate $auctionTemplate): JsonResponse
    {
        $updated = $this->service->toggleStatus($auctionTemplate);

        return response()->json([
            'success' => $updated,
            'status' => $auctionTemplate->is_active,
            'message' => $auctionTemplate->is_active ? 'تم تفعيل القالب بنجاح.' : 'تم تعطيل القالب بنجاح.',
        ]);
    }

    public function showFields(AuctionTemplate $auctionTemplate): JsonResponse
    {
        $fieldGuide = $this->service->getFieldGuide();
        $typeValue = is_object($auctionTemplate->type) ? $auctionTemplate->type->value : (string) $auctionTemplate->type;
        $expected = $fieldGuide[$typeValue]['fields'] ?? [];
        $extracted = $auctionTemplate->field_names ?? [];

        $fieldComparison = array_map(function ($field) use ($extracted) {
            return [
                'name' => $field['name'],
                'label' => $field['label'],
                'required' => $field['required'],
                'found' => in_array($field['name'], $extracted, true),
            ];
        }, $expected);

        return response()->json([
            'template' => [
                'id' => $auctionTemplate->id,
                'name' => $auctionTemplate->name,
                'type' => $typeValue,
                'totalFields' => count($extracted),
            ],
            'fields' => $fieldComparison,
        ]);
    }
}
