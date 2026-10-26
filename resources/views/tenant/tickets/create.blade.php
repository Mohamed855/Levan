@extends('tenant.layouts.layoutMaster')

@section('title', 'رفع تذكرة ')

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
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/tickets/form-validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">معلومات التذكرة</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ tenant_route_name('tickets.store') }}" method="POST"
                        enctype="multipart/form-data" novalidate data-form-validate
                        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                        data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                        data-draft-resource-key="tenant.ticket-create">
                        @csrf

                        <div id="info-validation" class="content">
                            <div class="row g-4">
                                <x-form.controls.input name="title" id="title" label="عنوان التذكرة"
                                    placeholder="أدخل عنوان التذكرة" required col="col-md-6" />

                                <x-form.controls.select name="ticketing_sections_id" id="ticketing_sections_id"
                                    label="تصنيف التذكرة" placeholder="اختر التصنيف" required select2 col="col-md-6">
                                    <option value=""> </option>
                                    @foreach ($sections as $section)
                                        <option value="{{ $section['id'] ?? '' }}"
                                            {{ old('ticketing_sections_id') == ($section['id'] ?? '') ? 'selected' : '' }}>
                                            {{ $section['name'] ?? '' }}</option>
                                    @endforeach
                                </x-form.controls.select>

                                <x-form.controls.textarea name="description" id="description" label="وصف المشكلة"
                                    rows="5" placeholder="اشرح تفاصيل المشكلة التي تواجهها..." required
                                    col="col-md-6" />

                                <x-form.controls.file name="attachments" id="attachments" label="المرفقات (اختياري)"
                                    multiple accept="image/*,application/pdf,.doc,.docx,.txt"
                                    help="يمكنك رفع عدة صور أو ملفات (حد أقصى 5 ملفات، كل ملف حتى 10MB)"
                                    :max-size="10" col="col-md-6" />
                            </div>

                            <div class="col-12 d-flex justify-content-between mt-5">
                                <x-form.wizard.previous-button :href="tenant_route_name('tickets.index')" text="العودة" />
                                <div class="d-flex gap-2">
                                    <x-form.wizard.draft-button />
                                    <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                        <i class="bx bx-save me-1"></i>
                                        <span>حفظ </span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endsection
