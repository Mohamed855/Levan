@extends('tenant.layouts.layoutMaster')

@section('title', 'الشركات المحذوفة')

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
            archiveIndex: "{{ tenant_route_name('archive.companies.index') }}",
            massRestore: "{{ tenant_route_name('archive.companies.mass-restore') }}",
            massForceDelete: "{{ tenant_route_name('archive.companies.mass-force-delete') }}",
            restore: "{{ tenant_route_name('archive.companies.restore', ['company' => ':id']) }}",
            forceDelete: "{{ tenant_route_name('archive.companies.force-delete', ['company' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenant/archive/companies/index.js'])
@endsection

@section('content')
    <x-data-table.archive-page>
        <x-slot:filters>
            <x-data-table.filters table="#archived-companies-table">
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#archived-companies-table"
                    name="status"
                    label="حالة الشركة"
                    placeholder="اختر حالة الشركة"
                    :enum="\App\Enums\Shared\ActivityStatus::class"
                />

                <x-filter.select
                    id="filter-authorized-count"
                    filterKey="authorized_count"
                    table="#archived-companies-table"
                    name="authorized_count"
                    label="عدد المفوضين"
                    placeholder="اختر عدد المفوضين"
                    :enum="\App\Enums\Tenants\Companies\AuthorizedCountRange::class"
                />
            </x-data-table.filters>
        </x-slot:filters>

        {!! $dataTable->table(
            ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-companies-table'],
            false,
        ) !!}
    </x-data-table.archive-page>
@endsection
