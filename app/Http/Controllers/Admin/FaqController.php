<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Faqs\StoreFaqRequest;
use App\Http\Requests\Admin\Faqs\UpdateFaqRequest;
use App\Models\Admin\Faq;
use App\Services\Admin\Faqs\FaqService;
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

    public function index(): View
    {
        return view('admin.faqs.index', ['faqs' => Faq::latest()->paginate(20)]);
    }

    public function create(): View
    {
        return view('admin.faqs.form', ['faq' => new Faq]);
    }

    public function store(StoreFaqRequest $r): RedirectResponse
    {
        $this->faqService->create($r->validated() + ['is_active' => $r->boolean('is_active')]);

        return redirect()->route('admin.faqs.index')->with('success', 'تمت إضافة السؤال بنجاح.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.form', compact('faq'));
    }

    public function update(UpdateFaqRequest $r, Faq $faq): RedirectResponse
    {
        $this->faqService->update($faq, $r->validated() + ['is_active' => $r->boolean('is_active')]);

        return redirect()->route('admin.faqs.index')->with('success', 'تم تحديث السؤال بنجاح.');
    }

    public function destroy(AdminAuthenticatedRequest $request, Faq $faq): RedirectResponse
    {
        $this->faqService->delete($faq);

        return redirect()->route('admin.faqs.index')->with('success', 'تم حذف السؤال بنجاح.');
    }
}
