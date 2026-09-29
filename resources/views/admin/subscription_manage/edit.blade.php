@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل الشركة')

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
    @vite(['resources/assets/js/admin/subscriptions/edit-form.js'])
@endsection

@section('content')

    <div class="row">
        <div class="col-12">
            <div id="wizard-validation" class="bs-stepper">
                <!-- Stepper Header -->
                <div class="bs-stepper-header">
                    <div class="step" data-target="#info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">الباقة و التراخيص</span>
                                <span class="bs-stepper-subtitle">تعديل الباقة وعدد التراخيص</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content p-4">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ route('admin.companies.update', $subscription->id) }}"
                        method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div id="info-validation" class="content">
                            <div class="row g-4">

                                <!-- العميل (للعرض فقط) -->
                                <div class="col-md-6">
                                    <label class="form-label" for="tenant_id">
                                        العميل
                                    </label>
                                    <input type="text" class="form-control"
                                        value="{{ $subscription->tenant->company_name }} - ({{ $subscription->tenant->id }})"
                                        readonly disabled>
                                    <input type="hidden" name="tenant_id" value="{{ $subscription->tenant_id }}">
                                </div>

                                <!-- رقم الاشتراك (للعرض فقط) -->
                                <div class="col-md-6">
                                    <label class="form-label" for="subscription_number">
                                        رقم الاشتراك
                                    </label>
                                    <input type="text" class="form-control"
                                        value="{{ $subscription->subscription_number }}" readonly disabled>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="tax_number">الرقم الضريبي <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="tax_number" name="tax_number"
                                        value="{{ old('tax_number', $subscription->tenant->tax_number) }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="city_id">المدينة <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select select2" id="city_id" name="city_id" required>
                                        <option value="">اختر المدينة</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->id }}" @selected(old('city_id', $subscription->tenant->city_id) == $city->id)>
                                                {{ $city->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label" for="address">العنوان <span
                                            class="text-danger">*</span></label>
                                    <textarea class="form-control" id="address" name="address" rows="2" required>{{ old('address', $subscription->tenant->address) }}</textarea>
                                </div>

                                <!-- اختيار الباقة -->
                                <div class="col-md-6">
                                    <label class="form-label" for="plan_id">
                                        <i class="ti ti-package me-1"></i>
                                        الباقة
                                    </label>
                                    <select class="form-select select2" data-placeholder="اختر الباقة" name="plan_id"
                                        id="plan_id" required>
                                        <option value=""></option>
                                        @foreach ($plans as $plan)
                                            <option value="{{ $plan->id }}" data-price="{{ $plan->price }}"
                                                data-yearly-price="{{ $plan->yearly_renewal_price }}"
                                                data-users="{{ $plan->included_users }}"
                                                data-duration="{{ $plan->subscription_duration_type }}"
                                                data-cycle="{{ $plan->subscription_billing_cycle }}"
                                                {{ old('plan_id', $subscription->plan_id) == $plan->id ? 'selected' : '' }}>
                                                {{ $plan->name }} - ({{ number_format($plan->price, 2) }} ريال)
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">الباقة الحالية: {{ $subscription->plan->name }}</small>
                                </div>

                                <!-- سعر الاشتراك -->
                                <div class="col-md-6">
                                    <label class="form-label" for="price">
                                        <i class="ti ti-currency-dollar me-1"></i>
                                        سعر الاشتراك (ريال)
                                    </label>
                                    <input type="number" class="form-control" name="price" id="price" min="0"
                                        step="0.01" value="{{ old('price', $subscription->price) }}" placeholder="0.00"
                                        required>
                                    <small class="text-muted">السعر الأصلي للباقة:
                                        {{ number_format($subscription->plan->price, 2) }} ريال</small>
                                </div>

                                <!-- عدد المستخدمين المتاحين -->
                                <div class="col-md-6">
                                    <label class="form-label" for="total_users">
                                        <i class="ti ti-users me-1"></i>
                                        إجمالي عدد المستخدمين المتاح
                                    </label>
                                    <input type="number" class="form-control" name="total_users" id="total_users"
                                        min="1" step="1"
                                        value="{{ old('total_users', $subscription->total_users) }}" placeholder="10"
                                        required>
                                    <small class="text-muted">
                                        المستخدمين المستهلكين: {{ $subscription->used_users }} /
                                        {{ $subscription->total_users }}
                                    </small>
                                </div>

                                <!-- عدد التراخيص الإضافية -->
                                <div class="col-md-6">
                                    <label class="form-label" for="licenses-count">
                                        <i class="ti ti-license me-1"></i>
                                        عدد التراخيص الإضافية
                                    </label>
                                    <input type="number" class="form-control" name="licenses_quota" id="licenses-count"
                                        min="0"
                                        value="{{ old('licenses_quota', $subscription->used_users ?? 0) }}"
                                        step="1" placeholder="0">
                                    <small class="text-muted">سعر الترخيص الإضافي:
                                        {{ number_format($subscription->plan->extra_user_license_price, 2) }} ريال</small>
                                </div>

                                <!-- حالة الاشتراك -->
                                <div class="col-md-6">
                                    <label class="form-label" for="status">
                                        <i class="ti ti-info-circle me-1"></i>
                                        حالة الاشتراك
                                    </label>
                                    <select class="form-select select2" data-placeholder="اختر حالة الاشتراك"
                                        name="status" id="status" required>
                                        <option value=""></option>
                                        @foreach ($allStatus as $status)
                                            <option value="{{ $status['id'] }}"
                                                {{ old('status', $subscription->status->value) == $status['id'] ? 'selected' : '' }}>
                                                {{ $status['name'] }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- حالة الدفع -->
                                {{-- <div class="col-md-6">
                                    <label class="form-label" for="payment_status">
                                        <i class="ti ti-credit-card me-1"></i>
                                        حالة الدفع
                                    </label>
                                    <select class="form-select select2" data-placeholder="اختر حالة الدفع" name="payment_status"
                                        id="payment_status" required>
                                        <option value=""></option>
                                        <option value="paid" {{ old('payment_status', $subscription->payment_status ?? 'pending') == 'paid' ? 'selected' : '' }}>
                            مدفوع
                            </option>
                            <option value="pending" {{ old('payment_status', $subscription->payment_status ?? 'pending') == 'pending' ? 'selected' : '' }}>
                                قيد الانتظار
                            </option>
                            <option value="failed" {{ old('payment_status', $subscription->payment_status ?? 'pending') == 'failed' ? 'selected' : '' }}>
                                فشل
                            </option>
                            <option value="refunded" {{ old('payment_status', $subscription->payment_status ?? 'pending') == 'refunded' ? 'selected' : '' }}>
                                مسترد
                            </option>
                            </select>
                        </div> --}}

                                <!-- تاريخ البدء -->
                                <div class="col-md-6">
                                    <label class="form-label" for="starts_at">
                                        <i class="ti ti-calendar me-1"></i>
                                        تاريخ البدء
                                    </label>
                                    <input type="date" class="form-control" name="starts_at" id="starts_at"
                                        value="{{ old('starts_at', \Carbon\Carbon::parse($subscription->starts_at)->format('Y-m-d')) }}"
                                        required>
                                </div>

                                <div class="col-md-6"><label class="form-label" for="ends_at">تاريخ نهاية الاشتراك <span
                                            id="ends-at-required" class="text-danger">*</span></label><input
                                        type="date" class="form-control" name="ends_at" id="ends_at"
                                        value="{{ old('ends_at', $subscription->ends_at ? \Carbon\Carbon::parse($subscription->ends_at)->format('Y-m-d') : '') }}"><small
                                        id="ends-at-hint" class="text-muted"></small></div>
                                <!-- تاريخ الدفع التالي -->
                                <div class="col-md-6">
                                    <label class="form-label" for="next_payment_date">
                                        <i class="ti ti-calendar-event me-1"></i>
                                        تاريخ الدفع التالي
                                    </label>
                                    <input type="date" class="form-control" name="next_payment_date"
                                        id="next_payment_date"
                                        value="{{ old('next_payment_date', $subscription->next_payment_date ? \Carbon\Carbon::parse($subscription->next_payment_date)->format('Y-m-d') : '') }}">
                                </div>

                                <!-- التجديد التلقائي -->
                                <div class="col-md-12">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="auto_renew"
                                            id="auto_renew" value="1"
                                            {{ old('auto_renew', $subscription->auto_renew) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="auto_renew">
                                            <i class="ti ti-refresh me-1"></i>
                                            تفعيل التجديد التلقائي
                                        </label>
                                    </div>
                                </div>

                                <!-- ملاحظات إضافية -->
                                <div class="col-md-12">
                                    <div class="alert alert-info mb-0">
                                        <h6 class="alert-heading mb-2">
                                            <i class="ti ti-info-circle me-1"></i>
                                            معلومات الباقة الحالية
                                        </h6>
                                        <ul class="mb-0">
                                            <li>اسم الباقة: <strong>{{ $subscription->plan->name }}</strong></li>
                                            <li>السعر الأصلي: <strong>{{ number_format($subscription->plan->price, 2) }}
                                                    ريال</strong></li>
                                            <li>سعر التجديد السنوي:
                                                <strong>{{ number_format($subscription->plan->yearly_renewal_price, 2) }}
                                                    ريال</strong>
                                            </li>
                                            <li>عدد المستخدمين المضمن: <strong>{{ $subscription->plan->included_users }}
                                                    مستخدم</strong></li>
                                            <li>سعر الترخيص الإضافي:
                                                <strong>{{ number_format($subscription->plan->extra_user_license_price, 2) }}
                                                    ريال</strong>
                                            </li>
                                            @if ($subscription->plan->unlimited_branches)
                                                <li>الفروع: <strong>غير محدودة</strong></li>
                                            @endif
                                        </ul>
                                    </div>
                                </div>

                            </div>

                            <div class="col-12 d-flex justify-content-end mt-4">
                                <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary me-2">
                                    <i class="ti ti-arrow-right me-1"></i>
                                    إلغاء
                                </a>
                                <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                    <i class="ti ti-device-floppy me-1"></i>
                                    <span>حفظ التعديلات</span>
                                </button>
                            </div>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var priceInput = document.getElementById('price');
        var licensesInput = document.getElementById('licenses-count');

        // سعر الباقة فقط (من غير تراخيص)
        var basePrice = parseFloat("{{ $subscription->plan->price }}");
        var licensePrice = parseFloat("{{ $subscription->plan->extra_user_license_price }}");

        function calculatePrice() {
            var licensesCount = parseInt(licensesInput.value) || 0;
            var totalPrice = basePrice + (licensesCount * licensePrice);

            priceInput.value = totalPrice.toFixed(2);
        }

        licensesInput.addEventListener('input', calculatePrice);
    </script>


@endsection
