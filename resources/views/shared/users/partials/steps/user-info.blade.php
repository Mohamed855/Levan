@php
    $is_profile = $is_profile ?? false;
    $isEdit = isset($user) && $user->exists;
    $currentRoleId = $isEdit ? $user->roles->first()?->id : null;
    $roles = $roles ?? collect();
    $resolvedImageUrl = $imageUrl ?? (
        $isEdit && !empty($user?->image)
            ? (function_exists('tenant') && tenant() ? tasset($user->image) : asset('storage/' . $user->image))
            : null
    );
@endphp

<div class="row g-4">
    @if (!$isEdit && !$is_profile)
        <div class="col-12">
            <div class="alert alert-primary d-flex align-items-center gap-2 mb-0" role="alert">
                <i class="ti ti-info-circle fs-4"></i>
                <span>سيتم توليد كلمة المرور تلقائياً وإرسالها إلى البريد الإلكتروني للمستخدم في الرسالة الترحيبية.</span>
            </div>
        </div>
    @endif

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

    <x-form.controls.file name="image" label="الصورة الشخصية" :value="$resolvedImageUrl"
        help="JPG، PNG أو GIF. الحد الأقصى 10MB" accept="image/*" col="col-12" :max-size="10" />
</div>
