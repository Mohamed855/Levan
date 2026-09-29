@extends('admin.layouts.layoutMaster')

@section('title', 'إنشاء شركة جديد')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/subscriptions/create-form.js'])
    <script>
        window.plansData = @json($plans);
    </script>
@endsection

@section('content')

    <div class="row">
        <div class="col-12">
            <div id="subscription-wizard" class="bs-stepper">
                <!-- Stepper Header -->
                <div class="bs-stepper-header">
                    <div class="step" data-target="#step-plan">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">بيانات الشركة</span>
                                <span class="bs-stepper-subtitle">بيانات الشركة الاساسية </span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#step-payment">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">الباقة و الدفع</span>
                                <span class="bs-stepper-subtitle">تفاصيل الباقة و الدفع</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="p-4 bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="subscription-form" action="{{ route('admin.companies.store') }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf

                        <div id="step-plan" class="content">
                            <div class="row g-2">
                                <!-- الاسم -->
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label"> اسم الشركة
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="company_name" name="company_name"
                                        value="{{ old('company_name') }}" placeholder="مثال:  شركة المجد العقارية "
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label for="commercial_number" class="form-label">رقم السجل التجاري
                                        <span id="discountUnit"></span>
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" inputmode="numeric" pattern="[0-9]{10}" maxlength="10"
                                        class="form-control" id="commercial_number" name="commercial_number"
                                        placeholder="مثال: 1010123456" value="{{ old('commercial_number') }}" required
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                                </div>

                                <div class="col-md-6">
                                    <label for="name" class="form-label"> اسم المستخدم
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name') }}" placeholder="اسم المستخدم " required>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label"> رقم الجوال
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="tel" inputmode="numeric" pattern="05[0-9]{8}" maxlength="10"
                                        class="form-control" id="phone" name="phone" placeholder="05xxxxxxxx"
                                        value="{{ old('phone') }}" required
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10)">
                                </div>

                                <div class="col-md-4">
                                    <label for="email" class="form-label"> البريد الإلكتروني
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="{{ old('email') }}" placeholder="example@domain.com" required>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label" for="password">
                                        كلمة المرور
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" id="password" name="password" class="form-control"
                                        placeholder="••••••••" required />
                                </div>

                                <!-- تأكيد كلمة المرور -->
                                <div class="col-md-4">
                                    <label class="form-label" for="password_confirmation">
                                        تأكيد كلمة المرور
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="password" id="password_confirmation" name="password_confirmation"
                                        class="form-control" placeholder="••••••••" required />
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-end">
                                <button type="button" class="btn btn-primary btn-next" id="btn-next-plan">
                                    التالي <i class="ti ti-arrow-right ms-2"></i>
                                </button>
                            </div>
                        </div>

                        <div id="step-payment" class="content">

                            <div class="row g-2">
                                <div class="col-md-6">
                                    <label for="plan_id" class="form-label">
                                        الباقة
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="plan_id" name="plan_id" class="form-select select2" required
                                        data-placeholder="اختر الباقة">
                                        <option value=""> </option>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan['id'] }}"
                                                {{ old('plan_id') == $plan['id'] ? 'selected' : '' }}>
                                                {{ $plan['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- حالة الدفع -->
                                <div class="col-md-6">
                                    <label class="form-label" for="payment-status">
                                        حالة الدفع
                                    </label>
                                    <select class="form-select select2" data-placeholder="اختر حالة الدفع "
                                        name="payment_type" id="payment-status" required>
                                        <option value=""></option>
                                        <option value="paid"> تم الدفع</option>
                                        <option value="unpaid"> لم يتم الدفع</option>
                                        <option value="trial">فترة تجربة</option>
                                    </select>
                                </div>

                                <!-- وسيلة الدفع -->
                                <div class="col-md-6">
                                    <label class="form-label" for="payment-method-select">
                                        وسيلة الدفع
                                    </label>
                                    <select class="form-select select2" name="payment_method"
                                        data-placeholder="اختر وسيلة الدفع " id="payment-method-select" required>

                                        <option value=""></option>
                                        <option value="cash">نقداً</option>
                                        <option value="online"> دفع إلكتروني</option>
                                        <option value="bank_transfer">
                                            تحويل بنكي
                                            -
                                            <span> يحتاج إيصال التحويل البنكي </span>
                                        </option>
                                    </select>
                                </div>

                                <!-- حقل رفع الإيصال البنكي -->
                                <div class="col-md-6" id="receipt-upload-wrapper" style="display: none;">
                                    <label class="form-label" for="receipt-upload">
                                        إيصال التحويل البنكي <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" class="form-control" name="receipt_upload" id="receipt-upload"
                                        accept="image/*,.pdf" data-fv-field="receipt_upload">
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle me-1"></i>
                                        يرجى رفع صورة أو ملف PDF للإيصال البنكي
                                    </small>
                                </div>

                                <!-- التسوية -->
                                <div class="col-md-6" id="discount-type-wrapper">
                                    <label class="form-label" for="discount-type">
                                        التسوية (الخصم)
                                    </label>
                                    <select class="form-select select2" name="has_discount" id="discount-type"
                                        data-placeholder="هل يوجد تسوية ">
                                        <option value=""></option>

                                        <option value="no">بدون تسوية</option>
                                        <option value="yes">دفع بتسوية</option>
                                    </select>
                                </div>

                                <div class="col-md-6" id="trial-end-date-wrapper" style="display: none;">
                                    <label class="form-label" for="trial-end-date">
                                        تاريخ نهاية التجربة <span class="text-danger">*</span>
                                    </label>
                                    <input type="date" class="form-control" name="trial_end_date" id="trial-end-date"
                                        value="{{ old('trial_end_date') }}" min="{{ now()->toDateString() }}">
                                </div>

                                <!-- قيمة التسوية -->
                                <div class="col-md-6" id="discount-value-wrapper" style="display: none;">
                                    <label class="form-label" for="discount-value-input">
                                        قيمة التسوية
                                    </label>
                                    <input type="number" class="form-control" name="discount_value"
                                        id="discount-value-input" min="0" step="0.01" value="0">
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle me-1"></i>
                                        سيتم خصم هذا المبلغ من الإجمالي
                                    </small>
                                </div>

                                <div class="col-12">
                                    <!-- ملخص الاشتراك النهائي -->
                                    <div class="py-4 card-header">
                                        <h6 class="mb-0 card-title">
                                            <i class="ti ti-list-details text-warning me-2"></i>
                                            تفاصيل الاشتراك
                                        </h6>
                                    </div>

                                    <div class="mb-2 border-dashed border-1 border-light"></div>

                                    <div class="px-5 card-body">
                                        <div class="table-responsive">
                                            <table class="table text-center table-striped small">
                                                <tr>
                                                    <td class="fw-bold">الباقة المختارة</td>
                                                    <td>
                                                        <strong id="final-plan-name">-</strong>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="fw-bold">دورة الفوترة</td>
                                                    <td>
                                                        <strong>سنوي</strong>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="fw-bold">سعر الباقة</td>
                                                    <td>
                                                        <span id="final-plan-price">0.00</span>
                                                    </td>
                                                </tr>

                                                <tr class="table-active">
                                                    <td class="fw-bold">المجموع قبل الضريبة</td>
                                                    <td>
                                                        <strong><span id="final-subtotal">0.00</span> </strong>
                                                    </td>
                                                </tr>

                                                <tr>
                                                    <td class="fw-bold">ضريبة القيمة المضافة (15%)</td>
                                                    <td>
                                                        <span id="final-vat">0.00</span>
                                                    </td>
                                                </tr>

                                                <tr class="table-active">
                                                    <td class="fw-bold">المجموع شامل الضريبة</td>
                                                    <td>
                                                        <strong><span id="final-total-with-vat">0.00</span>
                                                        </strong>
                                                    </td>
                                                </tr>

                                                <tr class="text-danger">
                                                    <td class="fw-bold">التسوية (الخصم)</td>
                                                    <td>
                                                        - <span id="final-discount">0.00</span>
                                                    </td>
                                                </tr>

                                                <tr class="table-primary">
                                                    <td class="fw-bold">الإجمالي النهائي</td>
                                                    <td>
                                                        <strong class="text-primary">
                                                            <span id="final-total">0.00</span>
                                                            <span class="icon-saudi_riyal"></span>
                                                        </strong>
                                                    </td>
                                                </tr>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4 d-flex justify-content-between">
                                <button type="button" class="btn btn-label-secondary btn-prev">
                                    <i class="ti ti-arrow-left me-2"></i> السابق
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    حفظ
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
