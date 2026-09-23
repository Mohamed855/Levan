@extends('tenants.layouts.layoutMaster')

@section('title', 'المجموعات')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/moment/moment.js',
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
        arabicDataTable: "{{ asset('assets/json/ar.json') }}"
    };

    window.csrfToken = "{{ csrf_token() }}";
</script>

@vite(['resources/assets/js/tenants/user-mangment/team/index.js'])

{!! $dataTable->scripts() !!}
@endsection

@section('content')
<div id="top_card" class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">المجموعات</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $totalTeams }}</h4>
                        </div>
                        <small class="mb-0">إجمالي المجموعات</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-users-group ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">المجموعات النشطة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $activeTeams }}</h4>
                        </div>
                        <small class="mb-0">المجموعات النشطة</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-circle-check ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-4">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">المجموعات غير النشطة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $inactiveTeams }}</h4>
                        </div>
                        <small class="mb-0">المجموعات غير النشطة</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-danger">
                            <i class="ti ti-circle-x ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card" id="card-teams">
    <div class="card-body">
        <div class="filters row">
            <div class="col-md-6">
                <label for="filter-status">حالة المجموعة</label>
                <select id="filter-status" class="form-control select2" data-placeholder="اختر حالة المجموعة">
                    <option value=""></option>
                    <option value="active">نشط</option>
                    <option value="inactive">غير نشط</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="filter-branch">الفرع</label>
                <select id="filter-branch" class="form-control select2" data-placeholder="اختر الفرع">
                    <option value=""></option>
                    @foreach ($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <hr class="mt-10">

        <div class="table-responsive">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100']) !!}
        </div>
    </div>
</div>
@endsection