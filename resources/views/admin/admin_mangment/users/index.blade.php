@extends('admin.layouts.layoutMaster')

@section('title', 'المستحدمين ')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')

    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection


@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/admin_mangment/users/index.js', 'resources/assets/js/admin/admin_mangment/users/filter.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('content')

    <div id="top_card" class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="إجمالي المستخدمين" :value="$totalUsers"
            subtitle="إجمالي المستخدمين" icon="ti-users" color="primary" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="المستخدمين النشطين" :value="$activeUsers"
            subtitle="المستخدمين النشطين" icon="ti-user-check" color="success" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-4" title="المستخدمين غير النشطين" :value="$inactiveUsers"
            subtitle="المستخدمين غير النشطين" icon="ti-user-off" color="warning" />
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <x-filter.select id="filter-status" label="حالة المستخدمين"
                    placeholder="اختر حالة المستخدم" :options="$filterOptions['statuses']" />
                <x-filter.select id="filter-role" label="دور المستخدم" placeholder="اختر الدور"
                    :options="$filterOptions['roles']" />
            </div>
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="btn-reset-filters" class="btn btn-secondary">
                    <i class="ti ti-refresh me-1"></i>إعادة تعيين
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'users-table'], false) !!}
        </div>
    </div>
@endsection
