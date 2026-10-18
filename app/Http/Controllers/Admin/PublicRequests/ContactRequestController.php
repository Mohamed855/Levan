<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\PublicRequests;

use App\Actions\Admin\PublicRequests\ReplyToContactRequestAction;
use App\DataTables\Admin\PublicRequests\ContactRequestsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PublicRequests\ReplyContactRequest;
use App\Enums\Admin\PublicRequests\ContactRequestStatus;
use App\Models\Admin\PublicRequests\ContactRequest;

final class ContactRequestController extends Controller
{
    public function __construct(private readonly ReplyToContactRequestAction $replyAction)
    {
        $this->middleware('can:contact_requests.view')->only(['index', 'show']);
        $this->middleware('can:contact_requests.reply')->only('reply');
    }

    public function index(ContactRequestsDataTable $dataTable)
    {
        $query = ContactRequest::query();
        $stats = [
            'total' => (clone $query)->count(),
            'pending' => (clone $query)->where('status', ContactRequestStatus::PENDING)->count(),
            'replied' => (clone $query)->where('status', ContactRequestStatus::REPLIED)->count(),
        ];
        $filterOptions = ['statuses' => collect(ContactRequestStatus::cases())->map(fn ($status) => [
            'value' => $status->value,
            'label' => $status->label(),
        ])->all()];

        return $dataTable->render('admin.public_requests.contact.index', compact('stats', 'filterOptions'));
    }

    public function show(ContactRequest $contactRequest)
    {
        $this->authorize('view', $contactRequest);

        return view('admin.public_requests.contact.show', compact('contactRequest'));
    }
    public function reply(ReplyContactRequest $request, ContactRequest $contactRequest) { $this->authorize('reply', $contactRequest); $this->replyAction->execute($contactRequest, $request->validated('reply'), $request->user('admin')); return back()->with('success', 'تم إرسال الرد بنجاح.'); }
}
