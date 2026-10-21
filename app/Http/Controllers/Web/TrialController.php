<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Actions\Web\CreateTrialRequestAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreTrialRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TrialController extends Controller
{
    public function index(): View { return view('web.trial'); }

    public function store(StoreTrialRequest $request, CreateTrialRequestAction $action): RedirectResponse
    {
        $action->execute($request->validated());

        return to_route('site.index')->with('success', 'تم استلام طلب التجربة المجانية بنجاح.');
    }
}
