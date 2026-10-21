@extends('layouts.dashboard')
@section('title', 'إضافة دور')
@section('dashboard-scope', 'tenant')
@section('content')<form method="POST" action="{{ route('tenant.roles.store') }}">@csrf @include('tenant.roles.partials.form')</form>@endsection
