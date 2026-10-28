@php
    $isEdit = isset($user) && $user->exists;
@endphp

<div class="row g-6">
    <x-form.controls.password name="password" label="كلمة المرور" :placeholder="$isEdit ? 'اتركه فارغاً لعدم التغيير' : 'ادخل كلمة المرور'"
        data-validation-dependents="password_confirmation" :required="!$isEdit" />

    <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور" placeholder="أعد إدخال كلمة المرور"
        :required="!$isEdit" />

    <x-form.controls.file name="image" label="الصورة الشخصية" :value="$isEdit && $user->image ? tasset($user->image) : null" accept="image/*"
        help="JPG، PNG أو GIF. الحد الأقصى 10MB" col="col-12" :max-size="10" />
</div>
