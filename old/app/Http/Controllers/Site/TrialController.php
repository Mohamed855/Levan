<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Admin\PublicRequests\CreateTrialRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreTrialRequest;
use App\Jobs\Admin\Mail\SendContactMailJob;

class TrialController extends Controller
{
    public function __construct(private readonly CreateTrialRequestAction $createTrialRequest) {}

    public function index()
    {
        return view('web.trial');
    }

    public function demoRequest(StoreTrialRequest $request)
    {
        try {
            $validated = $request->validated();
            $this->createTrialRequest->execute($validated);

            SendContactMailJob::dispatch($validated['customer_email'], $validated['customer_name'], 'trial')->onQueue('email');

            return redirect()->route('site.index')->with('info', 'تم استقبال طلب التجربة بنجاح وسنتواصل معك قريبًا.');
        } catch (\Throwable $exception) {
            report($exception);

            return redirect()->back()->withInput()->with('error', 'حدث خطأ غير متوقع');
        }
    }
}
