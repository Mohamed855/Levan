@extends('layouts.dashboard')
@section('title', 'تعديل دور')
@section('dashboard-scope', 'tenant')
@section('content')<form method="POST" action="{{ route('tenant.roles.update', $role) }}">@csrf @method('PUT') @include('tenant.roles.partials.form')</form>@endsection
