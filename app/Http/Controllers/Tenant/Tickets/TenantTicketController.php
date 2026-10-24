<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Tickets;

use App\DataTables\Tenant\Tickets\TenantTicketDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Tickets\ReplyTenantTicketRequest;
use App\Http\Requests\Tenant\Tickets\StoreTenantTicketRequest;
use App\Services\Tenants\Shared\CentralTicketGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class TenantTicketController extends Controller
{
    public function __construct(
        private readonly CentralTicketGateway $ticketGateway
    ) {
        $this->middleware('can:tickets.view')->only(['index', 'show', 'downloadAttachment']);
        $this->middleware('can:tickets.create')->only(['create', 'store']);
        $this->middleware('can:tickets.reply')->only(['reply']);
    }

    public function index(TenantTicketDataTable $dataTable)
    {
        return $dataTable->render('tenant.tickets.index', [
            'stats' => $dataTable->getStats(),
        ]);
    }

    public function create(): View
    {
        return view('tenant.tickets.create');
    }

    public function store(StoreTenantTicketRequest $request): RedirectResponse
    {
        $attachments = array_values(array_filter($request->file('attachments', []), fn (mixed $file): bool => $file instanceof UploadedFile));
        $user = $request->user('tenant');
        abort_unless($user !== null, 403);
        $ticket = $this->ticketGateway->createTicket($request->validated(), $user, $attachments);

        return redirect()->route('tenant.tickets.show', $ticket->id)
            ->with('success', 'تم إنشاء تذكرة الدعم الفني بنجاح، وسيقوم فريق الدعم بالرد قريباً.');
    }

    public function show(int|string $ticket): View
    {
        $ticketModel = $this->ticketGateway->ticketForCurrentTenant($ticket);

        return view('tenant.tickets.show', [
            'ticket' => $ticketModel,
        ]);
    }

    public function reply(ReplyTenantTicketRequest $request, int|string $ticket): RedirectResponse
    {
        $ticketModel = $this->ticketGateway->ticketForCurrentTenant($ticket);

        $this->ticketGateway->replyTicket(
            $ticketModel,
            (string) $request->validated('message'),
            $request->user('tenant'),
        );

        return redirect()->route('tenant.tickets.show', $ticketModel->id)
            ->with('success', 'تم إرسال ردك بنجاح.');
    }

    public function downloadAttachment(int|string $ticket, int|string $attachment)
    {
        $ticketModel = $this->ticketGateway->ticketForCurrentTenant($ticket);
        $attachmentModel = $this->ticketGateway->attachmentForCurrentTenant($ticketModel, $attachment);
        abort_unless(Storage::disk('local')->exists($attachmentModel->path), 404);

        return Storage::disk('local')->download($attachmentModel->path, $attachmentModel->original_name);
    }
}
