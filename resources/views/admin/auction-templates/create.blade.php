@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة قالب مزاد جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/scss/tenant/pages/assignment-create.scss'])
@endsection

@section('page-script')
    <script>
        window.auctionTemplateFieldGuides = @json($fieldGuide);
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/auction-templates/create.js'])
@endsection

@section('content')
    @php
        $steps = [
            ['id' => 'step-basic-info', 'title' => 'البيانات الأساسية'],
            ['id' => 'step-files', 'title' => 'ملفات القالب والإعدادات'],
            ['id' => 'step-review', 'title' => 'المراجعة والحفظ'],
        ];
    @endphp

    <div class="row">
        <div class="col-12 mb-6">
            <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
                <x-ui.validation-errors class="mb-4" />

                <form id="wizard-validation-form" action="{{ route('admin.auction-templates.store') }}" method="POST"
                    enctype="multipart/form-data" autocomplete="off" data-form-validate
                    data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-file-url="{{ route('admin.wizard-drafts.file.destroy') }}"
                    data-draft-resource-key="admin.auction-template-create" data-draft-auto-restore>
                    @csrf

                    {{-- الخطوة 1: البيانات الأساسية --}}
                    <x-form.wizard.step id="step-basic-info" :fields="['name', 'type', 'description']">
                        <div class="row g-4">
                            <x-form.controls.input name="name" id="name" label="اسم القالب"
                                placeholder="مثال: البروشور التسويقي الرسمي 2026" required col="col-md-6" />

                            <x-form.controls.select name="type" id="type" label="نوع القالب" required
                                :options="$typeOptions" col="col-md-6" placeholder="اختر نوع القالب..." />

                            <x-form.controls.textarea name="description" id="description"
                                label="وصف القالب وملاحظات التصميم" rows="3"
                                placeholder="ملاحظات حول أبعاد القالب أو الغرض منه..." col="col-12" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 2: ملفات القالب والإعدادات --}}
                    <x-form.wizard.step id="step-files" :fields="['file', 'is_active']">
                        <div class="row g-4">
                            <x-form.controls.file name="file" id="file"
                                label="ملف القالب (PDF أو صورة تصميم عالية الدقة)"
                                accept=".pdf,application/pdf,image/png,image/jpeg,image/webp" required col="col-12"
                                help="يمكن رفع ملف PDF جاهز أو صورة تصميم عالية الدقة (PNG, JPG, WEBP). الحد الأقصى 30 ميجابايت." />

                            {{-- كارت المعايير والمواصفات المعتمدة لملف القالب --}}
                            <div class="col-12" id="specifications-guide-wrapper">
                                <div class="card border border-primary-subtle shadow-none bg-label-primary mb-0">
                                    <div class="card-body p-3">
                                        <div class="d-flex align-items-start gap-3">
                                            <div
                                                class="avatar avatar-md bg-primary text-white rounded d-flex align-items-center justify-content-center flex-shrink-0">
                                                <i class="ti ti-ruler-measure fs-3"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold text-primary">المعايير المعتمدة للملف وفقاً لنوع
                                                    القالب المحدد:</h6>

                                                <div id="spec-guide-brochure" class="spec-guide-item d-none">
                                                    <p class="mb-1 text-heading small">
                                                        <i class="ti ti-file-type-pdf text-danger me-1"></i>
                                                        <strong>قالب البروشور:</strong> يجب رفع ملف مستند
                                                        <strong>PDF</strong>، بمقاس صفحات <strong>A4 رأسي (210×297
                                                            مم)</strong>. يُستخدم ككتيّب متعدد الصفحات للطباعة وعرض تفاصيل
                                                        المزاد والعقارات المشمولة.
                                                    </p>
                                                </div>

                                                <div id="spec-guide-social" class="spec-guide-item d-none">
                                                    <p class="mb-1 text-heading small">
                                                        <i class="ti ti-brand-instagram text-info me-1"></i>
                                                        <strong>قالب السوشيال ميديا:</strong> يجب أن يكون صورة عالية الدقة
                                                        (PNG, JPG, WEBP) أو PDF، بنسبة أبعاد <strong>مربعة 1:1</strong>
                                                        (المقاس الموصى به <strong>1080×1080 بكسل</strong>) مخصص للنشر
                                                        الرقمي.
                                                    </p>
                                                </div>

                                                <div id="spec-guide-billboard" class="spec-guide-item d-none">
                                                    <p class="mb-1 text-heading small">
                                                        <i class="ti ti-ad text-warning me-1"></i>
                                                        <strong>قالب اللوحة الإعلانية:</strong> يجب أن يكون بتنسيق
                                                        <strong>عرضي عريض Landscape</strong> (بنسبة <strong>16:9</strong>
                                                        بدقة 1920×1080 بكسل أو A4 أفقي 297×210 مم) مخصص للشاشات واللوحات
                                                        الخارجية.
                                                    </p>
                                                </div>

                                                <div id="spec-guide-default" class="spec-guide-item">
                                                    <p class="mb-0 text-muted small">يرجى اختيار نوع القالب في الخطوة الأولى
                                                        لعرض الأبعاد والمواصفات المعتمدة له بدقة.</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <x-form.controls.checkbox name="is_active" id="is_active"
                                label="تفعيل القالب فور الحفظ وإتاحته للمستأجرين" :checked="true" col="col-12" />
                        </div>

                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.next-button />
                            </div>
                        </x-form.wizard.actions>
                    </x-form.wizard.step>

                    {{-- الخطوة 3: المراجعة والحفظ --}}
                    <x-form.wizard.step id="step-review" :fields="['is_active']">
                        <div class="row g-4 mb-4 assignment-review">
                            <div class="col-lg-9 d-flex flex-column gap-4">
                                <x-form.wizard.review-card title="البيانات الأساسية" step="1"
                                    class="assignment-review__card">
                                    <div><strong>اسم القالب:</strong> <span id="review-name">-</span></div>
                                    <div><strong>نوع القالب:</strong> <span id="review-type">-</span></div>
                                    <div><strong>الوصف:</strong> <span id="review-description">-</span></div>
                                </x-form.wizard.review-card>

                                <x-form.wizard.review-card title="ملفات القالب والإعدادات" step="2"
                                    class="assignment-review__card">
                                    <div><strong>ملف القالب:</strong> <span id="review-file-name">-</span></div>
                                    <div><strong>حالة التفعيل:</strong> <span id="review-status">-</span></div>
                                </x-form.wizard.review-card>
                            </div>

                            <div class="col-lg-3">
                                <article class="card assignment-preview h-100">
                                    <div class="assignment-preview__image bg-primary d-flex flex-column justify-content-center p-4 text-white text-center position-relative overflow-hidden"
                                        style="min-height: 140px;">
                                        <div id="preview-template-icon" class="position-relative z-1">
                                            <i class="ti ti-palette fs-1 mb-2 text-white"></i>
                                            <h5 class="text-white mb-1" id="preview-template-name">اسم القالب</h5>
                                            <small class="text-white-50" id="preview-template-type">نوع القالب</small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2" id="preview-template-status">
                                            <span class="badge bg-label-success">مفعل ومتاح للمستأجرين</span>
                                        </p>
                                        <p class="text-muted mb-0 small" id="preview-template-desc">وصف القالب وملاحظات
                                            التصميم</p>
                                    </div>
                                </article>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <x-form.wizard.previous-button />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button />
                                <x-form.wizard.save-button />
                            </div>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
