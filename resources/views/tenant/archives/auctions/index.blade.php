@extends('layouts.dashboard')
@section('title', 'أرشيف المزادات')
@section('dashboard-scope', 'tenant')
@section('vendor-style') @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss','resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss']) @endsection
@section('vendor-script') @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js']) {!! $dataTable->scripts() !!} @endsection
@section('content')
<div class="card"><div class="card-header d-flex justify-content-between"><h5 class="mb-0">أرشيف المزادات</h5><a class="btn btn-sm btn-label-secondary" href="{{ route('tenant.auctions.index') }}">العودة للمزادات</a></div><div class="card-body">{!! $dataTable->table(['class' => 'table table-hover w-100'], false) !!}</div></div>
@endsection
