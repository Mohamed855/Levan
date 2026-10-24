<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Subscriptions;

use App\Actions\Admin\Subscriptions\UpdateSubscriptionStatusAction;
use App\Actions\Admin\Subscriptions\ReviewPendingSubscriptionsAction;
use App\DataTables\Admin\Subscriptions\SubscriptionDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Subscriptions\UpdateSubscriptionStatusRequest;
use App\Http\Requests\Admin\Subscriptions\ReviewPendingSubscriptionRequest;
use App\Http\Requests\Admin\Subscriptions\ReviewPendingSubscriptionsRequest;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Subscriptions\Subscription;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

final class SubscriptionController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:subscriptions.view')->only(['index', 'show', 'pending', 'expiring', 'expired']);
        $this->middleware('can:subscriptions.update')->only(['updateStatus', 'accept', 'reject', 'massAccept', 'massReject']);
        $this->middleware('can:subscriptions.delete')->only('destroy');
    }

    public function index(SubscriptionDataTable $dataTable): mixed
    {
        return $this->renderIndex($dataTable);
    }

    public function pending(SubscriptionDataTable $dataTable): mixed
    {
        return $this->renderIndex($dataTable, 'pending');
    }

    public function expiring(SubscriptionDataTable $dataTable): mixed
    {
        return $this->renderIndex($dataTable, null, 30);
    }

    public function expired(SubscriptionDataTable $dataTable): mixed
    {
        return $this->renderIndex($dataTable, 'expired');
    }

    private function renderIndex(SubscriptionDataTable $dataTable, ?string $initialStatus = null, ?int $initialExpiring = null): mixed
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

        return $dataTable->render('admin.subscriptions.index', compact('stats', 'filterOptions', 'initialStatus', 'initialExpiring'));
    }

    public function show(Subscription $subscription): View
    {
        $this->authorize('view', $subscription);

        $subscription->load(['tenant', 'plan', 'periods']);

        $allowedTransitions = UpdateSubscriptionStatusRequest::allowedTransitions()[$subscription->status] ?? [];

        return view('admin.subscriptions.show', compact('subscription', 'allowedTransitions'));
    }

    public function updateStatus(
        UpdateSubscriptionStatusRequest $request,
        Subscription $subscription,
        UpdateSubscriptionStatusAction $action,
    ): RedirectResponse {
        $this->authorize('update', $subscription);

        $action->execute($subscription, $request->validated('status'));

        return back()->with('success', 'تم تحديث حالة الاشتراك بنجاح.');
    }

    public function accept(Subscription $subscription, ReviewPendingSubscriptionsAction $review): RedirectResponse
    {
        $this->authorize('update', $subscription);
        $review->accept($subscription);

        return back()->with('success', 'تم قبول الاشتراك وتفعيل فترته الحالية.');
    }

    public function reject(ReviewPendingSubscriptionRequest $request, Subscription $subscription, ReviewPendingSubscriptionsAction $review): RedirectResponse
    {
        $this->authorize('update', $subscription);
        $review->reject($subscription, $request->validated('reason'));

        return back()->with('success', 'تم رفض الاشتراك وتسجيل السبب عند إدخاله.');
    }

    public function massAccept(ReviewPendingSubscriptionsRequest $request, ReviewPendingSubscriptionsAction $review): \Illuminate\Http\JsonResponse
    {
        try {
            $count = $review->acceptMany($request->validated('ids'));
        } catch (RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => "تم قبول {$count} اشتراك/اشتراكات."]);
    }

    public function massReject(ReviewPendingSubscriptionsRequest $request, ReviewPendingSubscriptionsAction $review): \Illuminate\Http\JsonResponse
    {
        try {
            $count = $review->rejectMany($request->validated('ids'), $request->validated('reason'));
        } catch (RuntimeException $exception) {
            return response()->json(['success' => false, 'message' => $exception->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => "تم رفض {$count} اشتراك/اشتراكات."]);
    }

    public function destroy(Subscription $subscription): mixed
    {
        $this->authorize('delete', $subscription);

        $subscription->delete();

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الاشتراك بنجاح.']);
        }

        return to_route('admin.subscriptions.index')->with('success', 'تم حذف الاشتراك بنجاح.');
    }
}
