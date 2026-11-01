<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\DataTables\Admin\Faq\FaqDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Faqs\StoreFaqRequest;
use App\Http\Requests\Admin\Faqs\UpdateFaqRequest;
use App\Models\Admin\Faq;
use App\Services\Admin\Faqs\FaqService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class FaqController extends Controller
{
    public function __construct(
        private readonly FaqService $faqService
    ) {
        $this->authorizeResource(Faq::class, 'faq');
        $this->middleware('can:faqs.view')->only('index');
    }

    public function index(FaqDataTable $dataTable): mixed
    {
        $statistics = [
            'total' => Faq::count(),
            'active' => Faq::where('is_active', true)->count(),
            'inactive' => Faq::where('is_active', false)->count(),
        ];

        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'غير نشط'],
            ],
        ];

        return $dataTable->render('admin.faqs.index', compact('statistics', 'filterOptions'));
    }

    public function toggleStatus(AdminAuthenticatedRequest $request, Faq $faq): JsonResponse
    {
        $this->authorize('update', $faq);

        $faq->update(['is_active' => ! $faq->is_active]);
        $status = (bool) $faq->fresh()->is_active;

        return response()->json([
            'success' => true,
            'message' => $status ? 'تم تفعيل السؤال بنجاح' : 'تم تعطيل السؤال بنجاح',
            'status' => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.faqs.create', ['faq' => new Faq]);
    }

    public function store(StoreFaqRequest $r): RedirectResponse
    {
        $this->faqService->create($r->validated() + ['is_active' => $r->boolean('is_active')]);

        return redirect()->route('admin.faqs.index')->with('success', 'تمت إضافة السؤال بنجاح.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(UpdateFaqRequest $r, Faq $faq): RedirectResponse
    {
        $this->faqService->update($faq, $r->validated() + ['is_active' => $r->boolean('is_active')]);

        return redirect()->route('admin.faqs.index')->with('success', 'تم تحديث السؤال بنجاح.');
    }

    public function destroy(AdminAuthenticatedRequest $request, Faq $faq): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $this->faqService->delete($faq);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف السؤال بنجاح.']);
        }

        return redirect()->route('admin.faqs.index')->with('success', 'تم حذف السؤال بنجاح.');
    }
}
