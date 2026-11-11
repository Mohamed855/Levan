@include('shared.users._details', [
    'avatarUrl' => $user->image ? tasset($user->image) : asset('static/brand/logos/logo.svg'),
    'existingImageValue' => $user->image ? tasset($user->image) : null,
    'canEditAvatar' => auth()->id() === $user->id || auth()->user()?->can('users.edit'),
    'uploadAction' => tenant_route_name('users.update-image', $user->id),
    'validationUrl' => tenant_route_name('wizard-drafts.validate-step'),
    'isAdminRole' => $user->isAdmin(),
])
