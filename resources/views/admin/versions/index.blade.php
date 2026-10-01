@extends('admin.layouts.layoutMaster')

@section('title', 'الاصدارات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/versions/filter.js'])
@endsection

@section('content')

    <x-ui.validation-errors />

    <x-data-table.page table-id="versions-table">
        <x-slot:filters>
            <x-data-table.filters>
                <x-filter.select id="filter-stage" label="المرحلة"
                    placeholder="اختر المرحلة" :options="$filterOptions['stages'] ?? []" />
                <x-filter.select id="filter-status" label="الحالة"
                    placeholder="اختر الحالة" :options="$filterOptions['statuses'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'versions-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>

@endsection
