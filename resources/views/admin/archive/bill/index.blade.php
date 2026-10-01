@extends('admin.layouts.layoutMaster')

@section('title', 'ارشيف الفواتير')

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
            archiveIndex: "{{ route('admin.archives.bills.index') }}",
            restore: "{{ route('admin.archives.bills.restore', ['id' => ':id']) }}",
            forceDelete: "{{ route('admin.archives.bills.force-delete', ['id' => ':id']) }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/admin/archive/bills/index.js'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <x-filter.select col="col-md-4" id="filter-type" label="نوع الفاتورة"
                    placeholder="اختر نوع الفاتورة" :options="$filterOptions['types'] ?? []" />
                <x-filter.select col="col-md-4" id="filter-status" label="حالة الدفع"
                    placeholder="اختر حالة الدفع" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select col="col-md-4" id="filter-payment-method" label="طريقة الدفع"
                    placeholder="اختر طريقة الدفع" :options="$filterOptions['paymentMethods'] ?? []" />
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
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'bill-archive-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
