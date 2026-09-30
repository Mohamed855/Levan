@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل الكوبون')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/admin/coupons/form-validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">تعديل الكوبون</span>
                                <span class="bs-stepper-subtitle">تحديث البيانات الأساسية</span>
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

                    <form id="wizard-validation-form" action="{{ route('admin.coupons.update', $coupon->id) }}"
                        method="POST" enctype="multipart/form-data" onSubmit="return false">
                        @csrf
                        @method('PUT')

                        <div id="info-validation" class="content">
                            <div class="row g-2">
                                <!-- الاسم -->
                                <div class="col-md-6">
                                    <label for="name" class="form-label">اسم الكوبون <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name', $coupon->name) }}" required>
                                </div>

                                <!-- الكود -->
                                <div class="col-md-6">
                                    <label for="code" class="form-label">كود الكوبون <span class="text-danger">*</span>
                                        <small class="text-muted">سيتم تحويل الكود إلى أحرف كبيرة تلقائيًا</small>
                                    </label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="code" name="code"
                                            value="{{ old('code', $coupon->code) }}" style="text-transform: uppercase;"
                                            required>
                                        <button type="button" class="btn btn-outline-secondary btn-generate-code" id="btn-generate-code" onclick="generateCode()">
                                            <i class="ti ti-wand"></i> توليد
                                        </button>
                                    </div>
                                </div>

                                <!-- نوع الخصم -->
                                <div class="col-md-6">
                                    <label for="discount_type" class="form-label">نوع الخصم <span
                                            class="text-danger">*</span></label>
                                    <select class="form-select select2" id="discount_type" name="discount_type" required
                                        onchange="updateDiscountFields()" data-placeholder="اختر النوع">
                                        <option value=""> </option>
                                        <option value="fixed"
                                            {{ old('discount_type', is_object($coupon->discount_type) ? $coupon->discount_type->value : $coupon->discount_type) == 'fixed' ? 'selected' : '' }}>
                                            مبلغ ثابت</option>
                                        <option value="percentage"
                                            {{ old('discount_type', is_object($coupon->discount_type) ? $coupon->discount_type->value : $coupon->discount_type) == 'percentage' ? 'selected' : '' }}>
                                            نسبة مئوية</option>
                                    </select>
                                </div>

                                <!-- قيمة الخصم -->
                                <div class="col-md-6">
                                    <label for="discount_value" class="form-label">قيمة الخصم <span
                                            id="discountUnit"></span>
                                        <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="discount_value" name="discount_value"
                                        value="{{ old('discount_value', $coupon->discount_value) }}" step="0.01"
                                        min="0" required>
                                </div>

                                <!-- الحد الأقصى -->
                                <div class="col-md-6" id="maxDiscountField" style="display:none;">
                                    <label for="max_discount" class="form-label">الحد الأقصى للخصم
                                        <small class="text-muted">ينطبق فقط على النسبة المئوية</small>

                                    </label>
                                    <input type="number" class="form-control" id="max_discount" name="max_discount"
                                        value="{{ old('max_discount', $coupon->max_discount) }}" step="0.01"
                                        min="0">
                                </div>

                                <!-- تاريخ الانتهاء -->
                                <div class="col-md-6">
                                    <label for="expiry_date" class="form-label">تاريخ الانتهاء</label>
                                    <input type="date" class="form-control" id="expiry_date" name="expiry_date"
                                        min="{{ date('Y-m-d') }}"
                                        value="{{ old('expiry_date', $coupon->expiry_date ? $coupon->expiry_date->format('Y-m-d') : '') }}">
                                </div>

                                <!-- حد الاستخدام -->
                                <div class="col-md-6">
                                    <label for="usage_limit" class="form-label">حد الاستخدام
                                        <small class="text-muted">اتركه فارغًا لاستخدام غير محدود</small>

                                    </label>
                                    <input type="number" class="form-control" id="usage_limit" name="usage_limit"
                                        value="{{ old('usage_limit', $coupon->usage_limit) }}" min="1">
                                </div>

                                <!-- الحالة -->
                                <div class="col-md-6 mb-4">
                                    <label for="is_active" class="form-label">حالة الكوبون <span
                                            class="text-danger">*</span></label>
                                    @php
                                        $currentStatus = old('is_active', $coupon->is_active);
                                        $isActiveSelected = ($currentStatus === true || $currentStatus === 1 || (string)$currentStatus === '1');
                                        $isInactiveSelected = ($currentStatus === false || $currentStatus === 0 || (string)$currentStatus === '0');
                                    @endphp
                                    <select id="is_active" name="is_active" class="form-select select2" data-placeholder="اختر الحالة">
                                        <option value=""></option>
                                        <option value="1" @selected($isActiveSelected)>مفعلة</option>
                                        <option value="0" @selected($isInactiveSelected)>غير مفعلة</option>
                                    </select>
                                </div>

                                <!-- إسناد الكوبون -->
                                <div class="col-md-6 mb-4">
                                    <label for="users" class="form-label">إسناد الكوبون</label>
                                    <select id="users" name="admin_user_id" class="form-select select2" data-placeholder="اختر الاسناد">
                                        <option value="">اختر الاسناد</option>
                                        @forelse($admin_users as $admin_user)
                                        <option value="{{ $admin_user->id }}" {{ (string) old('admin_user_id', $coupon->admin_user_id) === (string) $admin_user->id ? 'selected' : '' }}>
                                        {{ $admin_user->name }}
                                        </option>
                                        @empty
                                        <option value="">لا يوجد مستخدمين إداريين</option>
                                        @endforelse
                                    </select>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                    <i class="bx bx-save me-1"></i>
                                    <span>تحديث</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function updateDiscountFields() {
            const typeEl = document.getElementById('discount_type');
            const maxField = document.getElementById('maxDiscountField');
            const unit = document.getElementById('discountUnit');
            const value = document.getElementById('discount_value');

            if (!typeEl || !maxField || !unit || !value) return;

            const type = typeEl.value;

            if (type === 'percentage') {
                maxField.style.display = 'block';
                unit.textContent = '(%)';
                value.setAttribute('max', '100');
            } else if (type === 'fixed') {
                maxField.style.display = 'none';
                unit.textContent = '';
                value.removeAttribute('max');
            } else {
                maxField.style.display = 'none';
                unit.textContent = '';
            }
        }

        function generateCode() {
            if (typeof window.generateCouponCode === 'function') {
                return window.generateCouponCode();
            }

            const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
            let code = '';
            for (let i = 0; i < 8; i++) {
                code += chars.charAt(Math.floor(Math.random() * chars.length));
            }

            const input = document.getElementById('code');
            if (input) {
                input.value = code;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            const btn = document.getElementById('btn-generate-code');
            const icon = btn ? btn.querySelector('i') : null;
            if (icon) {
                icon.classList.add('ti-spin');
                setTimeout(() => icon.classList.remove('ti-spin'), 500);
            }

            return code;
        }

        document.addEventListener('DOMContentLoaded', () => {
            updateDiscountFields();
        });
    </script>
@endsection
