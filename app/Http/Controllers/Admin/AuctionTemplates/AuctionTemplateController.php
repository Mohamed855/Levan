<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AuctionTemplates;

use App\Actions\Admin\AuctionTemplates\CreateAuctionTemplateAction;
use App\Actions\Admin\AuctionTemplates\UpdateAuctionTemplateAction;
use App\DataTables\Admin\AuctionTemplates\AuctionTemplateDataTable;
use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuctionTemplates\StoreAuctionTemplateRequest;
use App\Http\Requests\Admin\AuctionTemplates\UpdateAuctionTemplateRequest;
use App\Http\Requests\Admin\AuctionTemplates\UpdateAuctionTemplateStatusRequest;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Models\Admin\AuctionTemplates\AuctionTemplate;
use App\Services\Admin\AuctionTemplates\AuctionTemplateService;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AuctionTemplateController extends Controller
{
    private string $page = 'admin.auction_templates';

    public function __construct(
        private readonly AuctionTemplateService $service
    ) {
        $this->middleware('auth:admin');
        $this->authorizeResource(AuctionTemplate::class, 'auction_template');
        $this->middleware('can:auction_templates.view')->only(['index', 'show', 'showFields']);
        $this->middleware('can:auction_templates.create')->only(['create', 'store']);
        $this->middleware('can:auction_templates.edit')->only(['edit', 'update', 'toggleStatus']);
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

        return $dataTable->render($this->page . '.index', compact('statistics', 'typeOptions', 'typeFilterOptions', 'statusFilterOptions'));
    }

    public function create(): View
    {
        return view($this->page . '.create', [
            'typeOptions' => AuctionTemplateType::options(),
            'fieldGuide'  => $this->service->getFieldGuide(),
        ]);
    }

    public function store(
        StoreAuctionTemplateRequest $request,
        CreateAuctionTemplateAction $action
    ): RedirectResponse {
        $data = $request->safe()->except(['file', 'thumbnail']);
        $data['is_active'] = $request->boolean('is_active', true);

        $action->execute(
            $data,
            $request->file('file'),
            $request->file('thumbnail')
        );

        return redirect()
            ->route('admin.auction-templates.index')
            ->with('success', 'تم إضافة قالب المزاد بنجاح واستخراج حقوله.');
    }

    public function show(AuctionTemplate $auctionTemplate): View
    {
        $fieldGuide = $this->service->getFieldGuide();
        $typeGuide = $fieldGuide[$auctionTemplate->type->value] ?? null;

        return view($this->page . '.show', [
            'template'  => $auctionTemplate,
            'typeGuide' => $typeGuide,
        ]);
    }

    public function edit(AuctionTemplate $auctionTemplate): View
    {
        return view($this->page . '.edit', [
            'template'    => $auctionTemplate,
            'typeOptions' => AuctionTemplateType::options(),
            'fieldGuide'  => $this->service->getFieldGuide(),
        ]);
    }

    public function update(
        UpdateAuctionTemplateRequest $request,
        AuctionTemplate $auctionTemplate,
        UpdateAuctionTemplateAction $action
    ): RedirectResponse {
        $data = $request->safe()->except(['file', 'thumbnail']);
        if ($request->has('is_active')) {
            $data['is_active'] = $request->boolean('is_active');
        }

        $action->execute(
            $auctionTemplate,
            $data,
            $request->file('file'),
            $request->file('thumbnail')
        );

        return redirect()
            ->route('admin.auction-templates.index')
            ->with('success', 'تم تحديث قالب المزاد بنجاح.');
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
            'status'  => $auctionTemplate->is_active,
            'message' => $auctionTemplate->is_active ? 'تم تفعيل القالب بنجاح.' : 'تم تعطيل القالب بنجاح.',
        ]);
    }

    public function showFields(AuctionTemplate $auctionTemplate): JsonResponse
    {
        $fieldGuide = $this->service->getFieldGuide();
        $expected = $fieldGuide[$auctionTemplate->type->value]['fields'] ?? [];
        $extracted = $auctionTemplate->field_names ?? [];

        $fieldComparison = array_map(function ($field) use ($extracted) {
            return [
                'name'     => $field['name'],
                'label'    => $field['label'],
                'required' => $field['required'],
                'found'    => in_array($field['name'], $extracted, true),
            ];
        }, $expected);

        $unexpected = array_values(array_diff($extracted, array_column($expected, 'name')));

        return response()->json([
            'template'   => [
                'id'          => $auctionTemplate->id,
                'name'        => $auctionTemplate->name,
                'type'        => $auctionTemplate->type->label(),
                'file_url'    => $auctionTemplate->file_url,
                'fields_count'=> count($extracted),
            ],
            'comparison' => $fieldComparison,
            'unexpected' => $unexpected,
            'all_fields' => $extracted,
        ]);
    }
}
