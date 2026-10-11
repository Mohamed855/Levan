@extends('tenants.layouts.layoutMaster')

@section('title', 'المحافظ المحذوفة')

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
    <script>
        window.routes = {
            archiveIndex: "{{ tenant_route_name('archive.portfolios.index') }}",
            massRestore: "{{ tenant_route_name('archive.portfolios.mass-restore') }}",
            massForceDelete: "{{ tenant_route_name('archive.portfolios.mass-force-delete') }}",
            restore: "{{ tenant_route_name('archive.portfolios.restore', ['portfolio' => ':id']) }}",
            forceDelete: "{{ tenant_route_name('archive.portfolios.force-delete', ['portfolio' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/archive/portfolios/index.js'])
@endsection

@section('content')
    <x-data-table.filters table="#archived-portfolios-table">
        <div>
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#archived-portfolios-table"
                name="status"
                label="حالة المحفظة"
                placeholder="اختر حالة المحفظة"
                :options="['active' => 'نشط', 'inactive' => 'غير نشط']"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-company"
                filterKey="company_id"
                table="#archived-portfolios-table"
                name="company_id"
                label="الشركة"
                placeholder="اختر الشركة"
                :options="$companies->pluck('name', 'id')"
            />
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-portfolios-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
