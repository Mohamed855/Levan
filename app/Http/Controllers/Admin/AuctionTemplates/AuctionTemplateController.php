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
use App\Http\Requests\Admin\UpdateActiveStatusRequest;
use App\Models\Admin\AuctionTemplate;
use App\Models\Admin\AuctionTemplateVersion;
use App\Services\Shared\Wizard\WizardDraftService;
use App\Support\Auctions\TemplateSchema;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuctionTemplateController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:auction_templates.view')->only(['index', 'show']);
        $this->middleware('can:auction_templates.create')->only(['create', 'store']);
        $this->middleware('can:auction_templates.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:auction_templates.delete')->only('destroy');
        $this->middleware('can:auction_templates.publish')->only('publish');
    }

    public function index(AuctionTemplateDataTable $dataTable)
    {
        $statistics = [
            'total'     => AuctionTemplate::query()->count(),
            'published' => AuctionTemplate::query()->where('status', 'published')->count(),
            'draft'     => AuctionTemplate::query()->where('status', 'draft')->count(),
            'active'    => AuctionTemplate::query()->where('is_active', true)->count(),
            'brochure'  => AuctionTemplate::query()->where('type', 'brochure')->count(),
        ];

        $filterOptions = [
            'types' => AuctionTemplateType::options(),
            'statuses' => [
                ['value' => 'published', 'label' => 'منشور'],
                ['value' => 'draft', 'label' => 'مسودة'],
                ['value' => 'archived', 'label' => 'مؤرشف'],
            ],
            'is_active' => [
                ['value' => 'active', 'label' => 'مفعل'],
                ['value' => 'inactive', 'label' => 'غير مفعل'],
            ],
        ];

        return $dataTable->render('admin.auction-templates.index', compact('statistics', 'filterOptions'));
    }

    public function create(): View
    {
        $this->authorize('create', AuctionTemplate::class);
        return view('admin.auction-templates.create', ['template' => new AuctionTemplate()]);
    }

    public function store(
        StoreAuctionTemplateRequest $request,
        CreateAuctionTemplateAction $action,
        TemplateSchema $schema,
        WizardDraftService $drafts
    ): RedirectResponse {
        $this->authorize('create', AuctionTemplate::class);
        $data = $request->validated();
        $data['schema'] = $schema->validate($data['schema'], $data['type']);
        $template = $action->execute($request->user('admin'), $data);
        $drafts->discard($request, 'admin.auction-templates.create');

        return to_route('admin.auction-templates.show', $template)->with('success', 'تم حفظ القالب بنجاح.');
    }

    public function show(AuctionTemplate $auctionTemplate): View
    {
        $this->authorize('view', $auctionTemplate);
        return view('admin.auction-templates.show', [
            'template' => $auctionTemplate->load('versions.creator'),
        ]);
    }

    public function edit(AuctionTemplate $auctionTemplate): View
    {
        $this->authorize('update', $auctionTemplate);
        return view('admin.auction-templates.edit', [
            'template' => $auctionTemplate->load('versions'),
        ]);
    }

    public function update(
        UpdateAuctionTemplateRequest $request,
        AuctionTemplate $auctionTemplate,
        UpdateAuctionTemplateAction $action,
        TemplateSchema $schema
    ): RedirectResponse {
        $this->authorize('update', $auctionTemplate);
        $data = $request->validated();
        if (isset($data['schema'])) {
            $data['schema'] = $schema->validate($data['schema'], $auctionTemplate->type);
        }
        $action->execute($auctionTemplate, $request->user('admin'), $data);

        return to_route('admin.auction-templates.show', $auctionTemplate)->with('success', 'تم تحديث القالب وحفظ نسخة جديدة من المخطط.');
    }

    public function publish(
        AuctionTemplate $auctionTemplate,
        AuctionTemplateVersion $version,
        PublishAuctionTemplateVersionAction $action
    ): RedirectResponse {
        $this->authorize('publish', $auctionTemplate);
        $action->execute($auctionTemplate, $version);

        return back()->with('success', 'تم نشر النسخة وهي جاهزة للاستخدام.');
    }

    public function toggleStatus(UpdateActiveStatusRequest $request, AuctionTemplate $auctionTemplate): JsonResponse|RedirectResponse
    {
        $this->authorize('update', $auctionTemplate);
        $newActive = $request->boolean('is_active');
        $auctionTemplate->update(['is_active' => $newActive]);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة القالب بنجاح.',
                'is_active' => $newActive,
            ]);
        }

        return back()->with('success', 'تم تحديث حالة القالب بنجاح.');
    }

    public function destroy(Request $request, AuctionTemplate $auctionTemplate): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $auctionTemplate);
        $auctionTemplate->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف القالب بنجاح.']);
        }

        return redirect()->route('admin.auction-templates.index')->with('success', 'تم حذف القالب بنجاح.');
    }
}
