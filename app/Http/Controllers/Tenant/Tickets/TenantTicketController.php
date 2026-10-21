<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Tickets;

use App\DataTables\Tenant\Tickets\TenantTicketDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Tickets\ReplyTenantTicketRequest;
use App\Http\Requests\Tenant\Tickets\StoreTenantTicketRequest;
use App\Services\Tenants\Shared\CentralTicketGateway;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class TenantTicketController extends Controller
{
    public function __construct(
        private readonly CentralTicketGateway $ticketGateway
    ) {
        $this->middleware('can:tickets.view')->only(['index', 'show']);
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
        $ticket = $this->ticketGateway->createTicket($request->validated());

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

        $this->ticketGateway->replyTicket($ticketModel, (string) $request->validated('message'));

        return redirect()->route('tenant.tickets.show', $ticketModel->id)
            ->with('success', 'تم إرسال ردك بنجاح.');
    }
}
