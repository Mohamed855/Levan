@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة دور جديد')

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @include('admin.roles.partials._form', ['is_edit' => false, 'role' => null])
@endsection
