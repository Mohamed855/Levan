@extends('tenants.layouts.layoutMaster')

@section('title', 'المستخدمون المحذوفون')

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
            archiveIndex: "{{ tenant_route_name('archive.users.index') }}",
            massRestore: "{{ tenant_route_name('archive.users.mass-restore') }}",
            massForceDelete: "{{ tenant_route_name('archive.users.mass-force-delete') }}",
            restore: "{{ tenant_route_name('archive.users.restore', ['user' => ':id']) }}",
            forceDelete: "{{ tenant_route_name('archive.users.force-delete', ['user' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/archive/users/index.js'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستخدمون المحذوفون</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalArchivedUsers }}</h4>
                            </div>
                            <small class="mb-0">إجمالي المستخدمين المحذوفين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-archive ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستخدمين النشطين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archivedActiveUsers }}</h4>
                            </div>
                            <small class="mb-0">المستخدمون النشطون المحذوفون</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-user-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستخدمين غير النشطين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archivedInactiveUsers }}</h4>
                            </div>
                            <small class="mb-0">المستخدمون غير النشطين المحذوفون</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">محذوف مؤخراً</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $recentlyArchivedUsers }}</h4>
                            </div>
                            <small class="mb-0">خلال آخر 30 يوم</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-clock ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <x-data-table.filters table="#archived-users-table">
        <div class="col-md-6">
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#archived-users-table"
                name="status"
                label="حالة المستخدم"
                placeholder="اختر حالة المستخدم"
                :options="['active' => 'نشط', 'inactive' => 'غير نشط']"
            />
        </div>

        <div class="col-md-6">
            <x-filter.select
                id="filter-role"
                filterKey="role"
                table="#archived-users-table"
                name="role"
                label="الدور"
                placeholder="اختر الدور"
                :options="$roles->pluck('name', 'name')"
            />
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-users-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
