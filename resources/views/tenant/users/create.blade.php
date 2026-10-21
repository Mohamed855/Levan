@extends('layouts.dashboard')
@section('title', 'إضافة مستخدم')
@section('dashboard-scope', 'tenant')
@section('content')
    <form method="POST" action="{{ route('tenant.users.store') }}">@csrf @include('tenant.users.partials.form')</form>
@endsection
