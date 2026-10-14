@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة مستخدم جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    @php
        $steps = [
            [
                'id' => 'basic-info-validation',
                'title' => 'المعلومات الأساسية',
            ],
            [
                'id' => 'security-info-validation',
                'title' => 'كلمة المرور والصورة',
            ],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="user-wizard" :steps="$steps">
                <x-ui.validation-errors class="mb-4" />

                <form id="user-wizard-form" action="{{ route('admin.users.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}">
                    @csrf

                    <x-form.wizard.step id="basic-info-validation" :fields="['name', 'email', 'phone']">
                        <div class="row g-4">
                            <x-form.controls.input name="name" label="الاسم الكامل" placeholder="ادخل الاسم الكامل"
                                required col="col-sm-6" />
                            <x-form.controls.input name="email" type="email" label="البريد الإلكتروني"
                                placeholder="ادخل البريد الإلكتروني" autocomplete="off" required col="col-sm-6" />
                            <x-form.controls.input name="phone" label="رقم الهاتف" placeholder="05xxxxxxxx" maxlength="10"
                                inputmode="numeric" required col="col-sm-6" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.users.index')" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button step="1" />
                                <x-form.wizard.next-button :disabled="true" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="security-info-validation" :fields="['password', 'password_confirmation', 'image']">
                        <div class="row g-4">
                            <x-form.controls.password name="password" label="كلمة المرور" placeholder="ادخل كلمة المرور"
                                data-validation-dependents="password_confirmation" required col="col-sm-6" />
                            <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور"
                                placeholder="أعد إدخال كلمة المرور" required col="col-sm-6" />
                            <x-form.controls.image-upload name="image" label="الصورة الشخصية" col="col-12" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button step="2" />
                                <x-form.wizard.save-button text="حفظ المستخدم" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
