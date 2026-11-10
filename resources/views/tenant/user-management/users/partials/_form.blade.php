@php
    $is_profile = $is_profile ?? false;
    $is_edit = $is_edit ?? false;
@endphp

@include('shared.users.partials._form', [
    'stepperId' => 'wizard-validation',
    'formId' => 'wizard-validation-form',
    'action' => $is_profile
        ? tenant_route_name('profile.update')
        : ($is_edit ? tenant_route_name('users.update', $user) : tenant_route_name('users.store')),
    'validationUrl' => tenant_route_name('wizard-drafts.validate-step'),
    'draftUrl' => $is_edit ? null : tenant_route_name('wizard-drafts.store'),
    'draftResourceKey' => 'tenant.user-create',
    'backUrl' => $is_profile ? tenant_route_name('profile.show') : tenant_route_name('users.index'),
    'imageUrl' => isset($user) && $user->exists && !empty($user->image) ? tasset($user->image) : null,
    'is_profile' => $is_profile,
    'is_edit' => $is_edit,
    'user' => $user ?? null,
    'roles' => $roles ?? collect(),
])
