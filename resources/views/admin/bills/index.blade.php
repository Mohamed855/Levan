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
    <div class="mb-4 row g-4">
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="المعاملات" :value="$billcounts" subtitle="إجمالي المعاملات"
            icon="ti-receipt-2" color="primary" />

        <x-ui.stat-card col="col-sm-6 col-xl-3" title="المعاملات المدفوعة" :value="$paidbills"
            subtitle="إجمالي المعاملات المدفوعة" icon="ti-cash" color="success" />

        <x-ui.stat-card col="col-sm-6 col-xl-3" title="المعاملات الغير مدفوعة" :value="$unpaidbills"
            subtitle="إجمالي المعاملات الغير مدفوعة" icon="ti-file-invoice" color="warning" />

        <x-ui.stat-card col="col-sm-6 col-xl-3" title="معاملات لم يوافق عليها" :value="$pendingbills"
            subtitle="إجمالي المعاملات التي لم يوافق عليها" icon="ti-ban" color="danger" />
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-data-table.filters table="#bill-table">
        <x-filter.select id="filter-status" filterKey="type" label="نوع العملية"
            placeholder="اختر نوع العملية" :options="$filterOptions['operationTypes'] ?? []" />
        <x-filter.select id="filter-paid" filterKey="status" label="حالة العملية"
            placeholder="اختر حالة العملية" :options="$filterOptions['statuses'] ?? []" />
        <x-filter.select id="filter-type" filterKey="payment_method" label="نوع الدفع"
            placeholder="اختر نوع الدفع" :options="$filterOptions['paymentTypes'] ?? []" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'bill-table'], false) !!}
        </div>
    </div>

@endsection
