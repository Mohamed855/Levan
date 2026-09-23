@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل التحصيل')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            generateReceiptNumber: "{{ tenant_route_name('financial-collections.collections.generate-receipt-number') }}",
            searchCustomers: "{{ tenant_route_name('financial-collections.collections.search-customers') }}",
            getCustomer: "{{ tenant_route_name('financial-collections.collections.get-customer', ':id') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
        window.collectionId = {{ $collection->id }};
    </script>
    @vite(['resources/assets/js/tenants/financial_collection/collections/validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#basic-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">معلومات العميل والمبلغ</span>
                                <span class="bs-stepper-subtitle">العميل والمبلغ المحصل</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#collection-details-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">تفاصيل التحصيل</span>
                                <span class="bs-stepper-subtitle">نوع وطريقة التحصيل</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#payment-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">معلومات السند والمرفقات</span>
                                <span class="bs-stepper-subtitle">رقم السند والتواريخ</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if ($collection->status === 'approved')
                        <div class="alert alert-warning">
                            <i class="ti ti-alert-triangle me-2"></i>
                            هذا التحصيل معتمد. لا يمكن تعديله.
                        </div>
                    @endif

                    <form id="wizard-validation-form"
                        action="{{ tenant_route_name('financial-collections.collections.update', $collection) }}"
                        method="POST" enctype="multipart/form-data" onSubmit="return false">
                        @csrf
                        @method('PUT')

                        <!-- معلومات العميل والمبلغ -->
                        <div id="basic-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="customer_id">العميل <span
                                            class="text-danger">*</span></label>
                                    <select name="customer_id" id="customer_id" class="form-control select2-ajax"
                                        data-placeholder="ابحث عن العميل برقم الحساب"
                                        {{ $collection->status === 'approved' ? 'disabled' : '' }}>
                                        @if ($collection->customer)
                                            <option value="{{ $collection->customer->id }}" selected>
                                                {{ $collection->customer->account_number }}
                                            </option>
                                        @endif
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="collected_amount">المبلغ المحصل <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="collected_amount" id="collected_amount"
                                            class="form-control" placeholder="0.00" step="0.01" min="0"
                                            value="{{ old('collected_amount', $collection->collected_amount) }}"
                                            {{ $collection->status === 'approved' ? 'readonly' : '' }} />
                                        <span class="input-group-text"><span class="icon-saudi_riyal"></span></span>
                                    </div>
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-label-secondary btn-prev" disabled>
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- تفاصيل التحصيل -->
                        <div id="collection-details-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="collection_type">نوع التحصيل <span
                                            class="text-danger">*</span></label>
                                    <select name="collection_type" id="collection_type" class="form-control select2"
                                        data-placeholder="اختر نوع التحصيل"
                                        {{ $collection->status === 'approved' ? 'disabled' : '' }}>
                                        <option value=""></option>
                                        <option value="cash"
                                            {{ old('collection_type', $collection->collection_type) == 'cash' ? 'selected' : '' }}>
                                            نقدي</option>
                                        <option value="scheduled"
                                            {{ old('collection_type', $collection->collection_type) == 'scheduled' ? 'selected' : '' }}>
                                            مجدول</option>
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="payment_method_id">طريقة التحصيل <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_method_id" id="payment_method_id" class="form-control select2"
                                        data-placeholder="اختر طريقة التحصيل"
                                        {{ $collection->status === 'approved' ? 'disabled' : '' }}>
                                        <option value=""></option>
                                        @foreach ($paymentMethods as $method)
                                            <option value="{{ $method->id }}"
                                                {{ old('payment_method_id', $collection->payment_method_id) == $method->id ? 'selected' : '' }}>
                                                {{ $method->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="payment_type">نوع السداد <span
                                            class="text-danger">*</span></label>
                                    <select name="payment_type" id="payment_type" class="form-control select2"
                                        data-placeholder="اختر نوع السداد"
                                        {{ $collection->status === 'approved' ? 'disabled' : '' }}>
                                        <option value=""></option>
                                        <option value="settlement"
                                            {{ old('payment_type', $collection->payment_type) == 'settlement' ? 'selected' : '' }}>
                                            سداد بتسوية</option>
                                        <option value="without_settlement"
                                            {{ old('payment_type', $collection->payment_type) == 'without_settlement' ? 'selected' : '' }}>
                                            سداد بدون تسوية</option>
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="collection_date">تاريخ التحصيل <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="collection_date" id="collection_date"
                                        class="form-control flatpickr-date" placeholder="اختر تاريخ التحصيل"
                                        value="{{ old('collection_date', $collection->collection_date?->format('Y-m-d')) }}"
                                        {{ $collection->status === 'approved' ? 'readonly' : '' }} />
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-label-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- معلومات السند والمرفقات -->
                        <div id="payment-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="receipt_number">رقم السند <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="receipt_number" id="receipt_number"
                                            class="form-control" placeholder="رقم السند"
                                            value="{{ old('receipt_number', $collection->receipt_number) }}"
                                            {{ $collection->status === 'approved' ? 'readonly' : '' }} />
                                        @if ($collection->status !== 'approved')
                                            <button type="button" class="btn btn-outline-secondary"
                                                id="generate-receipt" title="توليد رقم جديد">
                                                <i class="ti ti-refresh"></i>
                                            </button>
                                        @endif
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="closing_code">رمز الإقفال</label>
                                    <input type="text" name="closing_code" id="closing_code" class="form-control"
                                        placeholder="رمز الإقفال (اختياري)"
                                        value="{{ old('closing_code', $collection->closing_code) }}"
                                        {{ $collection->status === 'approved' ? 'readonly' : '' }} />
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="attachment">المرفق</label>

                                    @if ($collection->attachment)
                                        <div class="current-file mb-2">
                                            <div class="d-flex align-items-center p-2 border rounded">
                                                <i class="ti ti-paperclip me-2"></i>
                                                <a href="{{ tasset($collection->attachment) }}" target="_blank"
                                                    class="me-auto">
                                                    عرض المرفق الحالي
                                                </a>
                                                @if ($collection->status !== 'approved')
                                                    <div class="form-check">
                                                        <input type="checkbox" name="remove_attachment"
                                                            id="remove_attachment" class="form-check-input"
                                                            value="1">
                                                        <label class="form-check-label" for="remove_attachment">
                                                            حذف المرفق
                                                        </label>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif

                                    @if ($collection->status !== 'approved')
                                        <input type="file" name="attachment" id="attachment" class="form-control"
                                            accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" />
                                        <div class="form-text">
                                            أنواع الملفات المدعومة: PDF, JPG, PNG, DOC, DOCX, XLS, XLSX (الحد الأقصى: 10
                                            ميجابايت)
                                            @if ($collection->attachment)
                                                <br><small class="text-warning">اختيار ملف جديد سيستبدل المرفق
                                                    الحالي</small>
                                            @endif
                                        </div>
                                    @endif
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-label-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    @if ($collection->status !== 'approved')
                                        <button type="submit" class="btn btn-primary btn-next btn-submit">
                                            <span class="align-middle d-sm-inline-block d-none me-sm-2">تحديث</span>
                                        </button>
                                    @else
                                        <a href="{{ tenant_route_name('financial-collections.collections.index') }}"
                                            class="btn btn-primary">
                                            العودة للقائمة
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
