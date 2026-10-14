@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل الكوبون')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/coupons/form-ui.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'coupon-info-validation', 'title' => 'تعديل الكوبون'],
        ];
        $discountTypeValue = is_object($coupon->discount_type) ? $coupon->discount_type->value : $coupon->discount_type;
        $expiryDateValue = $coupon->expiry_date ? $coupon->expiry_date->format('Y-m-d') : '';
        $isActiveValue = $coupon->is_active ? '1' : '0';
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="coupon-wizard" :steps="$steps">
                <x-ui.validation-errors class="mb-4" />

                <form id="coupon-wizard-form" action="{{ route('admin.coupons.update', $coupon->id) }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <x-form.wizard.step id="coupon-info-validation" :fields="[
                        'name',
                        'code',
                        'discount_type',
                        'discount_value',
                        'max_discount',
                        'expiry_date',
                        'usage_limit',
                        'is_active',
                        'admin_user_id',
                    ]">
                        <div class="row g-4">
                            <!-- الاسم -->
                            <x-form.controls.input name="name" label="اسم الكوبون" :value="$coupon->name"
                                placeholder="مثال: خصم اليوم الوطني" required col="col-md-6" />

                            <!-- الكود -->
                            <div class="col-md-6" data-form-field data-field-name="code">
                                <label for="code" class="form-label">كود الكوبون <span class="text-danger">*</span>
                                    <small class="text-muted">سيتم تحويل الكود إلى أحرف كبيرة تلقائيًا</small>
                                </label>
                                <div class="input-group">
                                    <input type="text" class="form-control font-monospace" id="code" name="code"
                                        value="{{ old('code', $coupon->code) }}" style="text-transform: uppercase;"
                                        data-form-control required>
                                    <button type="button" class="btn btn-secondary btn-generate-code"
                                        id="btn-generate-code">
                                        <i class="ti ti-wand"></i> توليد
                                    </button>
                                </div>
                                <div id="code-error" class="invalid-feedback d-block" data-form-error role="alert" hidden>
                                </div>
                            </div>

                            <!-- نوع الخصم -->
                            <x-form.controls.select name="discount_type" id="discount_type" label="نوع الخصم" required
                                :value="$discountTypeValue" col="col-md-6" :enum="\App\Enums\Admin\Coupons\CouponDiscountType::class" />

                            <!-- قيمة الخصم -->
                            <div class="col-md-6" data-form-field data-field-name="discount_value">
                                <label for="discount_value" class="form-label">قيمة الخصم <span id="discountUnit"></span>
                                    <span class="text-danger">*</span></label>
                                <input type="number" class="form-control" id="discount_value" name="discount_value"
                                    value="{{ old('discount_value', $coupon->discount_value) }}" step="0.01"
                                    min="0" data-form-control required>
                                <div id="discount_value-error" class="invalid-feedback d-block" data-form-error
                                    role="alert" hidden></div>
                            </div>

                            <!-- الحد الأقصى -->
                            <div class="col-md-6" id="maxDiscountField"
                                style="{{ $discountTypeValue === 'percentage' ? '' : 'display:none;' }}" data-form-field
                                data-field-name="max_discount">
                                <label for="max_discount" class="form-label">الحد الأقصى للخصم
                                    <small class="text-muted">ينطبق فقط على النسبة المئوية</small>
                                </label>
                                <input type="number" class="form-control" id="max_discount" name="max_discount"
                                    value="{{ old('max_discount', $coupon->max_discount) }}" step="0.01" min="0"
                                    data-form-control>
                                <div id="max_discount-error" class="invalid-feedback d-block" data-form-error role="alert"
                                    hidden></div>
                            </div>

                            <!-- تاريخ الانتهاء -->
                            <x-form.controls.input type="date" name="expiry_date" id="expiry_date" label="تاريخ الانتهاء"
                                :value="$expiryDateValue" min="{{ date('Y-m-d') }}" col="col-md-6" />

                            <!-- حد الاستخدام -->
                            <x-form.controls.input type="number" name="usage_limit" id="usage_limit" label="حد الاستخدام"
                                :value="$coupon->usage_limit" placeholder="اتركه فارغاً لاستخدام غير محدود" min="1"
                                step="1" col="col-md-6" />

                            <!-- الحالة -->
                            <x-form.controls.select name="is_active" id="is_active" label="حالة الكوبون" required
                                :value="$isActiveValue" col="col-md-6" :enum="\App\Enums\Shared\ActivationStatus::class" />

                            <!-- إسناد الكوبون -->
                            <x-form.controls.select name="admin_user_id" id="users" label="إسناد الكوبون"
                                :value="$coupon->admin_user_id" col="col-md-6" placeholder="اختر الإسناد" :options="$admin_users
                                    ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
                                    ->toArray()" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.coupons.index')" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.save-button text="تحديث الكوبون" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
