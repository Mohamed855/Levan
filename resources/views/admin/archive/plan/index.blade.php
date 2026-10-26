@extends('admin.layouts.layoutMaster')

@section('title', 'ارشيف الباقات')

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
    <script>
        window.routes = {
            archiveIndex: "{{ route('admin.archives.plans.index') }}",
            restore: "{{ route('admin.archives.plans.restore', ['id' => ':id']) }}",
            forceDelete: "{{ route('admin.archives.plans.force-delete', ['id' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/admin/archive/plans/index.js'])
@endsection

@section('content')
    <x-data-table.archive-page>
        <x-slot:filters>
            <x-data-table.filters table="#paln-archive-table">
                <x-filter.select id="filter-status" filterKey="is_active" label="حالة الباقة" placeholder="اختر حالة الباقة"
                    :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-domain" filterKey="domain_management_enabled" label="إدارة الدومينات"
                    placeholder="اختر حالة إدارة الدومين" :options="$filterOptions['domains'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        {!! $dataTable->table(
            ['class' => 'table table-striped table-bordered w-100', 'id' => 'paln-archive-table'],
            false,
        ) !!}
    </x-data-table.archive-page>
@endsection
