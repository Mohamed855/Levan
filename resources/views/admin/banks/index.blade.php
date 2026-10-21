@extends('layouts.dashboard')

@section('title', 'الحسابات البنكية')
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
            const tableWrapper = $('#banks-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#banks-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#banks-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد حساب بنكي واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Bank');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="banks-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي الحسابات"
                :value="$statistics['total']"
                subtitle="كل الحسابات المضافة"
                icon="ti-building-bank"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="حسابات نشطة"
                :value="$statistics['active']"
                subtitle="متاحة للتحويلات"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="حسابات معطلة"
                :value="$statistics['inactive']"
                subtitle="متوقفة مؤقتاً"
                icon="ti-circle-x"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="حسابات مرقمة"
                :value="$statistics['with_account_number']"
                subtitle="تحتوي على رقم حساب"
                icon="ti-numbers"
                color="info"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="حسابات مع شعار"
                :value="$statistics['with_logo']"
                subtitle="تحتوي على شعار رسمي"
                icon="ti-photo"
                color="secondary"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#banks-table">
                <x-filter.select
                    id="filter-active"
                    filterKey="is_active"
                    table="#banks-table"
                    label="حالة التفعيل"
                    placeholder="اختر الحالة"
                    :options="$filterOptions['is_active']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'banks-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
