@extends('layouts.dashboard')

@section('title', 'مركز الأعمال - الشركات')
@section('dashboard-scope', 'tenant')

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
        $(function () {
            const tableWrapper = $('#companies-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#companies-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#companies-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="companies-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي الشركات"
                :value="$statistics['total']"
                subtitle="كل الشركات المسجلة"
                icon="ti-building"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="شركات نشطة"
                :value="$statistics['active']"
                subtitle="جاهزة للربط والعمليات"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="شركات معطلة"
                :value="$statistics['inactive']"
                subtitle="موقوفة مؤقتاً"
                icon="ti-circle-x"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="أضيفت هذا الشهر"
                :value="$statistics['added_this_month']"
                subtitle="خلال الشهر الحالي"
                icon="ti-calendar-event"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="شركات في الأرشيف"
                :value="$statistics['archived']"
                subtitle="سجلات محذوفة"
                icon="ti-archive"
                color="danger"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#companies-table">
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#companies-table"
                    label="الحالة"
                    placeholder="اختر الحالة"
                    :options="$filterOptions['status']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'companies-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
