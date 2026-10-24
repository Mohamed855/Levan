<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Tickets;

use App\Actions\Admin\Tickets\ReplyTicketAction;
use App\Actions\Admin\Tickets\UpdateTicketStatusAction;
use App\Actions\Admin\Tickets\UpdateTicketServiceAction;
use App\DataTables\Admin\Tickets\TicketDataTable;
use App\Enums\Admin\Tickets\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tickets\ReplyTicketRequest;
use App\Http\Requests\Admin\Tickets\UpdateTicketStatusRequest;
use App\Http\Requests\Admin\Tickets\UpdateTicketServiceRequest;
use App\Models\Admin\Ticket;
use App\Models\Admin\TicketAttachment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

final class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:tickets.view')->only(['index', 'show']);
        $this->middleware('can:tickets.reply')->only('reply');
        $this->middleware('can:tickets.update')->only('updateStatus');
        $this->middleware('can:tickets.delete')->only('destroy');
    }

    public function index(TicketDataTable $dataTable)
    {
        $statistics = [
            'total'       => Ticket::query()->count(),
            'open'        => Ticket::query()->where('status', 'open')->count(),
            'in_progress' => Ticket::query()->where('status', 'in_progress')->count(),
            'resolved'    => Ticket::query()->where('status', 'resolved')->count(),
            'closed'      => Ticket::query()->where('status', 'closed')->count(),
        ];

        $departments = Ticket::query()->distinct()->whereNotNull('department')->pluck('department')->all();

        $filterOptions = [
            'statuses'    => TicketStatus::options(),
            'departments' => array_map(fn ($d) => ['value' => $d, 'label' => $d], $departments),
        ];

        return $dataTable->render('admin.tickets.index', compact('statistics', 'filterOptions'));
    }

    public function show(Ticket $ticket): View
    {
        $this->authorize('view', $ticket);

        $ticket->load(['tenant', 'replies.adminUser', 'attachments']);

        return view('admin.tickets.show', [
            'ticket'  => $ticket,
            'replies' => $ticket->replies,
        ]);
    }

    public function reply(
        ReplyTicketRequest $request,
        Ticket $ticket,
        ReplyTicketAction $action
    ): RedirectResponse {
        $this->authorize('reply', $ticket);

        $action->execute(
            ticket: $ticket,
            adminUser: $request->user('admin'),
            message: $request->validated('message')
        );

        return back()->with('success', 'تم إرسال الرد بنجاح.');
    }

    public function updateStatus(
        UpdateTicketStatusRequest $request,
        Ticket $ticket,
        UpdateTicketStatusAction $action
    ): JsonResponse|RedirectResponse {
        $this->authorize('update', $ticket);

        $action->execute($ticket, $request->validated('status'));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة التذكرة بنجاح.',
            ]);
        }

        return back()->with('success', 'تم تحديث حالة التذكرة بنجاح.');
    }

    public function updateService(
        UpdateTicketServiceRequest $request,
        Ticket $ticket,
        UpdateTicketServiceAction $action,
    ): RedirectResponse {
        $this->authorize('update', $ticket);
        $action->execute($ticket, $request->boolean('service_enabled'));

        return back()->with('success', 'تم تحديث حالة خدمة الدعم للتذكرة.');
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف التذكرة بنجاح.']);
        }

        return redirect()->route('admin.tickets.index')->with('success', 'تم حذف التذكرة بنجاح.');
    }

    public function downloadAttachment(Ticket $ticket, int|string $attachment)
    {
        $this->authorize('view', $ticket);
        $attachmentModel = TicketAttachment::query()
            ->where('ticket_id', $ticket->getKey())
            ->findOrFail($attachment);
        $tenantId = (string) $ticket->tenant_id;
        abort_unless($tenantId !== '' && str_starts_with($attachmentModel->path, 'ticket-attachments/'.$tenantId.'/'), 404);
        abort_unless(Storage::disk('local')->exists($attachmentModel->path), 404);

        return Storage::disk('local')->download($attachmentModel->path, $attachmentModel->original_name);
    }
}
