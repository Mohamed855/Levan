@extends('tenants.layouts.layoutMaster')

@section('title', 'سجل النشاطات')

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
            activityLogsIndex: "{{ tenant_route_name('activity-logs.index') }}",
            activityLogsShow: "{{ tenant_route_name('activity-logs.show', ':id') }}",
            activityLogsArchives: "{{ tenant_route_name('activity-logs.archives') }}",
            activityLogsGetAllIds: "{{ tenant_route_name('activity-logs.get-all-ids') }}",
            activityLogsExport: "{{ tenant_route_name('activity-logs.export') }}",
            activityLogsExportDownload: "{{ tenant_route_name('activity-logs.export-download', ['file' => 'FILE_PLACEHOLDER']) }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/activity-logs/selection.js', 'resources/assets/js/tenants/activity-logs/activity-logs.js'])
@endsection

@section('content')
    <div id="top_card" class="row g-3 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي النشاطات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($statistics['total']) }}</h4>
                            </div>
                            <small class="mb-0">منذ البداية</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-activity ti-26px"></i>
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
                            <span class="text-heading">اليوم</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($statistics['today']) }}</h4>
                            </div>
                            <small class="mb-0">نشاطات اليوم</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-calendar-week ti-26px"></i>
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
                            <span class="text-heading">هذا الأسبوع</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($statistics['this_week']) }}</h4>
                            </div>
                            <small class="mb-0">نشاطات الأسبوع</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-calendar-week ti-26px"></i>
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
                            <span class="text-heading">هذا الشهر</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ number_format($statistics['this_month']) }}</h4>
                            </div>
                            <small class="mb-0">نشاطات الشهر</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-calendar-month ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول سجل النشاطات --}}
    <div class="card" id="card-activity-logs">
        <div class="card-body">
            {{-- فلاتر البحث --}}
            <div class="filters row g-3">
                <div class="col-md-3">
                    <label for="filter-user">المستخدم</label>
                    <select id="filter-user" class="form-control select2" data-placeholder="اختر المستخدم">
                        <option value=""></option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filter-log-name">نوع النشاط</label>
                    <select id="filter-log-name" class="form-control select2" data-placeholder="اختر النوع">
                        <option value=""></option>
                        <option value="companies">الشركات</option>
                        <option value="contracts">العقود</option>
                        <option value="customers">العملاء</option>
                        <option value="customer_follow">المتابعات</option>
                        <option value="customer_payment_plan">خطط السداد</option>
                        <option value="customer_phone">أرقام الاتصال</option>
                        <option value="customer_settlement">طلبات التسوية</option>

                        <option value="users">المستخدمين</option>
                        <option value="portfolios">المحافظ</option>
                        <option value="collections">التحصيلات</option>
                        <option value="auth">تسجيل الدخول</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="filter-date-from">من تاريخ</label>
                    <input type="date" id="filter-date-from" class="form-control">
                </div>

                <div class="col-md-3">
                    <label for="filter-date-to">إلى تاريخ</label>
                    <input type="date" id="filter-date-to" class="form-control">
                </div>
            </div>

            <hr class="mt-10">

            {{-- جدول البيانات --}}
            <table id="activity-logs-table" class="table table-striped table-bordered text-center">
                <thead>
                    <tr>
                        <th></th>
                        <th class="custom-checkbox">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th>نوع النشاط</th>
                        <th>الوصف</th>
                        <th>المستخدم</th>
                        <th>الموضوع</th>
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- سيتم تعبئة البيانات بواسطة DataTables --}}
                </tbody>
            </table>
        </div>
    </div>
@endsection
