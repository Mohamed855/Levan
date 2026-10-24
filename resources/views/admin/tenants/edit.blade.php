@extends('layouts.dashboard')
@section('title', 'تعديل عميل — ' . $tenant->company_name)
@section('dashboard-scope', 'admin')
@section('content')
<form method="POST" action="{{ route('admin.tenants.update', $tenant) }}">
    @csrf
    @method('PUT')
    <section class="card">
        <div class="card-header"><h5 class="mb-0">بيانات المنشأة والمسؤول</h5></div>
        <div class="card-body"><div class="row g-3">
            <x-shared.form.controls.input name="company_name" label="اسم الشركة" :value="old('company_name', $tenant->company_name)" required />
            <x-shared.form.controls.input name="commercial_number" label="السجل التجاري" :value="old('commercial_number', $tenant->commercial_number)" required />
            <x-shared.form.controls.input name="tax_number" label="الرقم الضريبي" :value="old('tax_number', $tenant->tax_number)" />
            <x-shared.form.controls.select name="city_id" label="المدينة" :options="$cities" :value="old('city_id', $tenant->city_id)" />
            <x-shared.form.controls.input name="address" label="العنوان" :value="old('address', $tenant->address)" />
            <x-shared.form.controls.input name="primary_contact_name" label="اسم المسؤول" :value="old('primary_contact_name', $tenant->primary_contact_name)" required />
            <x-shared.form.controls.input name="primary_contact_email" type="email" label="بريد المسؤول" :value="old('primary_contact_email', $tenant->primary_contact_email)" required />
            <x-shared.form.controls.input name="primary_contact_phone" label="جوال المسؤول" :value="old('primary_contact_phone', $tenant->primary_contact_phone)" required />
            <div class="col-12"><input type="hidden" name="force_two_factor_auth" value="0"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="force_two_factor_auth" id="force_two_factor_auth" value="1" @checked(old('force_two_factor_auth', $forceTwoFactorAuth))><label class="form-check-label" for="force_two_factor_auth">فرض التحقق بخطوتين على مستخدمي المنشأة</label></div><small class="text-muted">يُطبق هذا الإعداد فعليًا في مسار تسجيل دخول المستأجر.</small></div>
        </div></div>
    </section>
    <div class="mt-4 d-flex gap-2"><button class="btn btn-primary">حفظ التعديلات</button><a class="btn btn-label-secondary" href="{{ route('admin.tenants.show', $tenant) }}">إلغاء</a></div>
</form>
@endsection
