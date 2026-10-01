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
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/admin/activity-logs/activity-logs.js'])
@endsection

@section('content')
    <div id="top_card" class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي النشاطات" :value="number_format($statistics['total'])" subtitle="منذ البداية"
            icon="ti-activity" color="primary" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="اليوم" :value="number_format($statistics['today'])" subtitle="نشاطات اليوم"
            icon="ti-calendar-week" color="success" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="هذا الأسبوع" :value="number_format($statistics['this_week'])" subtitle="نشاطات الأسبوع"
            icon="ti-calendar-week" color="info" />
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="هذا الشهر" :value="number_format($statistics['this_month'])" subtitle="نشاطات الشهر"
            icon="ti-calendar-month" color="warning" />
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-4">
                    <label class="form-label" for="filter-user">المستخدم</label>
                    <select id="filter-user" class="form-control select2" data-placeholder="اختر المستخدم">
                        <option value=""></option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="filter-date-from">من تاريخ</label>
                    <input type="date" id="filter-date-from" class="form-control">
                </div>

                <div class="col-md-4">
                    <label class="form-label" for="filter-date-to">إلى تاريخ</label>
                    <input type="date" id="filter-date-to" class="form-control">
                </div>
            </div>
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="btn-reset-filters" class="btn btn-secondary">
                    <i class="ti ti-refresh me-1"></i>إعادة تعيين
                </button>
            </div>
        </div>
    </div>

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
