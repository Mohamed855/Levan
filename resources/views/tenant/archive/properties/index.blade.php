@extends('tenant.layouts.layoutMaster')

@section('title', 'أرشيف العقارات المحذوفة')

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
            <x-data-table.filters table="#properties-archive-table">
                <x-filter.date id="filter-deleted-date" filterKey="deleted_at" target="#properties-archive-table" label="تاريخ الحذف" />
            </x-data-table.filters>
        </x-slot:filters>

        {!! $dataTable->table(
            ['class' => 'table table-striped table-bordered w-100', 'id' => 'properties-archive-table'],
            false,
        ) !!}
    </x-data-table.archive-page>
@endsection

