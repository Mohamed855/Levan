@extends('admin.layouts.layoutMaster')

@section('title', 'اعدادات النظام')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/css/tenants/tab.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/js/tenants/settings/tab.js', 'resources/assets/js/tenants/settings/system-settings.js'])
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            if (window.jQuery) {
                const $ = window.jQuery;

                $('.select2').each(function() {
                    const $this = $(this);
                    $this.wrap('<div class="position-relative"></div>');
                    $this.select2({
                        placeholder: $this.data('placeholder') || 'اختر خيارًا',
                        dropdownParent: $this.parent(),
                        allowClear: true,
                        width: '100%',
                        language: 'ar',
                        dir: 'rtl'
                    });
                });
            }
        });
    </script>
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
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
                            <i class="ti ti-settings ti-sm me-1_5"></i>
                            <span class="align-middle">الإعدادات العامة</span>
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#etmamSettings">
                            <!-- أيقونة إعدادات SMS -->
                            <i class="ti ti-key ti-sm me-1_5"></i>
                            <span class="align-middle">إعدادات ETMAM API</span>
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#microsoft">
                            <!-- أيقونة إعدادات Microsoft Graph -->
                            <i class="ti ti-brand-windows ti-sm me-1_5"></i>
                            <span class="align-middle">إعدادات Microsoft Graph</span>
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
                @include('admin.settings.partials.general-settings')
                @include('admin.settings.partials.etmam-settings')
                @include('admin.settings.partials.microsoft_settings')
                @include('admin.settings.partials.two-factor-settings')

            </div>
        </div>
    </div>


@endsection
