<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\RedirectResponse;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Send a new email verification notification.
     */
    public function store(TenantAuthenticatedRequest $request): RedirectResponse
    {
        $user = $request->user('tenant');

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(RouteServiceProvider::home());
        }

        $user->sendEmailVerificationNotification();

        return back()->with('status', 'verification-link-sent');
    }
}
