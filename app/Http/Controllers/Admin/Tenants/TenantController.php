<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Tenants;

use App\Actions\Admin\Tenants\ProvisionTenantAction;
use App\Actions\Admin\PublicRequests\ProcessPublicRequestAction;
use App\DataTables\Admin\Tenants\TenantDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tenants\StoreTenantRequest;
use App\Models\Admin\City;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\PublicRequests\TrialRequest;
use App\Models\Admin\Tenants\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TenantController extends Controller
{
    public function index(TenantDataTable $dataTable): mixed
    {
        $statistics = [
            'total' => Tenant::query()->count(),
            'active' => Tenant::query()->whereHas('subscriptions', fn ($query) => $query->where('status', 'active'))->count(),
            'pending' => Tenant::query()->whereHas('subscriptions', fn ($query) => $query->where('status', 'pending'))->count(),
            'trial' => Tenant::query()->whereHas('subscriptions', fn ($query) => $query->where('status', 'trial'))->count(),
            'no_subscription' => Tenant::query()->doesntHave('subscriptions')->count(),
        ];

        $filterOptions = [
            'cities' => City::query()->select(['id', 'name'])->get()->map(fn ($c) => ['value' => (string) $c->id, 'label' => $c->name])->toArray(),
            'plans' => Plan::query()->select(['id', 'name'])->get()->map(fn ($p) => ['value' => (string) $p->id, 'label' => $p->name])->toArray(),
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'pending', 'label' => 'معلق'],
                ['value' => 'trial', 'label' => 'تجريبي'],
                ['value' => 'expired', 'label' => 'منتهي'],
                ['value' => 'cancelled', 'label' => 'ملغي'],
            ],
            'expiring' => [
                ['value' => '7', 'label' => 'خلال 7 أيام'],
                ['value' => '30', 'label' => 'خلال 30 يوماً'],
            ],
        ];

        return $dataTable->render('admin.tenants.index', compact('statistics', 'filterOptions'));
    }

    public function create(): View
    {
        return view('admin.tenants.create', ['plans' => Plan::query()->where('is_active', true)->orderBy('name')->get(), 'cities' => City::query()->orderBy('name')->get()]);
    }

    public function store(StoreTenantRequest $request, ProvisionTenantAction $action, ProcessPublicRequestAction $processAction): RedirectResponse
    {
        $validated = $request->validated();
        $tenant = $action->execute($validated);

        // Mark the originating TrialRequest as approved only after successful provision
        if (! empty($validated['trial_request_id'])) {
            $trialRequest = TrialRequest::query()->find((int) $validated['trial_request_id']);
            if ($trialRequest && $trialRequest->status === 'pending') {
                $processAction->approve($trialRequest, $request->user('admin'));
            }
        }

        return to_route('admin.tenants.show', $tenant)->with('success', 'تم إنشاء العميل وبيئته واشتراكه بنجاح.');
    }

    public function show(Tenant $tenant): View
    {
        return view('admin.tenants.show', ['tenant' => $tenant->load(['subscriptions.plan', 'subscriptions.currentPeriod'])]);
    }

    public function destroy(Tenant $tenant): mixed
    {
        $tenant->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف العميل بنجاح.']);
        }

        return to_route('admin.tenants.index')->with('success', 'تم حذف العميل بنجاح.');
    }
}
