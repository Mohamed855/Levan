@extends('layouts.dashboard')
@section('title', 'إضافة دور إداري')
@section('dashboard-scope', 'admin')
@section('content')<form method="POST" action="{{ route('admin.roles.store') }}">@csrf @include('admin.roles.partials.form')</form>@endsection
