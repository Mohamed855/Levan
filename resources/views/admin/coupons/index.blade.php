@extends('layouts.dashboard')

@section('title', 'الكوبونات')
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
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}"
        };

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function () {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.warning('يرجى تحديد كوبون واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Coupon');
            }
        }
        window.handleMassDelete = handleMassDelete;

        $(document).ready(function () {
            const tableWrapper = $('#coupon-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#coupon-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#coupon-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });
    </script>
@endsection

@section('content')
    <x-ui.validation-errors class="mb-4" />

    <x-data-table.page table-id="coupon-table">
        <x-slot:stats>
            <x-ui.stat-card title="إجمالي الكوبونات" :value="$totalCoupons ?? 0" subtitle="جميع الكوبونات المسجلة" icon="ti-ticket"
                color="primary" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="الكوبونات النشطة" :value="$activeCoupons ?? 0" subtitle="كوبونات مفعلة حالياً" icon="ti-rosette-discount"
                color="success" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="الكوبونات غير النشطة" :value="$inactiveCoupons ?? 0" subtitle="معطلة مؤقتاً" icon="ti-rosette-discount-off"
                color="warning" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="الكوبونات المنتهية" :value="$expiredCoupons ?? 0" subtitle="تجاوزت تاريخ الصلاحية" icon="ti-calendar-off"
                color="danger" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="مرات الاستخدام" :value="$usedCount ?? 0" subtitle="إجمالي مرات الاستخدام" icon="ti-discount-check"
                color="info" col="col-sm-6 col-xl" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#coupon-table">
                <x-filter.select id="filter-status" filterKey="is_active" label="حالة الكوبونات" placeholder="اختر حالة الكوبون"
                    :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-type" filterKey="discount_type" label="نوع الخصم" placeholder="اختر نوع الخصم"
                    :options="$filterOptions['types'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'coupon-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
