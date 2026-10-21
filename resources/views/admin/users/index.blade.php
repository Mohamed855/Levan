@extends('layouts.dashboard')

@section('title', 'مستخدمو الإدارة')
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
            const tableWrapper = $('#users-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#users-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#users-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد مستخدم واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'AdminUser');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="users-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي المستخدمين"
                :value="$totalUsers"
                subtitle="كل حسابات الإدارة"
                icon="ti-users"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="المستخدمين النشطين"
                :value="$activeUsers"
                subtitle="حسابات مفعلة"
                icon="ti-user-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="مدراء النظام"
                :value="$superAdminUsers"
                subtitle="صلاحيات كاملة"
                icon="ti-shield-lock"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="المستخدمين غير النشطين"
                :value="$inactiveUsers"
                subtitle="حسابات معطلة"
                icon="ti-user-off"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="المستخدمين المؤرشفين"
                :value="$archivedUsers"
                subtitle="سجلات مؤرشفة"
                icon="ti-archive"
                color="danger"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#users-table">
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#users-table"
                    label="حالة الحساب"
                    placeholder="اختر حالة الحساب"
                    :options="$filterOptions['statuses']"
                />
                <x-filter.select
                    id="filter-role"
                    filterKey="role"
                    table="#users-table"
                    label="الدور الإداري"
                    placeholder="اختر الدور الإداري"
                    :options="$filterOptions['roles']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'users-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
