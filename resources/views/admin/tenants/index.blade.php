@extends('layouts.dashboard')

@section('title', 'العملاء / الشركات')
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
                    toastr.warning('يرجى تحديد عميل واحد على الأقل');
                }
                return;
            }

            if (typeof confirmDeleteSelectedmss === 'function') {
                confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Tenant');
            }
        }
        window.handleMassDelete = handleMassDelete;

        $(document).ready(function () {
            const tableWrapper = $('#tenants-table_wrapper');

            tableWrapper.on('click', '#select-all', function () {
                const table = $('#tenants-table').DataTable();
                $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
            });

            tableWrapper.on('change', '.row-checkbox', function () {
                if (!this.checked) {
                    $('#select-all').prop('checked', false);
                }
            });

            $('#tenants-table').on('draw.dt', function () {
                $('#select-all').prop('checked', false);
            });
        });
    </script>
@endsection

@section('content')
    <x-ui.validation-errors class="mb-4" />

    <x-data-table.page table-id="tenants-table">
        <x-slot:stats>
            <x-ui.stat-card title="إجمالي العملاء" :value="$statistics['total'] ?? 0" subtitle="جميع الشركات المسجلة" icon="ti-buildings"
                color="primary" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="العملاء النشطون" :value="$statistics['active'] ?? 0" subtitle="اشتراكات سارية" icon="ti-circle-check"
                color="success" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="العملاء المعلقون" :value="$statistics['pending'] ?? 0" subtitle="بانتظار السداد أو التفعيل" icon="ti-clock-pause"
                color="warning" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="فترة تجريبية" :value="$statistics['trial'] ?? 0" subtitle="تحت التجربة المجانية" icon="ti-flask"
                color="info" col="col-sm-6 col-xl" />
            <x-ui.stat-card title="بدون اشتراك" :value="$statistics['no_subscription'] ?? 0" subtitle="لم يشتركوا بعد" icon="ti-alert-circle"
                color="danger" col="col-sm-6 col-xl" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#tenants-table">
                <x-filter.select id="filter-tenant-city" filterKey="city_id" label="المدينة" placeholder="اختر المدينة"
                    :options="$filterOptions['cities'] ?? []" />
                <x-filter.select id="filter-tenant-plan" filterKey="plan_id" label="الباقة الحالية" placeholder="اختر الباقة"
                    :options="$filterOptions['plans'] ?? []" />
                <x-filter.select id="filter-tenant-status" filterKey="subscription_status" label="حالة الاشتراك" placeholder="اختر الحالة"
                    :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-tenant-expiring" filterKey="expiring_in" label="قرب الانتهاء" placeholder="قرب الانتهاء"
                    :options="$filterOptions['expiring'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'tenants-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
