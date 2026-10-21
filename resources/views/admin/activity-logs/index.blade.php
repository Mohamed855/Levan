@extends('layouts.dashboard')

@section('title', 'سجل النشاطات')
@section('dashboard-scope', 'admin')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };

        $(function () {
            const tableWrapper = $('#activity-logs-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#activity-logs-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#activity-logs-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function () {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('يرجى تحديد سجل نشاط واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'ActivityLog');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="activity-logs-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي العمليات"
                :value="$statistics['total']"
                subtitle="كل أحداث النظام الموثقة"
                icon="ti-activity"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="عمليات اليوم"
                :value="$statistics['today']"
                subtitle="خلال الـ 24 ساعة الحالية"
                icon="ti-calendar-event"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="عمليات الأسبوع"
                :value="$statistics['this_week']"
                subtitle="خلال الأسبوع الحالي"
                icon="ti-calendar-stats"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="عمليات الشهر"
                :value="$statistics['this_month']"
                subtitle="خلال الشهر الحالي"
                icon="ti-calendar"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="عمليات النظام"
                :value="$statistics['by_system']"
                subtitle="أحداث تلقائية ومجدولة"
                icon="ti-robot"
                color="secondary"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#activity-logs-table">
                <x-filter.select
                    id="filter-user"
                    filterKey="user_id"
                    table="#activity-logs-table"
                    label="المستخدم الفاعل"
                    placeholder="اختر المستخدم"
                    :options="$filterOptions['users']"
                />
                <x-filter.select
                    id="filter-log-name"
                    filterKey="log_name"
                    table="#activity-logs-table"
                    label="نوع السجل"
                    placeholder="اختر النوع"
                    :options="$filterOptions['log_names']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'activity-logs-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
