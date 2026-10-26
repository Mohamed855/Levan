@extends('admin.layouts.layoutMaster')

@section('title', 'المعاملات المالية')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="manage-payment-table">
        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'manage-payment-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
