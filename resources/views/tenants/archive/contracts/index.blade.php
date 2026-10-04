@extends('tenants.layouts.layoutMaster')

@section('title', 'العقود المحذوفة')

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
            archiveIndex: "{{ tenant_route_name('archive.contracts.index') }}",
            massRestore: "{{ tenant_route_name('archive.contracts.mass-restore') }}",
            massForceDelete: "{{ tenant_route_name('archive.contracts.mass-force-delete') }}",
            getAllIds: "{{ tenant_route_name('archive.contracts.get-all-ids') }}",
            restore: "{{ tenant_route_name('archive.contracts.restore', ['contract' => ':id']) }}",
            forceDelete: "{{ tenant_route_name('archive.contracts.force-delete', ['contract' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
        window.totalArchivedCount = {{ $totalArchivedContracts }};
    </script>
    @vite(['resources/assets/js/tenants/archive/contracts/index.js'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">العقود المحذوفة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalArchivedContracts }}</h4>
                            </div>
                            <small class="mb-0">إجمالي العقود المحذوفة</small>
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
                            <span class="text-heading">العقود النشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archivedActiveContracts }}</h4>
                            </div>
                            <small class="mb-0">العقود النشطة المحذوفة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-check ti-26px"></i>
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
                            <span class="text-heading">العقود المنتهية</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archivedExpiredContracts }}</h4>
                            </div>
                            <small class="mb-0">العقود المنتهية المحذوفة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-x ti-26px"></i>
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
                            <span class="text-heading">العقود غير النشطة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $archivedInactiveContracts }}</h4>
                            </div>
                            <small class="mb-0">العقود غير النشطة المحذوفة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-secondary">
                                <i class="ti ti-circle-off ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <x-data-table.filters table="#archived-contracts-table">
        <div class="col-md-4">
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#archived-contracts-table"
                name="status"
                label="حالة العقد"
                placeholder="اختر حالة العقد"
                :options="[
                    'active' => 'نشط',
                    'inactive' => 'غير نشط',
                    'expired' => 'منتهي',
                    'suspended' => 'معلق'
                ]"
            />
        </div>

        <div class="col-md-4">
            <x-filter.select
                id="filter-company"
                filterKey="company_id"
                table="#archived-contracts-table"
                name="company_id"
                label="الشركة"
                placeholder="اختر الشركة"
                :options="$companies->pluck('name', 'id')"
            />
        </div>

        <div class="col-md-4">
            <x-filter.select
                id="filter-commission-type"
                filterKey="commission_type"
                table="#archived-contracts-table"
                name="commission_type"
                label="نوع العمولة"
                placeholder="اختر نوع العمولة"
                :options="[
                    'procedure' => 'حسب الإجراء',
                    'duration' => 'حسب المدة',
                    'delinquency' => 'حسب مدة التعثر'
                ]"
            />
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-contracts-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
