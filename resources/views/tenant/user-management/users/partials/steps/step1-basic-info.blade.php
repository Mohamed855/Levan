@php
    $isEdit = isset($user) && $user->exists;
    $currentRoleId = $isEdit ? $user->roles->first()?->id : null;
@endphp

<div class="row g-6">
    <x-form.controls.input 
        name="name" 
        label="الاسم الكامل" 
        :value="old('name', $user->name ?? '')"
        placeholder="ادخل الاسم الكامل" 
        required 
    />


    <x-form.controls.input 
        name="email" 
        type="email" 
        label="البريد الإلكتروني" 
        :value="old('email', $user->email ?? '')"
        placeholder="ادخل البريد الإلكتروني" 
        autocomplete="off" 
        required 
    />

    <x-form.controls.input 
        name="phone" 
        label="رقم الهاتف" 
        :value="old('phone', $user->phone ?? '')"
        placeholder="ادخل رقم الهاتف" 
        inputmode="tel" 
        required 
    />

    <x-form.controls.select 
        name="role_id" 
        label="الدور" 
        :options="$roles" 
        :value="old('role_id', $currentRoleId)"
        placeholder="اختر الدور" 
        required 
    />
</div>
