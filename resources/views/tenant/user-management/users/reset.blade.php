@extends('tenant.layouts.layoutMaster')

@section('title', 'تغيير كلمة المرور')

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('shared.users.partials._password-form', [
        'action' => tenant_route_name('users.password.update'),
        'validationUrl' => tenant_route_name('wizard-drafts.validate-step'),
        'backUrl' => tenant_route_name('profile.show'),
    ])
@endsection
