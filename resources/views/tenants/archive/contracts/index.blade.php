@extends('tenants.layouts.layoutMaster')

@section('title', 'أرشيف العقود')

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
                            <span class="text-heading">العقود المؤرشفة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalArchivedContracts }}</h4>
                            </div>
                            <small class="mb-0">إجمالي العقود المؤرشفة</small>
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
                            <small class="mb-0">العقود النشطة المؤرشفة</small>
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
                            <small class="mb-0">العقود المنتهية المؤرشفة</small>
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
                            <small class="mb-0">العقود غير النشطة المؤرشفة</small>
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

    <div class="card">
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label for="filter-status" class="form-label">حالة العقد</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="الكل">
                        <option value=""></option>
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                        <option value="expired">منتهي</option>
                        <option value="suspended">معلق</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="filter-company" class="form-label">الشركة</label>
                    <select id="filter-company" class="form-control select2" data-placeholder="الكل">
                        <option value=""></option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="filter-commission-type" class="form-label">نوع العمولة</label>
                    <select id="filter-commission-type" class="form-control select2" data-placeholder="الكل">
                        <option value=""></option>
                        <option value="procedure">حسب الإجراء</option>
                        <option value="duration">حسب المدة</option>
                        <option value="delinquency">حسب مدة التعثر</option>
                    </select>
                </div>
            </div>

            <hr class="mt-10">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'archived-contracts-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
