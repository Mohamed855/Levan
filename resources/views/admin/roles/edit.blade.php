@extends('layouts.dashboard')
@section('title', 'تعديل دور إداري')
@section('dashboard-scope', 'admin')
@section('content')<form method="POST" action="{{ route('admin.roles.update', $role) }}">@csrf @method('PUT') @include('admin.roles.partials.form')</form>@endsection
