@extends('tenant.layouts.layoutMaster')

@section('title', 'الفواتير')

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

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="bill-table">
        <x-slot:filters>
            <x-data-table.filters table="#bill-table">
                <x-filter.select id="filter-type" filterKey="type" label="نوع العملية"
                    placeholder="اختر نوع العملية" :enum="\App\Enums\Tenants\Billing\BillOperationType::class" />
                <x-filter.select id="filter-status" filterKey="status" label="حالة العملية"
                    placeholder="اختر حالة العملية" :enum="\App\Enums\Tenants\Billing\BillStatus::class" />
                <x-filter.select id="filter-payment-method" filterKey="payment_method" label="طريقة الدفع"
                    placeholder="اختر طريقة الدفع" :enum="\App\Enums\Tenants\Billing\PaymentMethod::class" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'bill-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
