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
        <div class="col-md-4">
            <label class="form-label" for="filter-type">نوع العملية</label>
            <select id="filter-type" class="form-control select2" data-datatable-filter="true" data-filter-key="type" data-placeholder="اختر نوع العملية">
                <option value=""></option>
                <option value="subscription">اشتراك</option>
                <option value="renewal">تجديد</option>
                <option value="extra_users">مستخدمين إضافيين</option>
                <option value="domain">دومين</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="filter-status">حالة العملية</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="اختر حالة العملية">
                <option value=""></option>
                <option value="paid">مدفوعة</option>
                <option value="unpaid">غير مدفوعة</option>
                <option value="partially_paid">مدفوعة جزئياً</option>
                <option value="cancelled">ملغاة</option>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="filter-payment-method">طريقة الدفع</label>
            <select id="filter-payment-method" class="form-control select2" data-datatable-filter="true" data-filter-key="payment_method" data-placeholder="اختر طريقة الدفع">
                <option value=""></option>
                <option value="bank_transfer">تحويل بنكي</option>
                <option value="credit_card">بطاقة ائتمانية</option>
                <option value="cash">نقدي</option>
            </select>
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

@endsection
