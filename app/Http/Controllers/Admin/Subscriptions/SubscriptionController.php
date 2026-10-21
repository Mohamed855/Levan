<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Subscriptions;

use App\Actions\Admin\Subscriptions\UpdateSubscriptionStatusAction;
use App\DataTables\Admin\Subscriptions\SubscriptionDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Subscriptions\UpdateSubscriptionStatusRequest;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscriptions\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:subscriptions.view')->only(['index', 'show']);
        $this->middleware('can:subscriptions.update')->only('updateStatus');
        $this->middleware('can:subscriptions.delete')->only('destroy');
    }

    public function index(SubscriptionDataTable $dataTable): mixed
    {
        $stats = [
            'total'    => Subscription::query()->count(),
            'active'   => Subscription::query()->where('status', 'active')->count(),
            'pending'  => Subscription::query()->where('status', 'pending')->count(),
            'expired'  => Subscription::query()->where('status', 'expired')->count(),
            'expiring' => Subscription::query()
                ->where('status', 'active')
                ->whereBetween('ends_at', [now(), now()->addDays(30)])
                ->count(),
        ];

        $filterOptions = [
            'statuses' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'pending', 'label' => 'قيد المراجعة'],
                ['value' => 'suspended', 'label' => 'معلق'],
                ['value' => 'expired', 'label' => 'منتهي'],
                ['value' => 'cancelled', 'label' => 'ملغي'],
                ['value' => 'trial', 'label' => 'فترة تجربة'],
            ],
            'plans' => Plan::query()->select(['id', 'name'])->get()->map(fn ($p) => ['value' => (string) $p->id, 'label' => $p->name])->toArray(),
            'expiring' => [
                ['value' => '7', 'label' => 'خلال 7 أيام'],
                ['value' => '30', 'label' => 'خلال 30 يوماً'],
            ],
        ];

        return $dataTable->render('admin.subscriptions.index', compact('stats', 'filterOptions'));
    }

    public function show(Subscription $subscription): View
    {
        $subscription->load(['tenant', 'plan', 'periods']);

        $allowedTransitions = UpdateSubscriptionStatusRequest::allowedTransitions()[$subscription->status] ?? [];

        return view('admin.subscriptions.show', compact('subscription', 'allowedTransitions'));
    }

    public function updateStatus(
        UpdateSubscriptionStatusRequest $request,
        Subscription $subscription,
        UpdateSubscriptionStatusAction $action,
    ): RedirectResponse {
        $action->execute($subscription, $request->validated('status'));

        return back()->with('success', 'تم تحديث حالة الاشتراك بنجاح.');
    }

    public function destroy(Subscription $subscription): mixed
    {
        $subscription->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الاشتراك بنجاح.']);
        }

        return to_route('admin.subscriptions.index')->with('success', 'تم حذف الاشتراك بنجاح.');
    }
}
