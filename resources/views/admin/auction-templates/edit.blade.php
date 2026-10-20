@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل قالب مزاد: ' . $template->name)

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const typeSelect = document.getElementById('type');
            if (typeSelect) {
                typeSelect.addEventListener('change', function () {
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

            <form id="auction-template-edit-form"
                action="{{ route('admin.auction-templates.update', $template) }}"
                method="POST"
                enctype="multipart/form-data"
                novalidate
                data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}">
                @csrf
                @method('PUT')

                <div class="row g-4">
                    {{-- Name --}}
                    <x-form.controls.input
                        name="name"
                        id="name"
                        label="اسم القالب"
                        :value="$template->name"
                        required
                        col="col-md-6"
                    />

                    {{-- Type --}}
                    <x-form.controls.select
                        name="type"
                        id="type"
                        label="نوع القالب"
                        required
                        :value="$template->type->value"
                        :options="$typeOptions"
                        col="col-md-6"
                    />

                    {{-- File Upload (PDF or High-Res Image) --}}
                    <x-form.controls.file
                        name="file"
                        id="file"
                        label="استبدال ملف القالب (PDF أو صورة تصميم)"
                        accept=".pdf,application/pdf,image/png,image/jpeg,image/webp"
                        col="col-md-6"
                        help="اتركه فارغاً للإبقاء على الملف الحالي. يمكنك رفع ملف PDF أو صورة بحد أقصى 30 ميجابايت."
                    >
                        @if($template->file_url)
                            @php
                                $isPdf = str_ends_with(strtolower($template->file_path), '.pdf');
                            @endphp
                            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between border">
                                <div class="d-flex align-items-center">
                                    <i class="ti {{ $isPdf ? 'ti-file-type-pdf text-danger' : 'ti-photo text-primary' }} fs-3 me-2"></i>
                                    <span class="text-heading small">الملف الحالي ({{ $isPdf ? 'PDF' : 'صورة' }})</span>
                                </div>
                                <a href="{{ $template->file_url }}" target="_blank" class="btn btn-xs btn-label-primary">
                                    <i class="ti ti-external-link me-1"></i>معاينة
                                </a>
                            </div>
                        @endif
                    </x-form.controls.file>

                    {{-- Thumbnail Upload --}}
                    <x-form.controls.file
                        name="thumbnail"
                        id="thumbnail"
                        label="استبدال صورة المعاينة (اختياري)"
                        accept="image/png,image/jpeg,image/webp"
                        col="col-md-6"
                        help="تُعرض للمستأجرين في شاشة إنشاء وتعديل المزاد لتسهيل اختيار القالب."
                    >
                        @if($template->thumbnail_url)
                            <div class="mt-2 d-flex align-items-center gap-2">
                                <img src="{{ $template->thumbnail_url }}" alt="{{ $template->name }}" class="rounded-circle border p-1" style="width: 50px; height: 50px; object-fit: cover;">
                                <span class="text-muted small">المعاينة الحالية</span>
                            </div>
                        @endif
                    </x-form.controls.file>

                    {{-- Description --}}
                    <x-form.controls.textarea
                        name="description"
                        id="description"
                        label="وصف القالب وملاحظات التصميم"
                        :value="$template->description"
                        rows="3"
                        col="col-12"
                    />

                    {{-- Extracted Fields Summary --}}
                    @if(!empty($template->field_names))
                        <div class="col-12">
                            <label class="form-label text-primary fw-bold">البيانات المعتمدة للتوليد في هذا القالب ({{ count($template->field_names) }} بيان):</label>
                            <div class="p-3 border rounded bg-light d-flex flex-wrap gap-1">
                                @foreach($template->field_names as $fname)
                                    <span class="badge bg-label-dark font-monospace">{{ $fname }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Field Guide Accordion Full-width --}}
                    <div class="col-12">
                        <div class="card border shadow-none mb-0">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0 fw-bold">
                                    <i class="ti ti-list-check me-1 text-primary"></i>دليل البيانات المطبوعة تلقائياً وفقاً لنوع القالب
                                </h6>
                            </div>
                            <div class="card-body p-0">
                                <div class="accordion accordion-flush" id="fieldGuideAccordion">
                                    @foreach($fieldGuide as $key => $guide)
                                        <div class="accordion-item">
                                            <h2 class="accordion-header" id="heading-{{ $key }}">
                                                <button class="accordion-button {{ $template->type->value === $key ? '' : 'collapsed' }} py-2" type="button"
                                                    data-bs-toggle="collapse" data-bs-target="#collapse-{{ $key }}"
                                                    aria-expanded="{{ $template->type->value === $key ? 'true' : 'false' }}">
                                                    <span class="badge bg-label-{{ $key === 'brochure' ? 'primary' : ($key === 'billboard' ? 'warning' : 'info') }} me-2">
                                                        {{ count($guide['fields']) }} بيان
                                                    </span>
                                                    {{ $guide['title'] }}
                                                </button>
                                            </h2>
                                            <div id="collapse-{{ $key }}" class="accordion-collapse collapse {{ $template->type->value === $key ? 'show' : '' }}"
                                                data-bs-parent="#fieldGuideAccordion">
                                                <div class="accordion-body p-2">
                                                    <div class="table-responsive">
                                                        <table class="table table-sm table-striped mb-0 small">
                                                            <thead>
                                                                <tr>
                                                                    <th>اسم الحقل البرمجي</th>
                                                                    <th>البيان المطبوع تلقائياً</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach($guide['fields'] as $field)
                                                                    <tr>
                                                                        <td><code>{{ $field['name'] }}</code></td>
                                                                        <td>
                                                                            {{ $field['label'] }}
                                                                            @if($field['required'])
                                                                                <span class="text-danger">*</span>
                                                                            @endif
                                                                        </td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Status Switch --}}
                    <x-form.controls.checkbox
                        name="is_active"
                        id="is_active"
                        label="القالب نشط ومتاح للمستأجرين"
                        :checked="(bool) $template->is_active"
                        col="col-12"
                    />

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
