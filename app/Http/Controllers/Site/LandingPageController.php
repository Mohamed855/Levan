<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactRequest;
use App\Jobs\Admin\Mail\SendContactMailJob;
use App\Models\Admin\ContentManagement\Client;
use App\Models\Admin\ContentManagement\Portfolio;
use App\Models\Admin\Plans\Plan;
use App\Services\shared\Integration\WithOutTenant\EtmamTicketServiceWithOutTenant;
use Illuminate\Support\Facades\Log;

class LandingPageController extends Controller
{
    public function __construct(private EtmamTicketServiceWithOutTenant $ticketService) {}

    public function index()
    {
        $plans = Plan::where('is_active', true)->orderBy('sort_order', 'asc')->get();
         $ourClients = Client::where('status' , 1)->latest()->get();
        $portfolios = Portfolio::where('status' , 1)->latest()->get();
        return view('site.index', compact('plans' , 'ourClients' , 'portfolios'));
    }

    public function contactRequest(StoreContactRequest $request)
    {
        try {
            $validated = $request->validated();
            $result    = $this->ticketService->contactRequest($validated);

            if (isset($result['errors']) && !empty($result['errors'])) {
                $firstError = collect($result['errors'])->flatten()->first();

                return back()->with('error', $firstError ?? 'حدث خطأ في البيانات المرسلة');
            }

            if ($result['success']) {
                SendContactMailJob::dispatch($request['customer_email'], $request['customer_name'], 'contact')->onQueue('email');
                return redirect()->route('site.index')->with('success', 'شكراً لتواصلك معنا. تم استقبال استفسارك بنجاح وسيتم الرد عليك في أقرب وقت.');
            }
        } catch (\Exception $e) {
            Log::error($e);
            return redirect()->back()->withInput()->with('error', 'حدث خطأ غير متوقع');
        }
    }

    public function privacyPolicy()
    {
        return view('site.privacy-policy',);
    }
}
