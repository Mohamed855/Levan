@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل قالب مزاد')

@section('page-style')
    @vite(['resources/assets/scss/shared/template-designer.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/auction-templates/designer.js'])
@endsection

@section('content')
    <h4 class="mb-4">تعديل قالب المزاد: <span class="text-primary">{{ $template->name }}</span></h4>
    <form method="POST" action="{{ route('admin.auction-templates.update', $template) }}" id="auction-template-edit-form" data-form-validate="true">
        @csrf
        @method('PUT')
        @include('admin.auction-templates.partials.form')
    </form>
@endsection
