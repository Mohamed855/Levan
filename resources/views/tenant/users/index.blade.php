@extends('layouts.dashboard')

@section('title', 'مستخدمو المستأجر')
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
            const tableWrapper = $('#tenant-users-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#tenant-users-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#tenant-users-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="tenant-users-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي المستخدمين"
                :value="$statistics['total']"
                subtitle="كل أعضاء الفريق"
                icon="ti-users"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="المستخدمون النشطون"
                :value="$statistics['active']"
                subtitle="حسابات مفعلة"
                icon="ti-user-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="المستخدمون المعطلون"
                :value="$statistics['inactive']"
                subtitle="حسابات موقوفة"
                icon="ti-user-off"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="الأدوار الإدارية"
                :value="$statistics['roles']"
                subtitle="مجموعات الصلاحيات"
                icon="ti-shield-check"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="المستخدمون في الأرشيف"
                :value="$statistics['archived']"
                subtitle="سجلات محذوفة"
                icon="ti-archive"
                color="danger"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#tenant-users-table">
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#tenant-users-table"
                    label="الحالة"
                    placeholder="اختر الحالة"
                    :options="$filterOptions['status']"
                />
                <x-filter.select
                    id="filter-role"
                    filterKey="role_id"
                    table="#tenant-users-table"
                    label="الدور الإداري"
                    placeholder="اختر الدور"
                    :options="$filterOptions['roles']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'tenant-users-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
