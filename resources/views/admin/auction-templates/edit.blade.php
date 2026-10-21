@extends('layouts.dashboard')
@section('title', 'تعديل قالب مزاد')
@section('dashboard-scope', 'admin')
@section('head')@parent @vite(['resources/assets/scss/shared/template-designer.scss', 'resources/assets/js/admin/auction-templates/designer.js'])@endsection
@section('content')<form method="POST" action="{{ route('admin.auction-templates.update', $template) }}">@csrf @method('PUT') @include('admin.auction-templates.partials.form')</form>@endsection
