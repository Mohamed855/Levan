@extends('tenant.layouts.layoutMaster')

@section('title', 'إضافة مستخدم جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'basic-info-validation', 'title' => 'المعلومات الأساسية'],
            ['id' => 'security-info-validation', 'title' => 'كلمة المرور والصورة'],
        ];
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

                <form id="wizard-validation-form" action="{{ tenant_route_name('users.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                    data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                    data-draft-resource-key="tenant.user-create">
                    @csrf

                    <x-form.wizard.step id="basic-info-validation" :fields="['name', 'employee_number', 'email', 'phone', 'role_id']">
                        @include('tenant.user-management.users.partials.steps.step1-basic-info', ['user' => null])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('users.index')" text="العودة" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    <x-form.wizard.step id="security-info-validation" :fields="['password', 'password_confirmation', 'image']">
                        @include('tenant.user-management.users.partials.steps.step2-security', ['user' => null])

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.save-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
