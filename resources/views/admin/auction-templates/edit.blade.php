@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل قالب مزاد: ' . $template->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const typeSelect = document.getElementById('type');
            if (typeSelect) {
                typeSelect.addEventListener('change', function() {
                    const targetKey = this.value;
                    const targetCollapse = document.getElementById('collapse-' + targetKey);
                    if (targetCollapse && typeof bootstrap !== 'undefined') {
                        const bsCollapse = bootstrap.Collapse.getOrCreateInstance(targetCollapse);
                        bsCollapse.show();
                    }
                });
            }
        });
    </script>
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-edit me-2 text-primary"></i>تعديل قالب المزاد: {{ $template->name }}
            </h4>
            <span class="badge {{ $template->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                {{ $template->is_active ? 'مفعل ومتاح للمستأجرين' : 'معطل وغير متاح' }}
            </span>
        </div>
        <a href="{{ route('admin.auction-templates.show', $template) }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة لتفاصيل القالب
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <x-ui.validation-errors class="mb-4" />

            <form id="auction-template-edit-form" action="{{ route('admin.auction-templates.update', $template) }}"
                method="POST" enctype="multipart/form-data" novalidate data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    {{-- Name --}}
                    <x-form.controls.input name="name" id="name" label="اسم القالب" :value="$template->name" required
                        col="col-md-6" />

                    {{-- Type --}}
                    <x-form.controls.select name="type" id="type" label="نوع القالب" required :value="$template->type->value"
                        :options="$typeOptions" col="col-md-6" />

                    {{-- File Upload (PDF or High-Res Image) --}}
                    <x-form.controls.file name="file" id="file" label="استبدال ملف القالب (PDF أو صورة تصميم)"
                                accept=".pdf,application/pdf" col="col-12"
                        help="اتركه فارغاً للإبقاء على الملف الحالي. يمكنك رفع ملف PDF أو صورة بحد أقصى 30 ميجابايت.">
                        @if ($template->file_url)
                            @php
                                $isPdf = str_ends_with(strtolower($template->file_path), '.pdf');
                            @endphp
                            <div
                                class="mt-2 p-2 bg-secondary rounded d-flex align-items-center justify-content-between border">
                                <div class="d-flex align-items-center">
                                    <i
                                        class="ti {{ $isPdf ? 'ti-file-type-pdf text-danger' : 'ti-photo text-primary' }} fs-3 me-2"></i>
                                    <span class="text-heading small">الملف الحالي ({{ $isPdf ? 'PDF' : 'صورة' }})</span>
                                </div>
                                <a href="{{ $template->file_url }}" target="_blank" class="btn btn-xs btn-label-primary">
                                    <i class="ti ti-external-link me-1"></i>معاينة
                                </a>
                            </div>
                        @endif
                    </x-form.controls.file>

                    {{-- Description --}}
                    <x-form.controls.textarea name="description" id="description" label="وصف القالب وملاحظات التصميم"
                        :value="$template->description" rows="3" col="col-12" />

                    {{-- Extracted Fields Summary --}}
                    {{-- كارت المعايير والمواصفات المعتمدة لملف القالب --}}
                    <div class="col-12">
                        <div class="card border border-primary-subtle shadow-none bg-label-primary mb-0">
                            <div class="card-body p-3">
                                <div class="d-flex align-items-start gap-3">
                                    <div
                                        class="avatar avatar-md bg-primary text-white rounded d-flex align-items-center justify-content-center flex-shrink-0">
                                        <i class="ti ti-ruler-measure fs-3"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 fw-bold text-primary">المعايير المعتمدة لملف هذا القالب
                                            ({{ $template->type->label() }}):</h6>
                                        @if ($template->type->value === 'brochure')
                                            <p class="mb-0 text-heading small">
                                                <i class="ti ti-file-type-pdf text-danger me-1"></i>
                                                <strong>ملف مستند PDF</strong> بمقاس صفحات <strong>A4 رأسي (210×297
                                                    مم)</strong>، يُستخدم ككتيّب متعدد الصفحات للطباعة وعرض تفاصيل المزاد
                                                والعقارات المشمولة.
                                            </p>
                                        @elseif($template->type->value === 'social')
                                            <p class="mb-0 text-heading small">
                                                <i class="ti ti-brand-instagram text-info me-1"></i>
                                                <strong>صورة عالية الدقة أو PDF</strong> بنسبة أبعاد <strong>مربعة
                                                    1:1</strong> (المقاس الموصى به <strong>1080×1080 بكسل</strong>) مخصص
                                                للنشر الرقمي.
                                            </p>
                                        @else
                                            <p class="mb-0 text-heading small">
                                                <i class="ti ti-ad text-warning me-1"></i>
                                                <strong>صورة أو PDF</strong> بتنسيق <strong>عرضي عريض Landscape</strong>
                                                (بنسبة <strong>16:9</strong> بدقة 1920×1080 بكسل أو A4 أفقي 297×210 مم) مخصص
                                                للشاشات واللوحات الخارجية.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Status Switch --}}
                    <x-form.controls.checkbox name="is_active" id="is_active" label="القالب نشط ومتاح للمستأجرين"
                        :checked="(bool) $template->is_active" col="col-12" />

                    {{-- Action Buttons --}}
                    <div class="col-12 text-end mt-4">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-device-floppy me-1"></i> حفظ التعديلات
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
