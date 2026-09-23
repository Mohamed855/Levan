@extends('tenants.layouts.layoutMaster')

@section('title', 'المستخدمين')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
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
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">المستخدمين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalUsers }}</h4>
                            </div>
                            <small class="mb-0">إجمالي المستخدمين</small>
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
                            <span class="text-heading">المستخدمين النشطين</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $activeUsers }}</h4>
                            </div>
                            <small class="mb-0">المستخدمين النشطين</small>
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
                                <h4 class="mb-0 me-2">{{ $inactiveUsers }}</h4>
                            </div>
                            <small class="mb-0">المستخدمين غير النشطين</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-user-off ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-6 col-xl-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">التراخيص</span>
                            <small class="text-muted mb-0 small">
                                متبقي
                                {{ ($subscription_formatted['total_users'] ?? 0) - ($subscription_formatted['used_users'] ?? 0) }}
                                ترخيص
                            </small>
                            <div class="d-flex align-items-center my-1">
                                <span class="badge bg-label-primary rounded-pill">
                                    {{ $subscription_formatted['used_users'] ?? 0 }} /
                                    {{ $subscription_formatted['total_users'] ?? 0 }}
                                </span>
                            </div>
                        </div>
                        <div class="avatar flex-shrink-0">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-license ti-28px"></i>
                            </span>
                        </div>
                    </div>

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
                </div>
            </div>
        </div>
    </div>

    {{-- جدول المستخدمين --}}
    <div class="card" id="card-users">
        <div class="card-body">
            {{-- فلاتر البحث --}}
            <div class="filters row">
                <div class="col-md-4">
                    <label for="filter-status">حالة المستخدم</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر حالة المستخدم">
                        <option value=""></option>
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="filter-role">دور المستخدم</label>
                    <select id="filter-role" class="form-control select2" data-placeholder="اختر الدور">
                        <option value=""></option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4"> {{-- جديد --}}
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
