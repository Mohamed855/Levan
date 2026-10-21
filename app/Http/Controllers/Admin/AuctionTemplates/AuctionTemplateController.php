<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\AuctionTemplates;

use App\Actions\Admin\AuctionTemplates\CreateAuctionTemplateAction;
use App\Actions\Admin\AuctionTemplates\PublishAuctionTemplateVersionAction;
use App\Actions\Admin\AuctionTemplates\UpdateAuctionTemplateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AuctionTemplates\StoreAuctionTemplateRequest;
use App\Http\Requests\Admin\AuctionTemplates\UpdateAuctionTemplateRequest;
use App\Models\Admin\AuctionTemplate;
use App\Models\Admin\AuctionTemplateVersion;
use App\Support\Auctions\TemplateSchema;
use App\Services\Shared\Wizard\WizardDraftService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AuctionTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuctionTemplate::query()->withCount('versions')->latest();
        $query->when($request->string('type')->toString(), fn ($q, $type) => $q->where('type', $type));
        $query->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status));

        return view('admin.auction-templates.index', [
            'templates' => $query->paginate(20)->withQueryString(),
            'filters' => ['types' => AuctionTemplate::TYPES, 'statuses' => ['draft', 'published', 'archived']],
            'statistics' => [
                ['label' => 'إجمالي القوالب', 'value' => AuctionTemplate::query()->count(), 'icon' => 'ti-templates', 'tone' => 'primary'],
                ['label' => 'القوالب المنشورة', 'value' => AuctionTemplate::query()->where('status', 'published')->count(), 'icon' => 'ti-world-check', 'tone' => 'success'],
                ['label' => 'قوالب المسودة', 'value' => AuctionTemplate::query()->where('status', 'draft')->count(), 'icon' => 'ti-pencil', 'tone' => 'warning'],
                ['label' => 'القوالب النشطة', 'value' => AuctionTemplate::query()->where('is_active', true)->count(), 'icon' => 'ti-toggle-right', 'tone' => 'info'],
                ['label' => 'قوالب الكتيبات', 'value' => AuctionTemplate::query()->where('type', 'brochure')->count(), 'icon' => 'ti-notebook', 'tone' => 'danger'],
            ],
        ]);
    }

    public function create(): View { return view('admin.auction-templates.create', ['template' => new AuctionTemplate()]); }

    public function store(StoreAuctionTemplateRequest $request, CreateAuctionTemplateAction $action, TemplateSchema $schema, WizardDraftService $drafts): RedirectResponse
    {
        $data = $request->validated();
        $data['schema'] = $schema->validate($data['schema'], $data['type']);
        $template = $action->execute($request->user('admin'), $data);
        $drafts->discard($request, 'admin.auction-templates.create');
        return to_route('admin.auction-templates.show', $template)->with('success', 'تم حفظ القالب بنجاح.');
    }

    public function show(AuctionTemplate $auctionTemplate): View { return view('admin.auction-templates.show', ['template' => $auctionTemplate->load('versions.creator')]); }
    public function edit(AuctionTemplate $auctionTemplate): View { return view('admin.auction-templates.edit', ['template' => $auctionTemplate->load('versions')]); }

    public function update(UpdateAuctionTemplateRequest $request, AuctionTemplate $auctionTemplate, UpdateAuctionTemplateAction $action, TemplateSchema $schema): RedirectResponse
    {
        $data = $request->validated();
        if (isset($data['schema'])) { $data['schema'] = $schema->validate($data['schema'], $auctionTemplate->type); }
        $action->execute($auctionTemplate, $request->user('admin'), $data);
        return to_route('admin.auction-templates.show', $auctionTemplate)->with('success', 'تم تحديث القالب وحفظ نسخة جديدة من المخطط.');
    }

    public function publish(AuctionTemplate $auctionTemplate, AuctionTemplateVersion $version, PublishAuctionTemplateVersionAction $action): RedirectResponse
    {
        $action->execute($auctionTemplate, $version);
        return back()->with('success', 'تم نشر النسخة وهي جاهزة للاستخدام.');
    }
    public function toggleStatus(AuctionTemplate $auctionTemplate): RedirectResponse
    {
        $auctionTemplate->update(['is_active' => ! $auctionTemplate->is_active]);

        return back();
    }
}
