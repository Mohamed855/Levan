@extends('layouts.dashboard')

@section('title', 'تعديل قالب مزاد')
@section('dashboard-scope', 'admin')

@section('page-style')
    @vite(['resources/assets/scss/shared/template-designer.scss'])
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.auction-templates.update', $template) }}">
        @csrf
        @method('PUT')
        @include('admin.auction-templates.partials.form')
    </form>
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/auction-templates/designer.js'])
@endsection
