@include('shared.users._details', [
    'avatarUrl' =>
        $user->image && Storage::disk('public')->exists($user->image)
            ? asset('storage/' . $user->image)
            : asset('static/brand/logos/logo.svg'),
    'existingImageValue' =>
        $user->image && Storage::disk('public')->exists($user->image)
            ? asset('storage/' . $user->image)
            : null,
    'canEditAvatar' => auth('admin')->id() === $user->id || auth('admin')->user()?->can('users.edit'),
    'uploadAction' => route('admin.users.update-image', $user->id),
    'validationUrl' => route('admin.wizard-drafts.validate-step'),
    'isAdminRole' => $user->isSuperAdmin(),
])
