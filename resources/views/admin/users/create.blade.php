@extends('layouts.dashboard')
@section('title', 'إضافة مستخدم إدارة')
@section('dashboard-scope', 'admin')
@section('content')
    <form method="POST" action="{{ route('admin.users.store') }}">
        @csrf
        @include('admin.users.partials.form')
    </form>
@endsection
