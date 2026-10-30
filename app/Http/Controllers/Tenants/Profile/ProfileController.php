<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\Profile\UpdateProfileRequest;
use App\Services\Tenants\UserManagement\User\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function show(TenantAuthenticatedRequest $request): View
    {
        return view('tenant.profile.show', $this->userService->getProfileData($request->user('tenant')));
    }

    public function edit(TenantAuthenticatedRequest $request): View
    {
        $user = $request->user('tenant')->load('roles');

        return view('tenant.profile.edit', ['user' => $user, 'roles' => $user->roles]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->userService->updateUser($request->user('tenant'), $request->validated());

        return redirect()->to(tenant_route_name('profile.show'))->with('success', 'تم تحديث البيانات الشخصية بنجاح.');
    }
}
