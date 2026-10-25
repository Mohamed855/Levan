<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Admin\PublicRequests\CreateContactRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactRequest;
use App\Jobs\Admin\Mail\SendContactMailJob;
use App\Models\Admin\Plans\Plan;
use App\Models\Admin\Faq;

class LandingPageController extends Controller
{
    public function __construct(private readonly CreateContactRequestAction $createContactRequest) {}

    public function index()
    {
        $plans = Plan::publiclyAvailable()->orderBy('sort_order', 'asc')->get();
        $ourClients = collect();
        $portfolios = collect();
        $faqs = \Illuminate\Support\Facades\Schema::hasTable('faqs')
            ? Faq::query()->active()->latest()->get()
            : collect();

        return view('web.index', compact('plans', 'ourClients', 'portfolios', 'faqs'));
    }

    public function contactRequest(StoreContactRequest $request)
    {
        try {
            $validated = $request->validated();
            $this->createContactRequest->execute($validated);

            SendContactMailJob::dispatch($validated['customer_email'], $validated['customer_name'], 'contact')->onQueue('email');

            return redirect()->route('site.index')->with('success', 'تم استقبال استفسارك بنجاح وسيتم الرد عليك في أقرب وقت.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withInput()->with('error', 'حدث خطأ غير متوقع');
        }
    }

    public function privacyPolicy()
    {
        return view('web.privacy-policy');
    }
}
