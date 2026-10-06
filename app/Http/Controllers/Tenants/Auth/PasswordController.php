<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\UpdateTenantPasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;

class PasswordController extends Controller
{
    /**
     * Update the user's password.
     */
    public function update(UpdateTenantPasswordRequest $request): RedirectResponse
    {
        $request->user('tenant')->update([
            'password' => Hash::make($request->validated('password')),
        ]);

        return back()->with('status', 'password-updated');
    }
}
