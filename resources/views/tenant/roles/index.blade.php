@extends('layouts.dashboard')

@section('title', 'الأدوار والصلاحيات')
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
            const tableWrapper = $('#tenant-roles-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#tenant-roles-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#tenant-roles-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="tenant-roles-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي الأدوار"
                :value="$statistics['total']"
                subtitle="كل أدوار المستأجر"
                icon="ti-shield"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="أدوار مستخدمة"
                :value="$statistics['roles_with_users']"
                subtitle="مسندة لمستخدمين"
                icon="ti-shield-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="أدوار شاغرة"
                :value="$statistics['empty_roles']"
                subtitle="دون مستخدمين حالياً"
                icon="ti-shield-x"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="أدوار النظام"
                :value="$statistics['system_roles']"
                subtitle="أساسية غير قابلة للحذف"
                icon="ti-lock"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="أدوار مخصصة"
                :value="$statistics['custom_roles']"
                subtitle="مضافة من قبل الإدارة"
                icon="ti-adjustments"
                color="secondary"
            />
        </x-slot:stats>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'tenant-roles-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
