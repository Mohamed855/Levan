@extends(auth('admin')->user()?->must_change_password ? 'admin.layouts.blankLayout' : 'admin.layouts.layoutMaster')

@section('title', 'تغيير كلمة المرور')


@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/shared/initial-password-modal.js'])
@endsection

@section('content')
    @include('shared.users.partials._password-page', [
        'requiredPasswordChange' => (bool) auth('admin')->user()?->must_change_password,
        'action' => route('admin.users.password.update'),
        'validationUrl' => route('admin.wizard-drafts.validate-step'),
        'backUrl' => route('admin.profile.show'),
    ])
@endsection
