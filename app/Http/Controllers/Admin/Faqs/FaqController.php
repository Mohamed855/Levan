<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Faqs;

use App\Actions\Admin\Faqs\SaveFaqAction;
use App\DataTables\Admin\Faqs\FaqDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Faqs\StoreFaqRequest;
use App\Http\Requests\Admin\Faqs\UpdateFaqRequest;
use App\Models\Admin\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class FaqController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:faq.view')->only('index');
        $this->middleware('can:faq.create')->only(['create', 'store']);
        $this->middleware('can:faq.update')->only(['edit', 'update', 'toggleStatus', 'destroy']);
    }

    public function index(FaqDataTable $dataTable)
    {
        $statistics = [
            'total'            => Faq::query()->count(),
            'active'           => Faq::query()->where('is_active', true)->count(),
            'inactive'         => Faq::query()->where('is_active', false)->count(),
            'added_this_month' => Faq::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'added_this_week'  => Faq::query()->where('created_at', '>=', now()->startOfWeek())->count(),
        ];

        $filterOptions = [
            'is_active' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'معطل'],
            ],
        ];

        return $dataTable->render('admin.faqs.index', compact('statistics', 'filterOptions'));
    }

    public function create(): View
    {
        return view('admin.faqs.create', ['faq' => new Faq()]);
    }

    public function store(StoreFaqRequest $request, SaveFaqAction $action): RedirectResponse
    {
        $action->create($request->validated());

        return to_route('admin.faqs.index')->with('success', 'تم إضافة السؤال الشائع بنجاح.');
    }

    public function edit(Faq $faq): View
    {
        return view('admin.faqs.edit', compact('faq'));
    }

    public function update(
        UpdateFaqRequest $request,
        Faq $faq,
        SaveFaqAction $action
    ): RedirectResponse {
        $action->update($faq, $request->validated());

        return to_route('admin.faqs.index')->with('success', 'تم تحديث السؤال الشائع بنجاح.');
    }

    public function toggleStatus(Faq $faq, SaveFaqAction $action): JsonResponse
    {
        $status = $action->toggleStatus($faq);

        return response()->json([
            'success'   => true,
            'is_active' => $status,
            'message'   => $status ? 'تم تفعيل السؤال بنجاح.' : 'تم تعطيل السؤال بنجاح.',
        ]);
    }

    public function destroy(
        Request $request,
        Faq $faq,
        SaveFaqAction $action
    ): RedirectResponse|JsonResponse {
        $action->delete($faq);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف السؤال الشائع بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف السؤال الشائع بنجاح.');
    }
}
