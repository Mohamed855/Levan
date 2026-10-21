<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Web\CreateContactRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreContactRequest;
use App\Models\Admin\Faq;
use App\Models\Admin\Plans\Plan;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LandingPageController extends Controller
{
    public function index(): View
    {
        return view('web.index', [
            'plans' => Plan::publiclyAvailable()->orderBy('sort_order')->get(),
            'ourClients' => collect(),
            'portfolios' => collect(),
            'faqs' => Faq::active()->latest()->get(),
        ]);
    }

    public function storeContact(StoreContactRequest $request, CreateContactRequestAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return to_route('site.index')->with('success', 'تم استلام رسالتك بنجاح.');
    }

    public function privacyPolicy(): View
    {
        return view('web.policy');
    }
}
