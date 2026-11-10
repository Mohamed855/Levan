@extends(auth('tenant')->user()?->must_change_password ? 'tenant.layouts.blankLayout' : 'tenant.layouts.layoutMaster')

@section('title', 'تغيير كلمة المرور')


@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/shared/initial-password-modal.js'])
@endsection

@section('content')
    @include('shared.users.partials._password-page', [
        'requiredPasswordChange' => (bool) auth('tenant')->user()?->must_change_password,
        'action' => tenant_route_name('users.password.update'),
        'validationUrl' => tenant_route_name('wizard-drafts.validate-step'),
        'backUrl' => tenant_route_name('profile.show'),
    ])
@endsection
