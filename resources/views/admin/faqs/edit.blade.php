@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل سؤال شائع')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    @include('admin.faqs.partials._form', ['is_edit' => true, 'faq' => $faq])
@endsection
