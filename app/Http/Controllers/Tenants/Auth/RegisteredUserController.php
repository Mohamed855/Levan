<?php

namespace App\Http\Controllers\Tenants\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\RegisterUserRequest;
use App\Models\Tenants\UserMangment\User\User;
use App\Providers\RouteServiceProvider;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('tenants.auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(RegisterUserRequest $request): RedirectResponse
    {
        $user = User::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'employee_number' => 'REG-'.strtoupper(Str::random(8)),
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'global_uid' => (string) Str::uuid(),
            'status' => 'active',
        ]);

        event(new Registered($user));

        Auth::login($user);

        return redirect(RouteServiceProvider::home());
    }
}
