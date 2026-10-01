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
    </script>
    @vite(['resources/assets/js/admin/bills/filter.js', 'resources/assets/js/admin/bills/index.js'])
@endsection

@section('content')
    <div class="mb-4 row g-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المعاملات" :value="$billcounts" subtitle="إجمالي المعاملات"
            icon="ti-receipt-2" color="primary" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المعاملات المدفوعة" :value="$paidbills"
            subtitle="إجمالي المعاملات المدفوعة" icon="ti-cash" color="success" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="المعاملات الغير مدفوعة" :value="$unpaidbills"
            subtitle="إجمالي المعاملات الغير مدفوعة" icon="ti-file-invoice" color="warning" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="معاملات لم يوافق عليها" :value="$pendingbills"
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

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <x-filter.select col="col-md-4" id="filter-status" label="نوع العملية"
                    placeholder="اختر نوع العملية" :options="$filterOptions['operationTypes'] ?? []" />
                <x-filter.select col="col-md-4" id="filter-paid" label="حالة العملية"
                    placeholder="اختر حالة العملية" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select col="col-md-4" id="filter-type" label="نوع الدفع"
                    placeholder="اختر نوع الدفع" :options="$filterOptions['paymentTypes'] ?? []" />
            </div>
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="btn-reset-filters" class="btn btn-secondary">
                    <i class="ti ti-refresh me-1"></i>إعادة تعيين
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'bill-table'], false) !!}
        </div>
    </div>

@endsection
