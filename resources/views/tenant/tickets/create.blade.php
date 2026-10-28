@extends('tenant.layouts.layoutMaster')

@section('title', 'رفع تذكرة جديدة')

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
    @php
        $steps = [['id' => 'ticket-info-validation', 'title' => 'معلومات التذكرة']];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                @if ($errors->any())
                    <div class="alert alert-danger" role="alert">
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

                    <x-form.wizard.step id="ticket-info-validation" :fields="['title', 'ticketing_sections_id', 'description', 'attachments']">
                        <div class="row g-4">
                            <x-form.controls.input name="title" id="title" label="عنوان التذكرة"
                                placeholder="أدخل عنوان التذكرة" required col="col-md-6" />

                            <x-form.controls.select name="ticketing_sections_id" id="ticketing_sections_id"
                                label="تصنيف التذكرة" placeholder="اختر التصنيف" required select2 :options="$sections"
                                col="col-md-6" />

                            <x-form.controls.textarea name="description" id="description" label="وصف المشكلة" rows="5"
                                placeholder="اشرح تفاصيل المشكلة التي تواجهها..." required />

                            <x-form.controls.file name="attachments" id="attachments" label="المرفقات (اختياري)" multiple
                                accept="image/*,application/pdf,.doc,.docx,.txt"
                                help="يمكنك رفع عدة صور أو ملفات (حد أقصى 5 ملفات، كل ملف حتى 10MB)" :max-size="10" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('tickets.index')" text="العودة" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.save-button text="إرسال التذكرة" />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
