@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة بنك جديد')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    @include('admin.settings.banks.partials._form', ['is_edit' => false, 'bank' => null])
@endsection
