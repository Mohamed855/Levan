<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Actions\Auth\PersistAuthenticationStateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\UpdateTenantPasswordRequest;
use Illuminate\Http\RedirectResponse;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(UpdateTenantPasswordRequest $request, PersistAuthenticationStateAction $authenticationState): RedirectResponse
    {
        $authenticationState->updatePassword($request->user('tenant'), $request->validated('password'));

        return back()->with('status', 'password-updated');
    }
}
