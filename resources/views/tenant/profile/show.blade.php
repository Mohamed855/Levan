@extends('tenant.layouts.layoutMaster')

@section('title', 'الصفحة الشخصية — ' . $user->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4">
        <div class="d-flex gap-2">
            <a href="{{ tenant_route_name('users.password.change') }}" class="btn btn-secondary">
                <i class="ti ti-key me-1"></i> تغيير كلمة المرور
            </a>
            <a href="{{ tenant_route_name('profile.edit') }}" class="btn btn-primary">
                <i class="ti ti-edit me-1"></i> تعديل البيانات
            </a>
        </div>
    </div>

    @include('tenant.user-management.users.partials._details')
@endsection
