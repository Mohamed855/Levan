@extends('layouts.dashboard')

@section('title', 'تذاكر الدعم الفني')
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
            const tableWrapper = $('#tickets-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#tickets-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#tickets-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد تذكرة واحدة على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Ticket');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="tickets-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي التذاكر"
                :value="$statistics['total']"
                subtitle="كل طلبات الدعم"
                icon="ti-ticket"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="تذاكر جديدة"
                :value="$statistics['open']"
                subtitle="بانتظار المعالجة"
                icon="ti-alert-circle"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="قيد المعالجة"
                :value="$statistics['in_progress']"
                subtitle="جاري العمل عليها"
                icon="ti-clock"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="تم الحل"
                :value="$statistics['resolved']"
                subtitle="تمت المعالجة بنجاح"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="تذاكر مغلقة"
                :value="$statistics['closed']"
                subtitle="مغلقة نهائياً"
                icon="ti-lock"
                color="secondary"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#tickets-table">
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#tickets-table"
                    label="حالة التذكرة"
                    placeholder="اختر الحالة"
                    :options="$filterOptions['statuses']"
                />
                <x-filter.select
                    id="filter-department"
                    filterKey="department"
                    table="#tickets-table"
                    label="القسم"
                    placeholder="اختر القسم"
                    :options="$filterOptions['departments']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'tickets-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
