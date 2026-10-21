@extends('layouts.dashboard')
@section('title', 'تعديل مستخدم')
@section('dashboard-scope', 'tenant')
@section('content')
    <form method="POST" action="{{ route('tenant.users.update', $user) }}">@csrf @method('PUT') @include('tenant.users.partials.form')</form>
@endsection
