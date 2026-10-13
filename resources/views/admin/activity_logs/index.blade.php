@extends('admin.layouts.layoutMaster')

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
            activityLogsIndex: "{{ route('admin.activity-logs.index') }}",
            activityLogsShow: "{{ route('admin.activity-logs.show', ':id') }}",
        };
        window.assets = {
            arabicDataTable: "{{ asset('static/i18n/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/admin/activity-logs/activity-logs.js'])
@endsection

@section('content')
    <div id="top_card" class="row g-4 mb-4">
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي النشاطات" :value="number_format($statistics['total'])" subtitle="منذ البداية"
            icon="ti-activity" color="primary" />
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="اليوم" :value="number_format($statistics['today'])" subtitle="نشاطات اليوم"
            icon="ti-calendar-week" color="success" />
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="هذا الأسبوع" :value="number_format($statistics['this_week'])" subtitle="نشاطات الأسبوع"
            icon="ti-calendar-week" color="info" />
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="هذا الشهر" :value="number_format($statistics['this_month'])" subtitle="نشاطات الشهر"
            icon="ti-calendar-month" color="warning" />
    </div>

    {{-- Filters Card --}}
    <x-data-table.filters table="#activity-logs-table">
        <x-filter.select id="filter-user" filterKey="user_id" label="المستخدم"
            placeholder="اختر المستخدم" :options="$filterOptions['users'] ?? []" />

        <div>
            <label class="form-label" for="filter-date-from">من تاريخ</label>
            <input type="date" id="filter-date-from" data-filter-key="date_from" data-datatable-filter="true" class="form-control">
        </div>

        <div>
            <label class="form-label" for="filter-date-to">إلى تاريخ</label>
            <input type="date" id="filter-date-to" data-filter-key="date_to" data-datatable-filter="true" class="form-control">
        </div>
    </x-data-table.filters>

    <div class="card" id="card-activity-logs">
        <div class="card-body">
            {{-- جدول البيانات --}}
            <table id="activity-logs-table" class="table table-striped table-bordered text-center">
                <thead>
                    <tr>
                        <th></th>
                        <th class="custom-checkbox">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th>القسم</th>
                        <th>الوصف</th>
                        <th>المستخدم</th>
                        {{-- <th>الموضوع</th> --}}
                        <th>التاريخ</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection
