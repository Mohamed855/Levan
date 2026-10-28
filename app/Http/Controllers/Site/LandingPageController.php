<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Actions\Admin\PublicRequests\CreateContactRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Site\StoreContactRequest;
use App\Models\Admin\Faq;
use App\Models\Admin\Plans\Plan;
use Illuminate\Support\Facades\Schema;

class LandingPageController extends Controller
{
    public function __construct(private readonly CreateContactRequestAction $createContactRequest) {}

    public function index()
    {
        $plans = Plan::publiclyAvailable()->orderBy('sort_order', 'asc')->get();
        $faqs = Schema::hasTable('faqs') ? Faq::query()->active()->latest()->get() : collect();

        return view('web.index', compact('plans', 'faqs'));
    }

    public function contactRequest(StoreContactRequest $request)
    {
        try {
            $validated = $request->validated();
            $contact = $this->createContactRequest->execute($validated);

            \App\Services\Shared\Mail\SystemMailService::sendContactRequestReceived(
                $validated['customer_email'],
                $validated['customer_name'],
                null,
                $validated['message']
            );
            \App\Services\Shared\Mail\SystemMailService::sendContactRequestAdminAlert(
                $validated['customer_name'],
                $validated['customer_email'],
                $validated['message'],
                $validated['customer_phone'] ?? null,
                null,
                route('admin.contact-requests.show', $contact->id)
            );

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم استقبال استفسارك بنجاح وسيتم الرد عليك في أقرب وقت.',
                ]);
            }

            return redirect()->route('site.index')->with('success', 'تم استقبال استفسارك بنجاح وسيتم الرد عليك في أقرب وقت.');
        } catch (\Throwable $exception) {
            report($exception);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ غير متوقع',
                ], 500);
            }

            return redirect()->back()->withInput()->with('error', 'حدث خطأ غير متوقع');
        }
    }

    public function privacyPolicy()
    {
        return view('web.privacy-policy');
    }
}
