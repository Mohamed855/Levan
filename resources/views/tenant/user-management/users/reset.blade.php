@php
    $customizerHidden = 'customizer-hide';
    $pageConfigs = ['myLayout' => 'blank'];
@endphp

@extends('tenant.layouts.layoutMaster')

@section('title', 'إعادة تعيين كلمة المرور')

@section('page-style')
    @vite(['resources/assets/vendor/scss/pages/page-auth.scss'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <div class="container-xxl">
        <div class="authentication-wrapper authentication-basic container-p-y">
            <div class="authentication-inner py-6">
                <div class="card">
                    <div class="card-body">
                        <div class="app-brand justify-content-center mb-6">
                            <a href="{{ tenant_route_name('dashboard') }}" class="app-brand-link">
                                <x-partials.tenant-logo :height="20" withbg="fill: #fff;" />
                            </a>
                        </div>
                        <h4 class="mb-1">إعادة تعيين كلمة المرور</h4>
                        <p class="mb-6">أدخل كلمة المرور الحالية، ثم كلمة المرور الجديدة.</p>

                        <form method="POST" action="{{ tenant_route_name('users.password.update') }}" novalidate
                            data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                            @csrf
                            <x-form.controls.password name="current_password" label="كلمة المرور الحالية" required autocomplete="current-password" col="col-12 mb-4" />
                            <x-form.controls.password name="new_password" label="كلمة المرور الجديدة" required autocomplete="new-password" col="col-12 mb-4" />
                            <x-form.controls.password name="new_password_confirmation" label="تأكيد كلمة المرور الجديدة" required autocomplete="new-password" col="col-12 mb-4" />
                            <div class="d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary">إعادة تعيين كلمة المرور</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
