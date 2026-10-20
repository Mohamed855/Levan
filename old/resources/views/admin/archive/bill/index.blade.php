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
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
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
    <x-data-table.filters table="#bill-archive-table">
        <x-filter.select id="filter-type" filterKey="type" label="نوع الفاتورة"
            placeholder="اختر نوع الفاتورة" :options="$filterOptions['types'] ?? []" />
        <x-filter.select id="filter-status" filterKey="status" label="حالة الدفع"
            placeholder="اختر حالة الدفع" :options="$filterOptions['statuses'] ?? []" />
        <x-filter.select id="filter-payment-method" filterKey="payment_method" label="طريقة الدفع"
            placeholder="اختر طريقة الدفع" :options="$filterOptions['paymentMethods'] ?? []" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'bill-archive-table'],
                false,
            ) !!}
        </div>
    </div>
@endsection
