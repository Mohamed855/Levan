@extends('tenants.layouts.layoutMaster')

@section('title', 'المستخدمين')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    <script>
        // متغيرات JavaScript العامة
        window.routes = {
            usersIndex: "{{ tenant_route_name('users.index') }}",
            usersCreate: "{{ tenant_route_name('users.create') }}",
            toggleStatus: "{{ tenant_route_name('users.toggle-status', ['user' => '___ID___']) }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };

        window.assets = {
            arabicDataTable: "{{ asset('static/i18n/ar.json') }}"
        };

        window.licenseRoutes = {
            assign: "{{ tenant_route_name('licenses.assign') }}",
            release: "{{ tenant_route_name('licenses.release') }}",
            subscriptions: "{{ tenant_route_name('subscriptions.index') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/assets/js/tenants/user-mangment/user/index.js', 'resources/assets/js/tenants/user-mangment/user/license.js'])

    {!! $dataTable->scripts() !!}
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div id="top_card" class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المستخدمين" :value="$totalUsers" subtitle="إجمالي المستخدمين"
            icon="ti-users" color="primary" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المستخدمين النشطين" :value="$activeUsers"
            subtitle="المستخدمين النشطين" icon="ti-user-check" color="success" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المستخدمين غير النشطين" :value="$inactiveUsers"
            subtitle="المستخدمين غير النشطين" icon="ti-user-off" color="danger" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="التراخيص" :value="($subscription_formatted['used_users'] ?? 0) . ' / ' . ($subscription_formatted['total_users'] ?? 0)" :subtitle="'متبقي ' .
            (($subscription_formatted['total_users'] ?? 0) - ($subscription_formatted['used_users'] ?? 0)) .
            ' ترخيص'"
            icon="ti-license" color="primary" valueClass="badge bg-label-primary rounded-pill">
            <div class="text-end">
                {{ $subscription_formatted['total_users'] > 0 ? round(($subscription_formatted['used_users'] / $subscription_formatted['total_users']) * 100) : 0 }}%
            </div>
            <div class="progress" style="height: 8px;">
                <div class="progress-bar bg-primary" role="progressbar"
                    style="width: {{ $subscription_formatted['total_users'] > 0 ? ($subscription_formatted['used_users'] / $subscription_formatted['total_users']) * 100 : 0 }}%"
                    aria-valuenow="{{ $subscription_formatted['used_users'] ?? 0 }}" aria-valuemin="0"
                    aria-valuemax="{{ $subscription_formatted['total_users'] ?? 0 }}">
                </div>
            </div>
        </x-admin.dashboard-card>
    </div>


    {{-- Filters Card --}}
    <x-data-table.filters table="#users-table" reset-id="btn-reset-filters">
        <div class="col-md-6">
            <label class="form-label" for="filter-status">حالة المستخدم</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="اختر حالة المستخدم">
                <option value=""></option>
                <option value="active">نشط</option>
                <option value="inactive">غير نشط</option>
            </select>
        </div>

        <div class="col-md-6">
            <label class="form-label" for="filter-role">دور المستخدم</label>
            <select id="filter-role" class="form-control select2" data-datatable-filter="true" data-filter-key="role" data-placeholder="اختر الدور">
                <option value=""></option>
                @foreach ($roles as $role)
                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
            </select>
        </div>
    </x-data-table.filters>

    <div class="card" id="card-users">
        <div class="card-body">
            <div class="table-responsive">
                {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100']) !!}
            </div>
        </div>
    </div>
@endsection
