@extends('tenants.layouts.layoutMaster')

@section('title', 'تقارير المستخدمين')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/apex-charts/apexcharts.js'])
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
            usersReportIndex: "{{ tenant_route_name('reports.users.index') }}",
            usersReportExportPDF: "{{ tenant_route_name('reports.users.export.pdf') }}"
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";

        // بيانات المخططات
        window.chartData = {
            branchData: @json($collections_by_branch),
            usersPerformance: @json($users_performance_chart),
        };
    </script>
    @vite(['resources/assets/js/tenants/users-report.js'])
@endsection

@section('content')
    {{-- الإحصائيات الرئيسية --}}
    <div class="row g-4 mb-4">
        <div class="col-sm-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي المستخدمين</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">{{ number_format($total_users) }}</h5>
                            </div>
                            <small class="mb-0">مستخدم مسجل</small>
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

        <div class="col-sm-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">أفضل محصل</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">{{ $top_performer_this_month['name'] }}</h5>
                            </div>
                            <small class="mb-0">{{ number_format($top_performer_this_month['amount'], 0) }} ريال</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-trophy ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">تحصيلات اليوم</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">{{ number_format($users_with_collections_today) }}</h5>
                            </div>
                            <small class="mb-0">مستخدم حصّل اليوم</small>
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

        <div class="col-sm-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي التحصيلات</span>
                            <div class="d-flex align-items-center my-1">
                                <h5 class="mb-0 me-2">{{ number_format($total_collections_this_month, 0) }}</h5>
                            </div>
                            <small class="mb-0">ريال هذا الشهر</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-currency-riyal ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- المخططات البيانية --}}
    <div class="row g-4 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">التحصيلات حسب الفرع</h5>
                </div>
                <div class="card-body">
                    <div id="branchChart"></div>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title mb-0">أعلى 10 محصلين</h5>
                </div>
                <div class="card-body">
                    <div id="usersPerformanceChart"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- جدول التقرير --}}
    <div class="card" id="card-users-report">
        <div class="card-body">
            {{-- فلاتر البحث --}}
            <div class="filters row">
                <div class="col-md-3">
                    <label for="branch_id">الفرع</label>
                    <select id="branch_id" name="branch_id" class="form-control select2" data-placeholder="جميع الفروع">
                        <option value=""></option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="status">حالة المستخدم</label>
                    <select id="status" name="status" class="form-control select2" data-placeholder="جميع الحالات">
                        <option value=""></option>
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="date_from">من تاريخ</label>
                    <input type="text" id="date_from" name="date_from" class="form-control flatpickr-date"
                        placeholder="اختر التاريخ">
                </div>

                <div class="col-md-2">
                    <label for="date_to">إلى تاريخ</label>
                    <input type="text" id="date_to" name="date_to" class="form-control flatpickr-date"
                        placeholder="اختر التاريخ">
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" id="reset-filters" class="btn btn-primary w-100">
                        <i class="ti ti-refresh me-1"></i>إعادة تعيين
                    </button>
                </div>
            </div>

            <hr class="mt-10">

            {{-- جدول البيانات --}}
            <table id="users-report-table" class="table table-striped table-bordered text-center">
                <thead>
                    <tr>
                        <th></th>
                        <th>معلومات المستخدم</th>
                        <th>بيانات الاتصال</th>
                        <th>الفرع</th>
                        <th>الأداء الإجمالي</th>
                        <th>الأداء الشهري</th>
                        <th>آخر نشاط</th>
                        <th>الحالة</th>
                        <th>تاريخ الانضمام</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- سيتم تعبئة البيانات بواسطة DataTables --}}
                </tbody>
            </table>
        </div>
    </div>
@endsection
