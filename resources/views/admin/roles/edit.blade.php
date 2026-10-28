@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل الدور')

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('admin.roles.partials._form', ['is_edit' => true, 'role' => $role])
@endsection
