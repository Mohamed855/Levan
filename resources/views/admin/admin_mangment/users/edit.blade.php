@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل بيانات المستخدم — ' . $user->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
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
                'subtitle' => 'البيانات الشخصية والاتصال',
            ],
            [
                'id' => 'security-info-validation',
                'title' => 'كلمة المرور والصورة',
                'subtitle' => 'الأمان والصورة الشخصية',
            ],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 d-flex align-items-center">
                    <i class="ti ti-edit text-primary me-2" aria-hidden="true"></i>تعديل بيانات المستخدم — {{ $user->name }}
                </h5>
                <div class="d-flex gap-2">
                    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary">
                        <i class="ti ti-arrow-right me-1" aria-hidden="true"></i>رجوع
                    </a>
                </div>
            </div>

            <x-form.wizard.stepper id="user-edit-wizard" :steps="$steps">
                <x-ui.validation-errors class="mb-4" />

                <form id="user-edit-wizard-form" action="{{ route('admin.users.update', $user) }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}">
                    @csrf
                    @method('PUT')

                    <x-form.wizard.step id="basic-info-validation" :fields="['name', 'email', 'phone']">
                        <div class="row g-4">
                            <x-form.controls.input name="name" label="الاسم الكامل" :value="$user->name"
                                placeholder="ادخل الاسم الكامل" required col="col-sm-6" />
                            <x-form.controls.input name="email" type="email" label="البريد الإلكتروني" :value="$user->email"
                                placeholder="ادخل البريد الإلكتروني" autocomplete="off" required col="col-sm-6" />
                            <x-form.controls.input name="phone" label="رقم الهاتف" :value="$user->phone"
                                placeholder="05xxxxxxxx" maxlength="10" inputmode="numeric" required col="col-sm-6" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="route('admin.users.index')" />
                            <x-form.wizard.next-button />
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="security-info-validation" :fields="['password', 'password_confirmation', 'image']">
                        <div class="row g-4">
                            <x-form.controls.password name="password" label="كلمة المرور الجديدة"
                                placeholder="اتركه فارغاً إذا لم ترغب بالتعديل"
                                data-validation-dependents="password_confirmation" col="col-sm-6" />
                            <x-form.controls.password name="password_confirmation" label="تأكيد كلمة المرور الجديدة"
                                placeholder="أعد إدخال كلمة المرور" col="col-sm-6" />
                            <x-form.controls.image-upload name="image" label="الصورة الشخصية" :value="$user->image ? asset('storage/' . $user->image) : asset('img/logo.svg')"
                                col="col-12" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <x-form.wizard.save-button text="حفظ التعديلات" />
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
