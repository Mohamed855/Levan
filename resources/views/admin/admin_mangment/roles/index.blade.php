@extends('admin.layouts.layoutMaster')

@section('title', 'الأدوار والصلاحيات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
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
            massDelete: "{{ route('admin.mass-delete') }}",
        };

        $(function () {
            const tableWrapper = $('#roles-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#roles-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#roles-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد دور واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Role');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="roles-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي الأدوار" :value="$statistics['total']" subtitle="كل الأدوار الإدارية" icon="ti-shield" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="أدوار مستخدمة" :value="$statistics['assigned']" subtitle="مرتبطة بمستخدم واحد أو أكثر" icon="ti-users" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="أدوار غير مستخدمة" :value="$statistics['unassigned']" subtitle="جاهزة للإسناد" icon="ti-user-question" color="info" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي الصلاحيات" :value="$statistics['permissions']" subtitle="صلاحيات حارس الإدارة" icon="ti-key" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="أدوار مخصصة" :value="$statistics['custom']" subtitle="باستثناء دور SuperAdmin" icon="ti-settings" color="danger" />
        </x-slot:stats>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'roles-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
