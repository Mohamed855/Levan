@extends('tenant.layouts.layoutMaster')

@section('title', 'إضافة دور جديد')

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('tenant.user-management.roles.partials._form', ['is_edit' => false, 'role' => null])
@endsection
