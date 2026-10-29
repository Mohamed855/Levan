@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة امتداد دومين')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    @include('admin.settings.domains.partials._form', ['is_edit' => false, 'domain' => null])
@endsection
