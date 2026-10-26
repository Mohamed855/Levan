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
        name="employee_number" 
        label="الرقم الوظيفي" 
        :value="old('employee_number', $isEdit ? $user->employee_number : ($generatedEmployeeNumber ?? ''))"
        placeholder="ادخل الرقم الوظيفي" 
        :readonly="$isEdit" 
        required 
        style="text-transform: uppercase;"
        :help="$isEdit ? 'الرقم الوظيفي ثابت لا يمكن تعديله.' : 'الرقم الوظيفي المميز للموظف'"
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
