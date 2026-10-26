@extends('admin.layouts.layoutMaster')

@section('title', 'البنوك')

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

@section('page-script')
    @vite(['resources/assets/js/admin/shared-status-toggle.js'])
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };

        window.handleMassDelete = function () {
            window.executeMassDelete('Bank', window.routes.massDelete, 'يرجى تحديد بنك واحد على الأقل');
        };
    </script>
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="banks-table">
        <x-slot:filters>
            <x-data-table.filters table="#banks-table">
                <x-filter.select id="filter-status" filterKey="status" label="الحالة"
                    placeholder="اختر الحالة" :options="$filterOptions['statuses'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'banks-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection

