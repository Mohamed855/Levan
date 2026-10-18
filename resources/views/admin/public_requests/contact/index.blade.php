@extends('admin.layouts.layoutMaster')
@section('title', 'طلبات التواصل')
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss'])
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
    {!! $dataTable->scripts() !!}
@endsection
@section('content')
    <div class="card"><div class="card-body"><div class="table-responsive">{!! $dataTable->table(['class' => 'table table-hover']) !!}</div></div></div>
@endsection
