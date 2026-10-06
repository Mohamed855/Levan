@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل المستخدم')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'basic-info-validation', 'title' => 'المعلومات الأساسية', 'subtitle' => 'البيانات الشخصية والاتصال'],
            ['id' => 'security-info-validation', 'title' => 'كلمة المرور والصورة', 'subtitle' => 'الأمان والصورة الشخصية'],
        ];
        $currentRoleId = $user->roles->first()?->id;
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form id="wizard-validation-form" action="{{ tenant_route_name('users.update', $user) }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <x-form.wizard.step id="basic-info-validation" :fields="['name', 'employee_number', 'email', 'phone', 'role_id']">
                        <div class="row g-6">
                            <x-form.controls.input name="name" label="الاسم الكامل" :value="$user->name"
                                placeholder="ادخل الاسم الكامل" required />
                            <x-form.controls.input name="employee_number" label="الرقم الوظيفي" :value="$user->employee_number"
                                placeholder="ادخل الرقم الوظيفي" required style="text-transform: uppercase;" />
                            <x-form.controls.input name="email" type="email" label="البريد الإلكتروني" :value="$user->email"
                                placeholder="ادخل البريد الإلكتروني" autocomplete="off" required />
                            <x-form.controls.input name="phone" label="رقم الهاتف" :value="$user->phone"
                                placeholder="ادخل رقم الهاتف" inputmode="tel" required />
                            <x-form.controls.select name="role_id" label="الدور" :options="$roles" :value="$currentRoleId"
                                placeholder="اختر الدور" required />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.next-button />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="security-info-validation" :fields="['password', 'password_confirmation', 'image']">
                        <div class="row g-6">
                            <x-form.controls.password name="password" label="كلمة المرور"
                                placeholder="اتركه فارغاً لعدم التغيير" data-validation-dependents="password_confirmation" />
                            <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور"
                                placeholder="أعد إدخال كلمة المرور" />
                            <x-form.controls.image-upload name="image" label="الصورة الشخصية" :value="tasset($user->image)"
                                hint="JPG، PNG أو GIF. الحد الأقصى 2MB" />

                            <x-form.wizard.actions>
                                <x-form.wizard.previous-button />
                                <x-form.wizard.save-button text="تحديث" />
                            </x-form.wizard.actions>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
