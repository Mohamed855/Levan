@php
    $is_profile = $is_profile ?? false;
    $is_edit = $is_edit ?? false;
@endphp

@include('shared.users.partials._form', [
    'stepperId' => 'user-wizard',
    'formId' => 'user-wizard-form',
    'action' => $is_profile
        ? route('admin.profile.update')
        : ($is_edit ? route('admin.users.update', $user) : route('admin.users.store')),
    'validationUrl' => route('admin.wizard-drafts.validate-step'),
    'draftUrl' => $is_edit ? null : route('admin.wizard-drafts.store'),
    'draftResourceKey' => 'admin.user-create',
    'backUrl' => $is_profile ? route('admin.profile.show') : route('admin.users.index'),
    'imageUrl' => !empty($user?->image) ? asset('storage/' . $user->image) : null,
    'is_profile' => $is_profile,
    'is_edit' => $is_edit,
    'user' => $user ?? null,
    'roles' => $roles ?? collect(),
])
