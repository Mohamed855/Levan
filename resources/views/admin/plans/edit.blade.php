@extends('layouts.dashboard')
@section('title', 'تعديل الباقة')
@section('dashboard-scope', 'admin')
@section('content')<form method="POST" action="{{ route('admin.plans.update', $plan) }}">@csrf @method('PUT') @include('admin.plans.partials.form')</form>@endsection
