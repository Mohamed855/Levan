<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\PublicRequests;

use App\Actions\Admin\PublicRequests\ProcessPublicRequestAction;
use App\DataTables\Admin\PublicRequests\ContactRequestsDataTable;
use App\DataTables\Admin\PublicRequests\TrialRequestsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublicRequests\ApproveTrialRequest;
use App\Http\Requests\Admin\PublicRequests\RejectTrialRequest;
use App\Http\Requests\Admin\PublicRequests\ReplyContactRequest;
use App\Models\Admin\PublicRequests\ContactRequest;
use App\Models\Admin\PublicRequests\TrialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class PublicRequestController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:public_requests.view')->only(['contacts', 'contact', 'trials']);
        $this->middleware('can:public_requests.reply')->only('reply');
        $this->middleware('can:public_requests.process')->only(['approve', 'reject']);
    }

    public function contacts(ContactRequestsDataTable $dataTable)
    {
        $stats = [
            'total'    => ContactRequest::query()->count(),
            'pending'  => ContactRequest::query()->where('status', 'pending')->count(),
            'replied'  => ContactRequest::query()->where('status', 'replied')->count(),
            'today'    => ContactRequest::query()->whereDate('created_at', today())->count(),
            'this_month' => ContactRequest::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return $dataTable->render('admin.public-requests.contacts', compact('stats'));
    }

    public function contact(ContactRequest $contactRequest): View
    {
        return view('admin.public-requests.contact-show', compact('contactRequest'));
    }

    public function reply(
        ReplyContactRequest $request,
        ContactRequest $contactRequest,
        ProcessPublicRequestAction $action,
    ): RedirectResponse {
        abort_if($contactRequest->status === 'replied', 409, 'هذا الطلب مُجاب عنه بالفعل.');

        $action->reply($contactRequest, $request->validated('reply'), $request->user('admin'));

        return back()->with('success', 'تم حفظ الرد بنجاح.');
    }

    public function trials(TrialRequestsDataTable $dataTable)
    {
        $stats = [
            'total'    => TrialRequest::query()->count(),
            'pending'  => TrialRequest::query()->where('status', 'pending')->count(),
            'approved' => TrialRequest::query()->where('status', 'approved')->count(),
            'rejected' => TrialRequest::query()->where('status', 'rejected')->count(),
            'this_month' => TrialRequest::query()->whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
        ];

        return $dataTable->render('admin.public-requests.trials', compact('stats'));
    }

    public function approve(
        ApproveTrialRequest $request,
        TrialRequest $trialRequest,
    ): RedirectResponse {
        abort_unless($trialRequest->status === 'pending', 409, 'هذا الطلب لم يعد معلقاً.');

        return to_route('admin.tenants.create', [
            'trial_request_id'      => $trialRequest->id,
            'company_name'          => $trialRequest->customer_name,
            'primary_contact_email' => $trialRequest->customer_email,
            'primary_contact_phone' => $trialRequest->customer_phone ?? null,
        ])->with('info', 'تم فتح نموذج إنشاء العميل — يُرجى إكمال البيانات لإتمام الإنشاء.');
    }

    public function reject(
        RejectTrialRequest $request,
        TrialRequest $trialRequest,
        ProcessPublicRequestAction $action,
    ): RedirectResponse {
        $action->reject($trialRequest, $request->validated('reason'), $request->user('admin'));

        return back()->with('success', 'تم رفض الطلب بنجاح.');
    }
}
