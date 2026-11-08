@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل الدور')

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('tenant.user-management.roles.partials._form', ['is_edit' => true, 'role' => $role])
@endsection
