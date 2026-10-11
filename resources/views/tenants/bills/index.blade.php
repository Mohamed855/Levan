@extends('tenants.layouts.layoutMaster')

@section('title', 'الفواتير ')

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
    <x-data-table.filters table="#bill-table" reset-id="btn-reset-filters">
        <div>
            <label class="form-label" for="filter-type">نوع العملية</label>
            <select id="filter-type" class="form-control select2" data-datatable-filter="true" data-filter-key="type" data-placeholder="اختر نوع العملية">
                <option value=""></option>
                <x-form.controls.enum-options :enum="\App\Enums\Tenants\Billing\BillOperationType::class" />
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-status">حالة العملية</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="اختر حالة العملية">
                <option value=""></option>
                <x-form.controls.enum-options :enum="\App\Enums\Tenants\Billing\BillStatus::class" />
            </select>
        </div>
        <div>
            <label class="form-label" for="filter-payment-method">طريقة الدفع</label>
            <select id="filter-payment-method" class="form-control select2" data-datatable-filter="true" data-filter-key="payment_method" data-placeholder="اختر طريقة الدفع">
                <option value=""></option>
                <x-form.controls.enum-options :enum="\App\Enums\Tenants\Billing\PaymentMethod::class" />
            </select>
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

@endsection
