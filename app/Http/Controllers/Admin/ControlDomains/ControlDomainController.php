<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\ControlDomains;

use App\Actions\Admin\ControlDomains\SaveControlDomainAction;
use App\DataTables\Admin\ControlDomains\ControlDomainDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ControlDomains\StoreControlDomainRequest;
use App\Http\Requests\Admin\ControlDomains\UpdateControlDomainRequest;
use App\Models\Admin\ControlDomain;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ControlDomainController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:control_domains.view')->only('index');
        $this->middleware('can:control_domains.create')->only(['create', 'store']);
        $this->middleware('can:control_domains.update')->only(['edit', 'update', 'toggleStatus', 'destroy']);
    }

    public function index(ControlDomainDataTable $dataTable)
    {
        $statistics = [
            'total'            => ControlDomain::query()->count(),
            'active'           => ControlDomain::query()->where('is_active', true)->count(),
            'inactive'         => ControlDomain::query()->where('is_active', false)->count(),
            'added_this_month' => ControlDomain::query()->where('created_at', '>=', now()->startOfMonth())->count(),
            'added_this_week'  => ControlDomain::query()->where('created_at', '>=', now()->startOfWeek())->count(),
        ];

        $filterOptions = [
            'is_active' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'معطل'],
            ],
        ];

        return $dataTable->render('admin.control-domains.index', compact('statistics', 'filterOptions'));
    }

    public function create(): View
    {
        return view('admin.control-domains.create', ['controlDomain' => new ControlDomain()]);
    }

    public function store(StoreControlDomainRequest $request, SaveControlDomainAction $action): RedirectResponse
    {
        $action->create($request->validated());

        return to_route('admin.control-domains.index')->with('success', 'تم إضافة النطاق بنجاح.');
    }

    public function edit(ControlDomain $controlDomain): View
    {
        return view('admin.control-domains.edit', compact('controlDomain'));
    }

    public function update(
        UpdateControlDomainRequest $request,
        ControlDomain $controlDomain,
        SaveControlDomainAction $action
    ): RedirectResponse {
        $action->update($controlDomain, $request->validated());

        return to_route('admin.control-domains.index')->with('success', 'تم تحديث النطاق بنجاح.');
    }

    public function toggleStatus(ControlDomain $controlDomain, SaveControlDomainAction $action): JsonResponse
    {
        $status = $action->toggleStatus($controlDomain);

        return response()->json([
            'success'   => true,
            'is_active' => $status,
            'message'   => $status ? 'تم تفعيل النطاق بنجاح.' : 'تم تعطيل النطاق بنجاح.',
        ]);
    }

    public function destroy(
        Request $request,
        ControlDomain $controlDomain,
        SaveControlDomainAction $action
    ): RedirectResponse|JsonResponse {
        $action->delete($controlDomain);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف النطاق بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تم حذف النطاق بنجاح.');
    }
}
