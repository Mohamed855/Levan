@extends('layouts.dashboard')
@section('title', 'إضافة عميل')
@section('dashboard-scope', 'admin')
@section('content')
<form method="POST" action="{{ route('admin.tenants.store') }}">
    @csrf
    @if(request('trial_request_id'))
        <input type="hidden" name="trial_request_id" value="{{ old('trial_request_id', request('trial_request_id')) }}">
    @endif
    <section class="card">
        <div class="card-body">
            <div class="row g-3">
                <x-shared.form.controls.input name="company_name" label="اسم الشركة" :value="old('company_name', request('company_name'))" required />
                <x-shared.form.controls.input name="commercial_number" label="السجل التجاري" required />
                <x-shared.form.controls.input name="tax_number" label="الرقم الضريبي" />
                <x-shared.form.controls.select name="city_id" label="المدينة" :options="$cities" />
                <x-shared.form.controls.input name="address" label="العنوان" />
                <x-shared.form.controls.input name="primary_contact_name" label="اسم المسؤول" :value="old('primary_contact_name', request('company_name'))" required />
                <x-shared.form.controls.input name="primary_contact_email" type="email" label="بريد المسؤول" :value="old('primary_contact_email', request('primary_contact_email'))" required />
                <x-shared.form.controls.input name="primary_contact_phone" label="جوال المسؤول" :value="old('primary_contact_phone', request('primary_contact_phone'))" required />
                <x-shared.form.controls.password name="password" label="كلمة المرور" required />
                <x-shared.form.controls.password name="password_confirmation" label="تأكيد كلمة المرور" required />
                <x-shared.form.controls.select name="plan_id" label="الباقة" :options="$plans" required />
                <x-shared.form.controls.input name="price" type="number" step="0.01" min="0" label="السعر النهائي" required />
                <x-shared.form.controls.select name="subscription_status" label="حالة الاشتراك" :options="['active'=>'نشط','pending'=>'معلق','trial'=>'تجريبي']" :value="old('subscription_status', request('trial_request_id') ? 'trial' : 'active')" required />
            </div>
        </div>
    </section>
    <div class="mt-4">
        <button class="btn btn-primary">إنشاء العميل</button>
    </div>
</form>
@endsection

