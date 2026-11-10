@extends('tenant.layouts.layoutMaster')

@section('title', 'سجل النشاطات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            activityLogsIndex: "{{ tenant_route_name('activity-logs.index') }}",
            activityLogsShow: "{{ tenant_route_name('activity-logs.show', ':id') }}",
            activityLogsArchives: "{{ tenant_route_name('activity-logs.archives') }}",
            activityLogsGetAllIds: "{{ tenant_route_name('activity-logs.get-all-ids') }}",
            activityLogsExport: "{{ tenant_route_name('activity-logs.export') }}",
            activityLogsExportDownload: "{{ tenant_route_name('activity-logs.export-download', ['file' => 'FILE_PLACEHOLDER']) }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenant/activity-logs/selection.js', 'resources/assets/js/tenant/activity-logs/activity-logs.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('content')
    <x-data-table.page table-id="activity-logs-table" card-id="card-activity-logs">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي النشاطات" :value="number_format($statistics['total'])" subtitle="منذ البداية"
                icon="ti-activity" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="اليوم" :value="number_format($statistics['today'])" subtitle="نشاطات اليوم"
                icon="ti-calendar-week" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="هذا الأسبوع" :value="number_format($statistics['this_week'])" subtitle="نشاطات الأسبوع"
                icon="ti-calendar-week" color="info" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="هذا الشهر" :value="number_format($statistics['this_month'])" subtitle="نشاطات الشهر"
                icon="ti-calendar-month" color="warning" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#activity-logs-table" reset-id="btn-reset-filters">
                <x-filter.select id="filter-user" filterKey="user_id" label="المستخدم" placeholder="اختر المستخدم"
                    :options="$filterOptions['users'] ?? []" />
                <x-filter.select id="filter-log-name" filterKey="log_name" label="نوع النشاط" placeholder="اختر النوع"
                    :options="$filterOptions['log_names'] ?? []" />
                <x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered text-center w-100', 'id' => 'activity-logs-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
