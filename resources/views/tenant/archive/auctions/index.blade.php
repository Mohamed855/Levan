@extends('tenant.layouts.layoutMaster')

@section('title', 'أرشيف المزادات المحذوفة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')
    <x-data-table.archive-page>
        <x-slot:filters>
            <x-data-table.filters table="#auctions-archive-table">
                <x-filter.date id="filter-deleted-from" filterKey="deleted_from" label="من تاريخ الحذف" />
                <x-filter.date id="filter-deleted-to" filterKey="deleted_to" label="إلى تاريخ الحذف" />
            </x-data-table.filters>
        </x-slot:filters>

        {!! $dataTable->table(
            ['class' => 'table table-striped table-bordered w-100', 'id' => 'auctions-archive-table'],
            false,
        ) !!}
    </x-data-table.archive-page>
@endsection
