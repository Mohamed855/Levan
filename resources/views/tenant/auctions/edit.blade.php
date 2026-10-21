@extends('layouts.dashboard')
@section('title', 'تعديل مزاد')
@section('dashboard-scope', 'tenant')
@section('head')@parent @vite('resources/assets/js/tenant/auctions/form.js')@endsection
@section('content')<form method="POST" action="{{ route('tenant.auctions.update', $auction) }}" enctype="multipart/form-data">@csrf @method('PUT') @include('tenant.auctions.partials.form')</form>@endsection