@php
    $is_profile = $is_profile ?? false;
    $isEdit = isset($user) && $user->exists;
    $currentRoleId = $isEdit ? $user->roles->first()?->id : null;
    $roles = $roles ?? collect();
@endphp

<div class="row g-4">
    <x-form.controls.input name="name" label="الاسم الكامل" :value="old('name', $user->name ?? '')" placeholder="ادخل الاسم الكامل"
        required col="col-md-6" />

    <x-form.controls.input name="email" type="email" label="البريد الإلكتروني" :value="old('email', $user->email ?? '')"
        placeholder="ادخل البريد الإلكتروني" autocomplete="off" required col="col-md-6" />

    <x-form.controls.input name="phone" label="رقم الهاتف" :value="old('phone', $user->phone ?? '')" placeholder="05xxxxxxxx"
        maxlength="10" inputmode="numeric" required col="col-md-6" />

    <x-form.controls.select name="role_id" label="الدور" :options="$roles" :value="$is_profile ? $currentRoleId : old('role_id', $currentRoleId)" placeholder="اختر الدور"
        :required="!$is_profile" :disabled="$is_profile" col="col-md-6" />
    @if ($is_profile)
        <input type="hidden" name="role_id" value="{{ $currentRoleId }}">
    @endif

    @if (!$is_profile)
        <x-form.controls.password name="password" :label="$is_edit ? 'كلمة المرور الجديدة' : 'كلمة المرور'" :placeholder="$is_edit ? 'اتركه فارغاً إذا لم ترغب بالتعديل' : 'ادخل كلمة المرور'"
            data-validation-dependents="password_confirmation" :required="!$is_edit" col="col-md-6" />

        <x-form.controls.password name="password_confirmation" :label="$is_edit ? 'تأكيد كلمة المرور الجديدة' : 'تأكيد كلمة المرور'" :placeholder="$is_edit ? 'أعد إدخال كلمة المرور' : 'أعد إدخال كلمة المرور'"
            :required="!$is_edit" col="col-md-6" />
    @endif

    <x-form.controls.file name="image" label="الصورة الشخصية" :value="!empty($user?->image) ? asset('storage/' . $user->image) : null"
        help="JPG، PNG أو GIF. الحد الأقصى 10MB" accept="image/*" col="col-12" :max-size="10" />
</div>

