@extends('tenants.layouts.layoutMaster')

@section('title', 'الشركات')

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
            massDelete: "{{ tenant_route_name('mass-delete') }}",
            toggleStatus: "{{ tenant_route_name('companies.toggle-status', ['company' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/business-center/companies/index.js'])
@endsection


@section('content')
    <x-data-table.page table-id="companies-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="الشركات" :value="$totalCompanies" subtitle="إجمالي الشركات"
                icon="ti-building" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="الشركات النشطة" :value="$activeCompanies" subtitle="الشركات النشطة"
                icon="ti-building-store" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="الشركات غير النشطة" :value="$inactiveCompanies" subtitle="الشركات غير النشطة"
                icon="ti-ban" color="danger" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="شركات أُضيفت مؤخرًا" :value="$recentCompanies" subtitle="خلال آخر 30 يوم"
                icon="ti-calendar-stats" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#companies-table">
                <x-filter.select id="filter-status" filterKey="status" label="حالة الشركة" placeholder="اختر حالة الشركة"
                    :enum="\App\Enums\Shared\ActivityStatus::class" />
                <x-filter.select id="filter-authorized-count" filterKey="authorized_count" label="عدد المفوضين" placeholder="اختر عدد المفوضين"
                    :enum="\App\Enums\Tenants\Companies\AuthorizedCountRange::class" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'companies-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
