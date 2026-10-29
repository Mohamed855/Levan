@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة سؤال شائع جديد')

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    @include('admin.faqs.partials._form', ['is_edit' => false, 'faq' => $faq ?? new \App\Models\Admin\Faq()])
@endsection
