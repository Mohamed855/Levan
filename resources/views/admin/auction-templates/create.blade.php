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
                    <x-form.wizard.step id="step-files" :fields="['file', 'thumbnail', 'is_active']">
                        <div class="row g-4">
                            <x-form.controls.file name="file" id="file"
                                label="ملف القالب (PDF أو صورة تصميم عالية الدقة)"
                                accept=".pdf,application/pdf,image/png,image/jpeg,image/webp" required col="col-md-6"
                                help="يمكن رفع ملف PDF جاهز أو صورة تصميم عالية الدقة (PNG, JPG, WEBP). الحد الأقصى 30 ميجابايت." />

                            <x-form.controls.file name="thumbnail" id="thumbnail" label="صورة المعاينة (اختياري)"
                                accept="image/png,image/jpeg,image/webp" col="col-md-6"
                                help="تُعرض للمستأجرين في شاشة إنشاء وتعديل المزاد لتسهيل اختيار القالب (في حال تركه فارغاً سيتم اعتماده تلقائياً من التصميم المرفوع)." />

                            <div class="col-12">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-header bg-secondary py-2">
                                        <h6 class="mb-0 fw-bold">
                                            <i class="ti ti-list-check me-1 text-primary"></i>البيانات المطبوعة تلقائياً
                                            وفقاً لنوع القالب
                                        </h6>
                                    </div>
                                    <div class="card-body p-0">
                                        <div class="accordion accordion-flush" id="fieldGuideAccordion">
                                            @foreach ($fieldGuide as $key => $guide)
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="heading-{{ $key }}">
                                                        <button
                                                            class="accordion-button {{ $loop->first ? '' : 'collapsed' }} py-2"
                                                            type="button" data-bs-toggle="collapse"
                                                            data-bs-target="#collapse-{{ $key }}"
                                                            aria-expanded="{{ $loop->first ? 'true' : 'false' }}">
                                                            <span
                                                                class="badge bg-label-{{ $key === 'brochure' ? 'primary' : ($key === 'billboard' ? 'warning' : 'info') }} me-2">
                                                                {{ count($guide['fields']) }} حقل
                                                            </span>
                                                            {{ $guide['title'] }}
                                                        </button>
                                                    </h2>
                                                    <div id="collapse-{{ $key }}"
                                                        class="accordion-collapse collapse {{ $loop->first ? 'show' : '' }}"
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
                                                                        @foreach ($guide['fields'] as $field)
                                                                            <tr>
                                                                                <td><code>{{ $field['name'] }}</code></td>
                                                                                <td>
                                                                                    {{ $field['label'] }}
                                                                                    @if ($field['required'])
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
                                    <div><strong>صورة المعاينة:</strong> <span id="review-thumbnail-name">-</span></div>
                                    <div><strong>حالة التفعيل:</strong> <span id="review-status">-</span></div>
                                </x-form.wizard.review-card>

                                <x-form.wizard.review-card title="البيانات المعتمدة للتوليد التلقائي" step="2"
                                    class="assignment-review__card">
                                    <div id="review-field-guide" class="small text-muted">-</div>
                                </x-form.wizard.review-card>
                            </div>

                            <div class="col-lg-3">
                                <article class="card assignment-preview h-100">
                                    <div class="assignment-preview__image bg-primary d-flex flex-column justify-content-center p-4 text-white text-center position-relative overflow-hidden"
                                        style="min-height: 140px;">
                                        <img id="preview-template-img"
                                            class="position-absolute top-0 start-0 w-100 h-100 object-fit-cover d-none"
                                            alt="">
                                        <div id="preview-template-icon" class="position-relative z-1">
                                            <i class="ti ti-palette fs-1 mb-2 text-white"></i>
                                            <h5 class="text-white mb-1" id="preview-template-name">اسم القالب</h5>
                                            <small class="text-white-50" id="preview-template-type">نوع القالب</small>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <p class="mb-2" id="preview-template-status">
                                            <span class="badge bg-label-success"><i class="ti ti-check me-1"></i>مفعل
                                                ومتاح للمستأجرين</span>
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
                                <x-form.wizard.save-button text="حفظ وتفعيل القالب" />
                            </div>
                        </div>
                    </x-form.wizard.step>
                </form>
            </x-form.wizard.stepper>
        </div>
    </div>
@endsection
