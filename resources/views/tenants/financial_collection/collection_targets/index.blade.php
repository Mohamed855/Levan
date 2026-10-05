@extends('tenants.layouts.layoutMaster')

@section('title', 'أهداف التحصيل')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            collectionTargetsIndex: "{{ tenant_route_name('financial-collections.collection-targets.index') }}",
            collectionTargetsCreate: "{{ tenant_route_name('financial-collections.collection-targets.create') }}",
            toggleStatus: "{{ tenant_route_name('financial-collections.collection-targets.toggle-status', ':id') }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}",
            getUserTargetDetails: "{{ tenant_route_name('financial-collections.collection-targets.user-details', ':userId') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/financial_collection/targets/index.js'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي المستخدمين</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">{{ $statistics['total_users'] }}</h5>
                            </div>
                            <small class="mb-0">عدد المستخدمين الذين لديهم أهداف</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-users ti-26px"></i>
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
                            <span class="text-heading">الأهداف الشهرية</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">{{ $statistics['monthly_targets'] }}</h5>
                            </div>
                            <small class="mb-0">الأهداف ذات الفترة الشهرية</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-calendar ti-26px"></i>
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
                            <span class="text-heading">الأهداف السنوية</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">{{ $statistics['yearly_targets'] }}</h5>
                            </div>
                            <small class="mb-0">الأهداف ذات الفترة السنوية</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-calendar-event ti-26px"></i>
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
                            <span class="text-heading">الأهداف المخصصة</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">{{ $statistics['custom_targets'] }}</h5>
                            </div>
                            <small class="mb-0">الأهداف ذات الفترة المخصصة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-calendar-time ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-data-table.filters table="#collection-targets-table" reset-id="btn-reset-filters">
        <div class="col-md-6">
            <label for="filter-status" class="form-label">حالة الهدف</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="اختر حالة الهدف">
                <option value=""></option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
            </select>
        </div>

        <div class="col-md-6">
            <label for="filter-user" class="form-label">المستخدم</label>
            <select id="filter-user" class="form-control select2" data-datatable-filter="true" data-filter-key="user_id" data-placeholder="اختر المستخدم">
                <option value=""></option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'collection-targets-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
