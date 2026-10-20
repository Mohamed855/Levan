<?php

namespace App\Http\Controllers\Admin\Tickets;

use App\DataTables\Admin\Tickets\AdminTicketsDataTable;
use App\Enums\Tenants\Tickets\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Tickets\ReplyTicketRequest;
use App\Http\Requests\Admin\Tickets\ToggleTicketsServiceRequest;
use App\Http\Requests\Admin\Tickets\UpdateTicketStatusRequest;
use App\Models\Admin\Tickets\TicketSection;
use App\Services\Admin\Tickets\AdminTicketService;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Database\Models\Tenant;

class AdminTicketController extends Controller
{
    private $page = 'admin.tickets';

    private $route = 'admin.tickets';

    public function __construct(private AdminTicketService $ticketService)
    {
        $this->middleware('can:tickets.view')->only(['index', 'show']);
        $this->middleware('can:tickets.reply')->only(['reply', 'updateStatus']);
        $this->middleware('can:system.settings')->only(['toggleService']);
    }

    /**
     * Display listing of tickets with KPI stats
     */
    public function index(AdminTicketsDataTable $dataTable)
    {
        try {
            $stats = $this->ticketService->getStats();
            $sections = TicketSection::where('is_active', true)->get();
            $tenants = Tenant::select('id', 'company_name')->get();
            $ticketsEnabled = $this->ticketService->ticketsEnabled();

            $filterOptions = [
                'statuses' => [
                    ['value' => 'open', 'label' => 'جديدة'],
                    ['value' => 'in_progress', 'label' => 'قيد المعالجة'],
                    ['value' => 'resolved', 'label' => 'تم الحل'],
                    ['value' => 'resolved_pending_survey', 'label' => 'بانتظار التقييم'],
                    ['value' => 'reopened', 'label' => 'معادة'],
                    ['value' => 'closed', 'label' => 'مغلقة'],
                ],
                'sections' => $sections->map(fn ($section): array => [
                    'value' => $section->id,
                    'label' => $section->name,
                ]),
                'tenants' => $tenants->map(fn ($tenant): array => [
                    'value' => $tenant->id,
                    'label' => $tenant->company_name ?? (string) $tenant->id,
                ]),
            ];

            return $dataTable->render($this->page.'.index', compact('stats', 'sections', 'tenants', 'ticketsEnabled', 'filterOptions'));
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب قائمة التذاكر: '.$e->getMessage());
        }
    }

    /**
     * Show ticket details and replies
     */
    public function show(string $ticketNumber)
    {
        try {
            $ticket = $this->ticketService->getTicket($ticketNumber);

            if (! $ticket) {
                return redirect()->route($this->route.'.index')->with('error', 'لم يتم العثور على التذكرة المطلوبة');
            }

            $this->authorize('view', $ticket);

            $status = TicketStatus::toBadge($ticket->status);
            $replies = $ticket->replies;

            return view($this->page.'.show', compact('ticket', 'status', 'replies'));
        } catch (\Exception $e) {
            Log::error('Error loading admin ticket', [
                'ticket_number' => $ticketNumber,
                'error' => $e->getMessage(),
            ]);

            return redirect()->route($this->route.'.index')->with('error', 'حدث خطأ أثناء تحميل التذكرة');
        }
    }

    /**
     * Reply to ticket from Admin
     */
    public function reply(ReplyTicketRequest $request, string $ticketNumber)
    {
        try {
            $ticket = $this->ticketService->getTicket($ticketNumber);
            if ($ticket) {
                $this->authorize('reply', $ticket);
            }

            $result = $this->ticketService->addReply(
                $ticketNumber,
                $request->input('message'),
                $request->file('attachments', [])
            );

            if ($result['success']) {
                return redirect()->back()->with('success', 'تم إرسال الرد بنجاح');
            }

            return redirect()->back()->withInput()->with('error', $result['message']);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء إرسال الرد');
        }
    }

    /**
     * Update ticket status
     */
    public function updateStatus(UpdateTicketStatusRequest $request, string $ticketNumber)
    {
        try {
            $ticket = $this->ticketService->getTicket($ticketNumber);
            if ($ticket) {
                $this->authorize('updateStatus', $ticket);
            }

            $result = $this->ticketService->updateStatus($ticketNumber, $request->input('status'));

            if ($result['success']) {
                return redirect()->back()->with('success', 'تم تغيير حالة التذكرة بنجاح');
            }

            return redirect()->back()->with('error', $result['message']);
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء تحديث حالة التذكرة');
        }
    }

    /**
     * Toggle tickets service on/off
     */
    public function toggleService(ToggleTicketsServiceRequest $request)
    {
        try {
            $this->ticketService->updateTicketsEnabled($request->boolean('tickets_enabled'));

            $statusText = $request->boolean('tickets_enabled') ? 'تم تفعيل' : 'تم إيقاف';

            return redirect()->back()->with('success', $statusText.' خدمة التذاكر بنجاح');
        } catch (\Exception $e) {
            report($e);

            return redirect()->back()->with('error', 'حدث خطأ أثناء تحديث حالة الخدمة');
        }
    }
}
