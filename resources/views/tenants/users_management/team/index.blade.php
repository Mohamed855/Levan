@extends('tenants.layouts.layoutMaster')

@section('title', 'المجموعات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            teamsIndex: "{{ tenant_route_name('teams.index') }}",
            teamsCreate: "{{ tenant_route_name('teams.create') }}",
            toggleStatus: "{{ tenant_route_name('teams.toggle-status', ['team' => '___ID___']) }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };

        window.assets = {
            arabicDataTable: "{{ asset('static/i18n/ar.json') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/assets/js/tenants/user-mangment/team/index.js'])

    {!! $dataTable->scripts() !!}
@endsection

@section('content')
    <div id="top_card" class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="المجموعات" :value="$totalTeams" subtitle="إجمالي المجموعات"
            icon="ti-users-group" color="primary" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="المجموعات النشطة" :value="$activeTeams" subtitle="المجموعات النشطة"
            icon="ti-circle-check" color="success" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="المجموعات غير النشطة" :value="$inactiveTeams"
            subtitle="المجموعات غير النشطة" icon="ti-circle-x" color="danger" />
    </div>


    {{-- Filters Card --}}
    <x-data-table.filters table="#teams-table" reset-id="btn-reset-filters">
        <div>
            <label class="form-label" for="filter-status">حالة المجموعة</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="اختر حالة المجموعة">
                <option value=""></option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
            </select>
        </div>
    </x-data-table.filters>

    <div class="card" id="card-teams">
        <div class="card-body">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100']) !!}
            </div>
        </div>
    </div>
@endsection
