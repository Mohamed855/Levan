@extends('admin.layouts.layoutMaster')

@section('title', 'تغيير كلمة المرور')

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('shared.users.partials._password-form', [
        'action' => route('admin.users.password.update'),
        'validationUrl' => route('admin.wizard-drafts.validate-step'),
        'backUrl' => route('admin.profile.show'),
    ])
@endsection
