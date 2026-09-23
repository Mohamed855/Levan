<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreTrialRequest;
use App\Jobs\Admin\Mail\SendContactMailJob;
use App\Services\shared\Integration\WithOutTenant\EtmamTicketServiceWithOutTenant;
use Illuminate\Support\Facades\Log;

class TrialController extends Controller
{
    public function __construct(private EtmamTicketServiceWithOutTenant $ticketService)
    {
    }

    public function index()
    {
        return view('site.trial');
    }

    public function demoRequest(StoreTrialRequest $request)
    {
        try {
            $validated = $request->validated();
            $result    = $this->ticketService->demoRequest($validated);

            if (isset($result['errors']) && !empty($result['errors'])) {
                $firstError = collect($result['errors'])->flatten()->first();

                return back()->with('error', $firstError ?? 'حدث خطأ في البيانات المرسلة');
            }

            if ($result['success']) {
                SendContactMailJob::dispatch($request['customer_email'], $request['customer_name'], 'trial')->onQueue('email');
                return redirect()->route('site.index')->with('info', 'شكراً لك على اهتمامك. تم استقبال طلب التجربة بنجاح وسنتواصل معك قريباً.');
            }

            return redirect()->back()->withInput()->with('error', $result['message']);
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->withInput()->with('error', 'حدث خطأ غير متوقع');
        }
    }
}
