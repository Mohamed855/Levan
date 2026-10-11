@extends('tenants.layouts.layoutMaster')

@section('title', 'الشركات المحذوفة')

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
            archiveIndex: "{{ tenant_route_name('archive.companies.index') }}",
            massRestore: "{{ tenant_route_name('archive.companies.mass-restore') }}",
            massForceDelete: "{{ tenant_route_name('archive.companies.mass-force-delete') }}",
            restore: "{{ tenant_route_name('archive.companies.restore', ['company' => ':id']) }}",
            forceDelete: "{{ tenant_route_name('archive.companies.force-delete', ['company' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/archive/companies/index.js'])
@endsection

@section('content')
    <x-data-table.filters table="#archived-companies-table">
        <div>
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#archived-companies-table"
                name="status"
                label="حالة الشركة"
                placeholder="اختر حالة الشركة"
                :options="['active' => 'نشط', 'inactive' => 'غير نشط']"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-authorized-count"
                filterKey="authorized_count"
                table="#archived-companies-table"
                name="authorized_count"
                label="عدد المفوضين"
                placeholder="اختر عدد المفوضين"
                :options="[
                    '0' => 'بدون مفوضين',
                    '1' => 'مفوض واحد',
                    '2-5' => 'من 2 إلى 5 مفوضين',
                    '6+' => 'أكثر من 5 مفوضين',
                ]"
            />
        </div>

        <div>
            <label for="filter-search" class="form-label">البحث</label>
            <input type="text" id="filter-search" class="form-control"
                placeholder="البحث في اسم الشركة أو المفوضين"
                data-filter-key="search_query"
                data-datatable-filter="true"
                data-datatable-target="#archived-companies-table">
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-companies-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
