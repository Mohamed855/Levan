@extends('tenant.layouts.layoutMaster')

@section('title', 'الملف الشخصي — ' . $user->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4">
        <div class="d-flex gap-2">
            <a href="{{ tenant_route_name('users.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
            @can('users.edit')
                <a href="{{ tenant_route_name('users.edit', $user->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل المستخدم
                </a>
            @endcan
        </div>
    </div>

    @include('tenant.user-management.users.partials._details')
@endsection
