<?php

namespace App\Http\Controllers\Tenants\Tickets;

use App\DataTables\Tenants\Tickets\TicketsDataTable;
use App\Enums\Tenants\Tickets\TicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Tickets\TicketReplyRequest;
use App\Http\Requests\Tenants\Tickets\TicketRequest;
use App\Services\shared\Integration\EtmamTicketService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TicketController extends Controller
{
    private $page = "tenants.tickets";
    private $route = "tickets";

    public function __construct(private EtmamTicketService $ticketService)
    {
        $this->middleware(function ($request, $next) {
            if (!$this->ticketService->isConfigured()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'خدمة التذاكر غير مفعلة'
                    ], 503);
                }

                return redirect()->to(tenant_route_name('dashboard'))->with('error', 'خدمة التذاكر غير مفعلة. يرجى التواصل مع الإدارة.');
            }

            return $next($request);
        });
    }


    public function index(TicketsDataTable $dataTable)
    {
        try {
            return $dataTable->render($this->page . '.index');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }


    public function create()
    {
        $sectionsResult = $this->ticketService->getSections();
        $sections = $sectionsResult['success']
            ? ($sectionsResult['data']['data'] ?? [])
            : [];

        return view($this->page . '.create', compact('sections'));
    }


    public function store(TicketRequest $request)
    {
        try {
            $validated = $request->validated();
            $result    = $this->ticketService->create($validated, $request->file('attachments', []));

            if ($result['success']) {
                return redirect()->to(tenant_route_name($this->route . '.index'))->with('success', 'تم إنشاء التذكرة بنجاح');
            }

            return redirect()->back()->withInput()->with('error', $result['message']);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء التذكرة');
        }
    }


    public function show(string $ticketNumber)
    {
        try {
            $result = $this->ticketService->get($ticketNumber);

            if (!$result['success']) {
                return redirect()->to(tenant_route_name($this->route . '.index'))->with('error', $result['message']);
            }

            $ticketData = $result['data']['data'] ?? null;

            if (!$ticketData) {
                return redirect()->to(tenant_route_name($this->route . '.index'))->with('error', 'لم يتم العثور على التذكرة');
            }

            $ticket = (object) $ticketData;

            $status = TicketStatus::toBadge($ticket->status);

            // dd($ticket);
            // جلب الردود
            $repliesResult = $this->ticketService->getReplies($ticketNumber);
            $repliesData = $repliesResult['success']
                ? ($repliesResult['data']['data'] ?? [])
                : [];

            // تحويل الردود إلى objects
            $replies = collect($repliesData)->map(fn($reply) => (object) $reply);


            // جلب الأقسام للعرض
            $sectionsResult = $this->ticketService->getSections();
            $sections = $sectionsResult['success']
                ? collect($sectionsResult['data']['data'] ?? [])->keyBy('id')
                : collect([]);

            $section = $sections->get($ticket->ticketing_sections_id);


            $surveyQuestions = [
                'agent_rating'      => 'ما تقييمك لأداء موظف  الدعم الفني ؟',
                'system_rating'     => 'ما تقييمك للنظام ؟',
                'solution_rating'   => 'ما تقييمك لجودة الحل المقدم ؟',
            ];

            return view($this->page . '.show', compact('ticket', 'replies', 'section', 'status', 'surveyQuestions'));
        } catch (\Exception $e) {
            Log::error('Error loading ticket', [
                'ticket_number' => $ticketNumber,
                'error' => $e->getMessage()
            ]);

            return redirect()
                ->to(tenant_route_name($this->route . '.index'))
                ->with('error', 'حدث خطأ أثناء تحميل التذكرة');
        }
    }


    public function reply(TicketReplyRequest $request, $ticket)
    {
        try {
            $validated = $request->validated();
            $result    = $this->ticketService->addReply($ticket, $validated['message'], $request->file('attachments', []));

            if ($result['success']) {
                return redirect()->back()->with('success', 'تم الرد على التذكرة بنجاح');
            }

            return redirect()->back()->withInput()->with('error', $result['message']);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->with('error', 'حدث خطأ أثناء إنشاء التذكرة');
        }
    }


    public function rateReply(Request $request, string $ticketNumber, int $replyId)
    {
        $request->validate([
            'stars' => 'required|integer|min:1|max:5',
        ]);

        $stars = (int) $request->input('stars');

        $result = $this->ticketService->rateReply($ticketNumber, $replyId, $stars);

        if ($result['success'] ?? false) {
            return back()->with('success', $result['message'] ?? 'تم تقييم الرد بنجاح');
        }

        return back()->with('error', $result['message'] ?? 'تعذر تقييم الرد حالياً');
    }


    public function submitSurvey(Request $request, string $ticketNumber)
    {
        $data = $request->validate([
            'solved'           => ['required', 'boolean'],
            'agent_rating'     => ['nullable', 'integer', 'between:1,5'],
            'system_rating'    => ['nullable', 'integer', 'between:1,5'],
            'solution_rating'  => ['nullable', 'integer', 'between:1,5'],
            'comment'          => ['nullable', 'string', 'max:1000'],
        ]);


        $result = $this->ticketService->answerSurvey($ticketNumber, $data);

        if ($result['success'] ?? false) {
            return back()->with('success', $result['message'] ?? 'تم تسجيل إجابتك بنجاح');
        }

        return back()->with('error', $result['message'] ?? 'تعذر تسجيل الإجابة حالياً');
    }
}
