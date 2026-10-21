@extends('layouts.dashboard')
@section('title', 'تعديل مستخدم إدارة')
@section('dashboard-scope', 'admin')
@section('content')
    <form method="POST" action="{{ route('admin.users.update', $user) }}">@csrf @method('PUT')
        @include('admin.users.partials.form')
    </form>
@endsection
