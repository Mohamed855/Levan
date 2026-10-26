<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\PublicRequests;

use App\Actions\Admin\PublicRequests\ApproveTrialRequestAction;
use App\Actions\Admin\PublicRequests\RejectTrialRequestAction;
use App\DataTables\Admin\PublicRequests\TrialRequestsDataTable;
use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublicRequests\ApproveTrialRequest;
use App\Http\Requests\Admin\PublicRequests\RejectTrialRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use Illuminate\Validation\ValidationException;

final class TrialRequestController extends Controller
{
    public function __construct(private readonly ApproveTrialRequestAction $approveAction, private readonly RejectTrialRequestAction $rejectAction)
    {
        $this->middleware('can:trial_requests.view')->only(['index', 'show']);
        $this->middleware('can:trial_requests.approve')->only('approve');
        $this->middleware('can:trial_requests.reject')->only('reject');
    }

    public function index(TrialRequestsDataTable $dataTable)
    {
        $query = TrialRequest::query();
        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', TrialRequestStatus::PENDING)->count(),
            'approved' => (clone $query)->where('status', TrialRequestStatus::APPROVED)->count(),
            'rejected' => (clone $query)->where('status', TrialRequestStatus::REJECTED)->count(),
        ];
        $filterOptions = ['statuses' => collect(TrialRequestStatus::cases())->map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->all()];

        return $dataTable->render('admin.public-requests.trial.index', compact('stats', 'filterOptions'));
    }

    public function show(TrialRequest $trialRequest)
    {
        $this->authorize('view', $trialRequest);

        return view('admin.public-requests.trial.show', compact('trialRequest'));
    }

    public function approve(ApproveTrialRequest $request, TrialRequest $trialRequest)
    {
        $this->authorize('approve', $trialRequest);
        try {
            $this->approveAction->execute($trialRequest, $request->user('admin'));
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            report($exception);

            return back()->with('error', 'تعذر إنشاء حساب التجربة. يمكنك إعادة المحاولة من هذا الطلب.');
        }

        return redirect()->route('admin.trial-requests.show', $trialRequest)->with('success', 'تم إنشاء حساب العميل وتفعيل تجربة 7 أيام. رابط الدخول متاح أدناه.');
    }

    public function reject(RejectTrialRequest $request, TrialRequest $trialRequest)
    {
        $this->authorize('reject', $trialRequest);
        $this->rejectAction->execute($trialRequest, $request->validated('rejection_reason'), $request->user('admin'));

        return back()->with('success', 'تم رفض طلب التجربة.');
    }
}
