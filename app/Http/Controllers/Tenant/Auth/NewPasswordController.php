<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Auth;

use App\Actions\Tenant\Auth\ResetPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Auth\ResetPasswordRequest;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    public function create(Request $request): View
    {
        return view('tenant.auth.reset-password', [
            'token' => $request->route('token'),
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function store(ResetPasswordRequest $request, ResetPasswordAction $resetPassword): RedirectResponse
    {
        $status = Password::broker('users')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request, $resetPassword): void {
                $resetPassword->execute($user, $request->string('password')->toString());

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PASSWORD_RESET
            ? redirect()->route('tenant.login')->with('status', __($status))
            : back()->withInput($request->only('email'))
                ->withErrors(['email' => __($status)]);
    }
}
