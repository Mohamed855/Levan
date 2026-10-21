<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Tickets;

use App\Actions\Admin\Tickets\ReplyTicketAction;
use App\Actions\Admin\Tickets\UpdateTicketStatusAction;
use App\DataTables\Admin\Tickets\TicketDataTable;
use App\Enums\Admin\Tickets\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tickets\ReplyTicketRequest;
use App\Http\Requests\Admin\Tickets\UpdateTicketStatusRequest;
use App\Models\Admin\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TicketController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:tickets.view')->only(['index', 'show']);
        $this->middleware('can:tickets.reply')->only('reply');
        $this->middleware('can:tickets.update')->only(['updateStatus', 'destroy']);
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
        $ticket->load(['tenant', 'replies.adminUser']);

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
        $action->execute($ticket, $request->validated('status'));

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث حالة التذكرة بنجاح.',
            ]);
        }

        return back()->with('success', 'تم تحديث حالة التذكرة بنجاح.');
    }

    public function destroy(Request $request, Ticket $ticket): JsonResponse|RedirectResponse
    {
        $ticket->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف التذكرة بنجاح.']);
        }

        return redirect()->route('admin.tickets.index')->with('success', 'تم حذف التذكرة بنجاح.');
    }
}
