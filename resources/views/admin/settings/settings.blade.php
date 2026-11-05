@extends('admin.layouts.layoutMaster')

@section('title', 'إعدادات النظام')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <form id="formGeneralSettings" method="POST" action="{{ route('admin.settings.update') }}"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate>
                    @csrf
                    @method('PUT')

                    <div class="card-body pt-4">
                        <div class="row g-4">
                            <x-form.controls.file name="image" label="شعار الموقع / الشعار العام" :value="$settings?->image ? asset('storage/' . $settings->image) : null"
                                accept="image/png, image/jpeg, image/webp"
                                help="الملفات المسموح بها: JPG, PNG أو WebP. الأبعاد الموصى بها: 150px × 150px"
                                :max-size="10" col="col-12" />

                            <x-form.controls.input name="office_name" label="اسم الشركة" :value="old('office_name', $settings->office_name ?? '')"
                                placeholder="أدخل اسم الشركة" required col="col-md-6" />

                            <x-form.controls.input name="logo_text" label="النص بجانب الشعار" :value="old('logo_text', $settings->logo_text ?? '')"
                                placeholder="أدخل النص بجانب الشعار" maxlength="14" required col="col-md-6"
                                help="الحد الأقصى لعدد الأحرف: 14." />

                            <x-form.controls.input name="office_address" label="عنوان المكتب" :value="old('office_address', $settings->office_address ?? '')"
                                placeholder="أدخل عنوان المكتب" col="col-md-6" />

                            <x-form.controls.input name="commercial_register" label="السجل التجاري" :value="old('commercial_register', $settings->commercial_register ?? '')"
                                placeholder="أدخل رقم السجل التجاري" col="col-md-6" />

                            <x-form.controls.input name="tax_number" label="الرقم الضريبي" :value="old('tax_number', $settings->tax_number ?? '')"
                                placeholder="أدخل الرقم الضريبي" col="col-md-6" />

                            <x-form.controls.input name="phone_number" label="رقم الهاتف" :value="old('phone_number', $settings->phone_number ?? '')"
                                placeholder="أدخل رقم الهاتف" col="col-md-6" />

                            <x-form.controls.input name="email" type="email" label="البريد الإلكتروني" :value="old('email', $settings->email ?? '')"
                                placeholder="أدخل البريد الإلكتروني" col="col-md-6" />

                            <x-form.controls.input name="facebook_url" type="url" label="رابط Facebook"
                                :value="old('facebook_url', $settings->facebook_url ?? '')" placeholder="https://facebook.com/..." col="col-md-4" />

                            <x-form.controls.input name="x_url" type="url" label="رابط X" :value="old('x_url', $settings->x_url ?? '')"
                                placeholder="https://x.com/..." col="col-md-4" />

                            <x-form.controls.input name="linkedin_url" type="url" label="رابط LinkedIn"
                                :value="old('linkedin_url', $settings->linkedin_url ?? '')" placeholder="https://linkedin.com/..." col="col-md-4" />

                            <x-form.controls.input name="google_maps_embed_url" label="رابط Google Maps Embed"
                                :value="old('google_maps_embed_url', $settings->google_maps_embed_url ?? '')" placeholder="https://www.google.com/maps/embed?pb=..." col="col-12"
                                help="يمكنك إدخال رابط التضمين مباشرة أو كود iframe بالكامل من خرائط جوجل." />
                        </div>

                        <div class="mt-4 pt-3 d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i>حفظ التعديلات
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
