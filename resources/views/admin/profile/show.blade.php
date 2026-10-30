@extends('admin.layouts.layoutMaster')

@section('title', 'الصفحة الشخصية — ' . $user->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-user me-2 text-primary"></i>الصفحة الشخصية: {{ $user->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-{{ $user->status === \App\Enums\Admin\Users\AdminUserStatus::ACTIVE ? 'success' : 'danger' }}">
                    {{ $user->status === \App\Enums\Admin\Users\AdminUserStatus::ACTIVE ? 'نشط' : 'غير نشط' }}
                </span>
                @if ($user->isSuperAdmin())
                    <span class="badge bg-primary">مدير النظام</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.users.password.change') }}" class="btn btn-secondary">
                <i class="ti ti-key me-1"></i> تغيير كلمة المرور
            </a>
            <a href="{{ route('admin.profile.edit') }}" class="btn btn-primary">
                <i class="ti ti-edit me-1"></i> تعديل البيانات
            </a>
        </div>
    </div>

    @include('admin.users.partials._details')
@endsection
