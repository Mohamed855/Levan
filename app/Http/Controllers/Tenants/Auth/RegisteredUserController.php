<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Actions\Tenants\Auth\RegisterTenantUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\RegisterUserRequest;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('tenant.auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterUserRequest $request, RegisterTenantUserAction $registerUser): RedirectResponse
    {
        $user = $registerUser->execute($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::home());
    }
}
