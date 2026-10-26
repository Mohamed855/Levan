@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة المعاملات المالية ')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}",
        };
    </script>
    @vite(['resources/assets/js/admin/bills/index.js'])
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="bill-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="المعاملات" :value="$totalBills ?? ($billcounts ?? 0)" subtitle="إجمالي المعاملات"
                icon="ti-receipt-2" color="primary" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="المعاملات المدفوعة" :value="$paidBills ?? ($paidbills ?? 0)"
                subtitle="إجمالي المعاملات المدفوعة" icon="ti-cash" color="success" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="المعاملات غير المدفوعة" :value="$unpaidBills ?? ($unpaidbills ?? 0)"
                subtitle="إجمالي المعاملات غير المدفوعة" icon="ti-file-invoice" color="warning" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="بانتظار المعالجة" :value="$pendingBills ?? ($pendingbills ?? 0)" subtitle="معاملات قيد المراجعة"
                icon="ti-clock-pause" color="info" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="معاملات مرفوضة" :value="$rejectedBills ?? 0"
                subtitle="إجمالي المعاملات المرفوضة" icon="ti-ban" color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#bill-table">
                <x-filter.select id="filter-status" filterKey="type" label="نوع العملية" placeholder="اختر نوع العملية"
                    :options="$filterOptions['operationTypes'] ?? []" />
                <x-filter.select id="filter-paid" filterKey="status" label="حالة العملية" placeholder="اختر حالة العملية"
                    :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-type" filterKey="payment_method" label="نوع الدفع" placeholder="اختر نوع الدفع"
                    :options="$filterOptions['paymentTypes'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'bill-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
