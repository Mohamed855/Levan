@extends('layouts.dashboard')
@section('title', 'إنشاء باقة')
@section('dashboard-scope', 'admin')
@section('content')<form method="POST" action="{{ route('admin.plans.store') }}">@csrf @include('admin.plans.partials.form')</form>@endsection
