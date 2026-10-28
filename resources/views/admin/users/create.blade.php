@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة مستخدم جديد')

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
        $steps = [
            [
                'id' => 'user-info-validation',
                'title' => 'بيانات المستخدم',
            ],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="user-wizard" :steps="$steps">
                <x-ui.validation-errors />

                <form id="user-wizard-form" action="{{ route('admin.users.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}" data-draft-resource-key="admin.user-create">
                    @csrf

                    <x-form.wizard.step id="user-info-validation" :fields="['name', 'email', 'phone', 'password', 'password_confirmation', 'image']">
                        <div class="row g-4">
                            <x-form.controls.input name="name" label="الاسم الكامل" placeholder="ادخل الاسم الكامل"
                                required col="col-md-6" />
                            <x-form.controls.input name="email" type="email" label="البريد الإلكتروني"
                                placeholder="ادخل البريد الإلكتروني" autocomplete="off" required col="col-md-6" />
                            <x-form.controls.input name="phone" label="رقم الهاتف" placeholder="05xxxxxxxx" maxlength="10"
                                inputmode="numeric" required col="col-md-6" />
                            <x-form.controls.file name="image" label="الصورة الشخصية" accept="image/*" col="col-md-6" />
                            <x-form.controls.password name="password" label="كلمة المرور" placeholder="ادخل كلمة المرور"
                                data-validation-dependents="password_confirmation" required col="col-md-6" />
                            <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور"
                                placeholder="أعد إدخال كلمة المرور" required col="col-md-6" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.users.index')" text="العودة" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button step="1" />
                                <x-form.wizard.save-button text="إضافة المستخدم" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
