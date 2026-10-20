@extends('tenant.layouts.layoutMaster')

@section('title', 'اعدادات النظام')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/css/shared/tab.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/js/tenant/settings/tab.js', 'resources/assets/js/tenant/settings/system-settings.js'])
@endsection
@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')



    <div class="row">
        @error('support_emails')
            <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        <div class="col-12 col-md-3 col-xl-3">
            <div>
                <ul class="nav nav-tabs justify-content-between border-bottom-0 flex-column">
                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold active m-0" data-bs-toggle="tab" href="#generalSettings">
                            <!-- أيقونة الإعدادات العامة -->
                            <i class="ti ti-settings ti-sm me-1_5"></i>
                            <span class="align-middle">الإعدادات العامة</span>
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#printSettings">
                            <!-- أيقونة إعدادات الطباعة -->
                            <i class="ti ti-printer ti-sm me-1_5"></i>
                            <span class="align-middle">الإعدادات الخاصة بالطباعة</span>
                        </a>
                    </li>

                    {{-- <li class="card rounded-0 mb-3 nav-item shadow-none">
                    <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#whatsapp">
                        <!-- أيقونة إعدادات WhatsApp -->
                        <i class="ti ti-brand-whatsapp ti-sm me-1_5"></i>
                        <span class="align-middle">إعدادات Whatsapp</span>
                    </a>
                </li> --}}
                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#SmsSettings">
                            <!-- أيقونة إعدادات SMS -->
                            <i class="ti ti-message ti-sm me-1_5"></i>
                            <span class="align-middle">إعدادات SMS</span>
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#twoFactorSettings">
                            <!-- أيقونة المصادقة الثنائية -->
                            <i class="ti ti-shield-lock ti-sm me-1_5"></i>
                            <span class="align-middle">المصادقة الثنائية</span>
                        </a>
                    </li>

                </ul>
            </div>
        </div>

        <div class="col-12 col-md-9 col-xl-9 order-1 order-md-0 mb-5">
            <div class="tab-content p-0">
                <!-- التبويبات الجانبية   -->
                @include('tenant.settings.partials.general-settings')
                @include('tenant.settings.partials.print-settings')
                {{-- @include('tenant.settings.partials.whatsapp-settings') --}}
                @include('tenant.settings.partials.sms-settings')
                @include('tenant.settings.partials.two-factor-settings')
            </div>
        </div>
    </div>


@endsection
