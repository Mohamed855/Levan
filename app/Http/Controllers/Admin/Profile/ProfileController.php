<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Profile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\AdminAuthenticatedRequest;
use App\Http\Requests\Admin\Profile\UpdateProfileRequest;
use App\Services\Admin\AdminMangment\Users\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(private UserService $userService) {}

    public function show(AdminAuthenticatedRequest $request): View
    {
        return view('admin.profile.show', $this->userService->getProfileData($request->user('admin')));
    }

    public function edit(AdminAuthenticatedRequest $request): View
    {
        return view('admin.profile.edit', ['user' => $request->user('admin')]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $this->userService->update($request->user('admin'), $request->validated());

        return redirect()->route('admin.profile.show')->with('success', 'تم تحديث البيانات الشخصية بنجاح.');
    }
}
