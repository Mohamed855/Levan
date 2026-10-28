@php
    $isEdit = isset($user) && $user->exists;
    $currentRoleId = $isEdit ? $user->roles->first()?->id : null;
@endphp

<div class="row g-4">
    <x-form.controls.input name="name" label="الاسم الكامل" :value="old('name', $user->name ?? '')" placeholder="ادخل الاسم الكامل"
        required col="col-md-6" />

    <x-form.controls.input name="email" type="email" label="البريد الإلكتروني" :value="old('email', $user->email ?? '')"
        placeholder="ادخل البريد الإلكتروني" autocomplete="off" required col="col-md-6" />

    <x-form.controls.input name="phone" label="رقم الهاتف" :value="old('phone', $user->phone ?? '')" placeholder="05xxxxxxxx" inputmode="tel"
        required col="col-md-6" />

    <x-form.controls.select name="role_id" label="الدور" :options="$roles" :value="old('role_id', $currentRoleId)" placeholder="اختر الدور"
        required col="col-md-6" />

    <x-form.controls.password name="password" label="كلمة المرور" :placeholder="$isEdit ? 'اتركه فارغاً لعدم التغيير' : 'ادخل كلمة المرور'"
        data-validation-dependents="password_confirmation" :required="!$isEdit" col="col-md-6" />

    <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور" placeholder="أعد إدخال كلمة المرور"
        :required="!$isEdit" col="col-md-6" />

    <x-form.controls.file name="image" label="الصورة الشخصية" :value="$isEdit && $user->image ? tasset($user->image) : null" accept="image/*"
        help="JPG، PNG أو GIF. الحد الأقصى 10MB" col="col-12" :max-size="10" />
</div>

