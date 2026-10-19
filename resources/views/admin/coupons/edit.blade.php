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
                            <x-form.controls.input name="code" id="code" label="كود الكوبون"
                                help="سيتم تحويل الكود إلى أحرف كبيرة تلقائيًا"
                                :value="$coupon->code" required col="col-md-6" :input-group="true"
                                class="font-monospace" style="text-transform: uppercase;">
                                <button type="button" class="btn btn-secondary btn-generate-code"
                                    id="btn-generate-code">
                                    <i class="ti ti-wand"></i> توليد
                                </button>
                            </x-form.controls.input>

                            <!-- نوع الخصم -->
                            <x-form.controls.select name="discount_type" id="discount_type" label="نوع الخصم" required
                                :value="$discountTypeValue" col="col-md-6" :enum="\App\Enums\Admin\Coupons\CouponDiscountType::class" />

                            <!-- قيمة الخصم -->
                            <x-form.controls.input type="number" name="discount_value" id="discount_value"
                                label="قيمة الخصم" :value="$coupon->discount_value" step="0.01" min="0"
                                required col="col-md-6" />

                            <!-- الحد الأقصى -->
                            <x-form.controls.input type="number" name="max_discount" id="max_discount"
                                wrapper-id="maxDiscountField"
                                :wrapper-style="$discountTypeValue === 'percentage' ? '' : 'display:none;'"
                                label="الحد الأقصى للخصم" help="ينطبق فقط على النسبة المئوية"
                                :value="$coupon->max_discount" step="0.01" min="0" col="col-md-6" />

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
