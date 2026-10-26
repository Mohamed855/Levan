@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل بيانات العميل — ' . ($client->company_name ?? $company->company_name))

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @php
        $target = $client ?? $company;
        $steps = [
            ['id' => 'client-details-step', 'title' => 'البيانات التجارية والأساسية'],
            ['id' => 'contact-settings-step', 'title' => 'بيانات المسؤول والإعدادات'],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="ti ti-edit text-primary me-2" aria-hidden="true"></i>تعديل بيانات العميل —
                    {{ $target->company_name }}
                </h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.clients.show', $target->id) }}" class="btn btn-sm btn-label-info">
                        <i class="ti ti-eye me-1" aria-hidden="true"></i>عرض التفاصيل
                    </a>
                    <a href="{{ route('admin.clients.index') }}" class="btn btn-sm btn-secondary">
                        <i class="ti ti-arrow-right me-1" aria-hidden="true"></i>رجوع
                    </a>
                </div>
            </div>

            <x-form.wizard.stepper id="client-edit-stepper" :steps="$steps">
                <x-ui.validation-errors />

                <form id="client-edit-form" action="{{ route('admin.clients.update', $target) }}" method="POST"
                    autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <!-- 1. البيانات التجارية الأساسية -->
                    <x-form.wizard.step id="client-details-step" :fields="['company_name', 'commercial_number', 'tax_number', 'city_id', 'address']">
                        <div class="row g-4">
                            <x-form.controls.input name="company_name" label="اسم العميل / الشركة" :value="$target->company_name"
                                maxlength="255" required col="col-md-6" />
                            <x-form.controls.input name="commercial_number" label="رقم السجل التجاري" :value="$target->commercial_number"
                                maxlength="10" inputmode="numeric" pattern="[0-9]*" data-digits-only readonly required
                                col="col-md-6" help="رقم السجل التجاري ثابت للشركة." />
                            <x-form.controls.input name="tax_number" label="الرقم الضريبي" :value="$target->tax_number" maxlength="50"
                                readonly col="col-md-6" help="الرقم الضريبي ثابت للشركة." />
                            <x-form.controls.select name="city_id" label="المدينة" :options="$cities" :value="$target->city_id"
                                placeholder="اختر المدينة" required col="col-md-6" />
                            <x-form.controls.textarea name="address" label="العنوان" :value="$target->address" rows="2"
                                maxlength="1000" required col="col-md-6" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.clients.index')" />
                            <x-form.wizard.next-button />
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <!-- 2. بيانات المسؤول والإعدادات -->
                    <x-form.wizard.step id="contact-settings-step" :fields="[
                        'primary_contact_name',
                        'primary_contact_email',
                        'primary_contact_phone',
                        'email_notifications_enabled',
                        'two_factor_enabled',
                        'external_integrations_enabled',
                    ]">
                        <div class="row g-4">
                            <x-form.controls.input name="primary_contact_name" label="اسم المسؤول" :value="$target->primary_contact_name"
                                maxlength="255" col="col-md-6" />
                            <x-form.controls.input name="primary_contact_email" type="email" label="البريد الإلكتروني"
                                :value="$target->primary_contact_email" maxlength="255" col="col-md-6" />
                            <x-form.controls.input name="primary_contact_phone" label="رقم الجوال" :value="$target->primary_contact_phone"
                                placeholder="05xxxxxxxx" maxlength="10" inputmode="numeric" pattern="[0-9]*"
                                col="col-md-6" />

                            <div class="col-12 mt-4">
                                <h6 class="fw-bold text-primary mb-0"><i class="ti ti-settings me-1"
                                        aria-hidden="true"></i>إعدادات العميل الخاصة</h6>
                            </div>
                            <x-form.controls.checkbox name="email_notifications_enabled" label="تنبيهات البريد"
                                :checked="$settings->email_notifications_enabled ?? true" col="col-md-6">إرسال إشعارات البريد.</x-form.controls.checkbox>
                            <x-form.controls.checkbox name="two_factor_enabled" label="التحقق الثنائي (2FA)"
                                :checked="$settings->two_factor_enabled ?? false" col="col-md-6">فرض 2FA على مستخدمي
                                العميل.</x-form.controls.checkbox>
                            <x-form.controls.checkbox name="external_integrations_enabled" label="التكاملات الخارجية"
                                :checked="$settings->external_integrations_enabled ?? false" col="col-md-6">السماح بربط API
                                والأنظمة.</x-form.controls.checkbox>
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <x-form.wizard.save-button />
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
