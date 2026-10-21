@extends('layouts.dashboard')
@section('title','تعديل الإسناد')
@section('dashboard-scope','tenant')
@section('content')<form method="POST" action="{{ route('tenant.assignments.update',$assignment) }}">@csrf @method('PUT') @include('tenant.assignments.partials.form')</form>@endsection
