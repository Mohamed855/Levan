@extends('layouts.dashboard')

@section('title', 'الفواتير والعمليات المالية')
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
            const tableWrapper = $('#bill-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#bill-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#bill-table').on('draw.dt', function () {
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
                    toastr.warning('يرجى تحديد فاتورة واحدة على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Bill');
            }
        }

        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-data-table.page table-id="bill-table">
        <x-slot:stats>
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="إجمالي الفواتير"
                :value="$stats['total']"
                subtitle="كل الفواتير الصادرة"
                icon="ti-receipt"
                color="primary"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="فواتير مدفوعة"
                :value="$stats['paid']"
                subtitle="تم سدادها بالكامل"
                icon="ti-circle-check"
                color="success"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="قيد المعالجة"
                :value="$stats['pending']"
                subtitle="بانتظار السداد أو التحقق"
                icon="ti-clock"
                color="warning"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="فواتير متأخرة"
                :value="$stats['overdue']"
                subtitle="تجاوزت تاريخ الاستحقاق"
                icon="ti-alert-triangle"
                color="danger"
            />
            <x-ui.stat-card
                col="col-sm-6 col-xl"
                title="ملغاة ومسترجعة"
                :value="$stats['cancelled']"
                subtitle="عمليات ملغاة أو مستردة"
                icon="ti-ban"
                color="secondary"
            />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#bill-table">
                <x-filter.select
                    id="filter-status"
                    filterKey="status"
                    table="#bill-table"
                    label="حالة العملية"
                    placeholder="اختر الحالة"
                    :options="$filterOptions['statuses']"
                />
                <x-filter.select
                    id="filter-type"
                    filterKey="type"
                    table="#bill-table"
                    label="نوع العملية"
                    placeholder="اختر النوع"
                    :options="$filterOptions['types']"
                />
                <x-filter.select
                    id="filter-payment-method"
                    filterKey="payment_method"
                    table="#bill-table"
                    label="طريقة الدفع"
                    placeholder="اختر الطريقة"
                    :options="$filterOptions['paymentMethods']"
                />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'bill-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
