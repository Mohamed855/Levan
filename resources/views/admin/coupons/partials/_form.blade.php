@php
    $is_edit = $is_edit ?? false;
    $steps = [['id' => 'coupon-info-validation', 'title' => $is_edit ? 'تعديل الكوبون' : 'معلومات الكوبون']];
    $discountTypeValue = old(
        'discount_type',
        $is_edit && isset($coupon)
            ? (is_object($coupon->discount_type) ? $coupon->discount_type->value : $coupon->discount_type)
            : ''
    );
    $expiryDateValue = $is_edit && isset($coupon) && $coupon->expiry_date ? $coupon->expiry_date->format('Y-m-d') : '';
    $isActiveValue = $is_edit && isset($coupon) ? ($coupon->is_active ? '1' : '0') : '1';
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="coupon-wizard" :steps="$steps">
            <x-ui.validation-errors />

            <form id="coupon-wizard-form"
                action="{{ $is_edit ? route('admin.coupons.update', $coupon->id) : route('admin.coupons.store') }}"
                method="POST"
                enctype="multipart/form-data"
                autocomplete="off"
                data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                @if(!$is_edit)
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-resource-key="admin.coupon-create"
                @endif
            >
                @csrf
                @if($is_edit)
                    @method('PUT')
                @endif

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
                        <x-form.controls.input name="name" label="اسم الكوبون"
                            :value="$is_edit ? $coupon->name : null"
                            placeholder="مثال: خصم اليوم الوطني"
                            required col="col-md-6" />

                        <!-- الكود -->
                        @if($is_edit)
                            <x-form.controls.input name="code" id="code" label="كود الكوبون"
                                help="كود الكوبون ثابت ولا يمكن تعديله بعد إطلاقه."
                                :value="$coupon->code" readonly required
                                col="col-md-6" style="text-transform: uppercase;" />
                        @else
                            <x-form.controls.input name="code" id="code" label="كود الكوبون"
                                help="سيتم تحويل الكود إلى أحرف كبيرة تلقائيًا"
                                :value="$generatedCode ?? ''" required col="col-md-6"
                                :input-group="true" style="text-transform: uppercase;">
                                <button type="button" class="btn btn-secondary btn-generate-code" id="btn-generate-code">
                                    <i class="ti ti-wand"></i> توليد
                                </button>
                            </x-form.controls.input>
                        @endif

                        <!-- نوع الخصم -->
                        <x-form.controls.select name="discount_type" id="discount_type" label="نوع الخصم" required
                            :value="$discountTypeValue"
                            col="col-md-6" :enum="\App\Enums\Admin\Coupons\CouponDiscountType::class" />

                        <!-- قيمة الخصم -->
                        <x-form.controls.input type="number" name="discount_value" id="discount_value"
                            label="قيمة الخصم"
                            :value="$is_edit ? $coupon->discount_value : null"
                            step="0.01" min="0" required col="col-md-6" />

                        <!-- الحد الأقصى -->
                        <x-form.controls.input type="number" name="max_discount" id="max_discount"
                            wrapper-id="maxDiscountField"
                            :wrapper-style="$discountTypeValue === 'percentage' ? '' : 'display:none;'"
                            label="الحد الأقصى للخصم"
                            help="ينطبق فقط على النسبة المئوية"
                            :value="$is_edit ? $coupon->max_discount : null"
                            step="0.01" min="0" col="col-md-6" />

                        <!-- تاريخ الانتهاء -->
                        <x-form.controls.input type="date" name="expiry_date" id="expiry_date" label="تاريخ الانتهاء"
                            :value="$is_edit ? $expiryDateValue : null"
                            min="{{ date('Y-m-d') }}" col="col-md-6" />

                        <!-- حد الاستخدام -->
                        <x-form.controls.input type="number" name="usage_limit" id="usage_limit" label="حد الاستخدام"
                            :value="$is_edit ? $coupon->usage_limit : null"
                            placeholder="اتركه فارغاً لاستخدام غير محدود" min="1" step="1"
                            col="col-md-6" />

                        <!-- الحالة -->
                        <x-form.controls.select name="is_active" id="is_active" label="حالة الكوبون" required
                            :value="$isActiveValue" col="col-md-6" :enum="\App\Enums\Shared\ActivationStatus::class" />

                        <!-- إسناد الكوبون -->
                        <x-form.controls.select name="admin_user_id" id="users" label="إسناد الكوبون"
                            :value="$is_edit ? $coupon->admin_user_id : null"
                            col="col-md-6" placeholder="اختر الإسناد" :options="$admin_users
                                ->map(fn($u) => ['value' => $u->id, 'label' => $u->name])
                                ->toArray()" />
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="route('admin.coupons.index')" />
                        <div class="d-flex gap-2">
                            @if(!$is_edit)
                                <x-form.wizard.draft-button step="1" />
                            @endif
                            <x-form.wizard.save-button />
                        </div>
                    </x-form.wizard.actions>
                </x-form.wizard.step>
            </form>
        </x-form.wizard.stepper>
    </div>
</div>
