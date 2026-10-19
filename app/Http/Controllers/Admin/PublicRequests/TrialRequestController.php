<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\PublicRequests;

use App\Actions\Admin\PublicRequests\ApproveTrialRequestAction;
use App\Actions\Admin\PublicRequests\RejectTrialRequestAction;
use App\DataTables\Admin\PublicRequests\TrialRequestsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublicRequests\ApproveTrialRequest;
use App\Http\Requests\Admin\PublicRequests\RejectTrialRequest;
use App\Enums\Admin\PublicRequests\TrialRequestStatus;
use App\Models\Admin\PublicRequests\TrialRequest;

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

        return $dataTable->render('admin.public_requests.trial.index', compact('stats', 'filterOptions'));
    }

    public function show(TrialRequest $trialRequest)
    {
        $this->authorize('view', $trialRequest);

        return view('admin.public_requests.trial.show', compact('trialRequest'));
    }
    public function approve(ApproveTrialRequest $request, TrialRequest $trialRequest) { $this->authorize('approve', $trialRequest); $this->approveAction->execute($trialRequest, $request->user('admin')); return redirect()->route('admin.clients.create', ['trial_request' => $trialRequest->getKey()])->with('trial_prefill', ['name' => $trialRequest->customer_name, 'email' => $trialRequest->customer_email, 'phone' => $trialRequest->customer_phone]); }
    public function reject(RejectTrialRequest $request, TrialRequest $trialRequest) { $this->authorize('reject', $trialRequest); $this->rejectAction->execute($trialRequest, $request->validated('rejection_reason'), $request->user('admin')); return back()->with('success', 'تم رفض طلب التجربة.'); }
}
