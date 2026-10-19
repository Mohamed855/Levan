@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة قالب مزاد جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <div class="row g-4">
        {{-- Form Column --}}
        <div class="col-lg-7 col-xl-8">
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="ti ti-file-upload me-2 text-primary"></i>بيانات قالب المزاد (PDF أو صورة تصميم)
                    </h5>
                    <a href="{{ route('admin.auction-templates.index') }}" class="btn btn-sm btn-label-secondary">
                        <i class="ti ti-arrow-right me-1"></i>العودة للقائمة
                    </a>
                </div>

                <div class="card-body">
                    <x-ui.validation-errors class="mb-4" />

                    <form id="auction-template-create-form" action="{{ route('admin.auction-templates.store') }}"
                        method="POST" enctype="multipart/form-data" autocomplete="off" data-form-validate
                        data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                        data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                        data-draft-resource-key="admin.auction-template-create">
                        @csrf

                        <div class="row g-3">
                            {{-- Name --}}
                            <x-form.controls.input name="name" id="name" label="اسم القالب"
                                placeholder="مثال: البروشور التسويقي الرسمي 2026" required col="col-md-7" />

                            {{-- Type --}}
                            <x-form.controls.select name="type" id="type" label="نوع القالب" required
                                :options="$typeOptions" col="col-md-5" />

                            {{-- File Upload --}}
                            <x-form.controls.file name="file" id="file" label="ملف القالب (PDF أو صورة تصميم عالية الدقة)"
                                accept=".pdf,application/pdf,image/png,image/jpeg,image/webp" required col="col-12"
                                help="يمكن رفع ملف PDF جاهز أو صورة تصميم عالية الدقة (PNG, JPG, WEBP). سيتم استخدامها تلقائياً كخلفية رسمية مع تركيب البيانات عليها بدقة 100%. الحد الأقصى 30 ميجابايت." />

                            {{-- Thumbnail Upload --}}
                            <x-form.controls.file name="thumbnail" id="thumbnail" label="صورة المعاينة (اختياري)"
                                accept="image/png,image/jpeg,image/webp" col="col-12"
                                help="تُعرض للمستأجرين في شاشة إنشاء وتعديل المزاد لتسهيل اختيار القالب (في حال رفع صورة سيتم اعتمادها تلقائياً كمعاينة إذا تُرك هذا الحقل فارغاً)." />

                            {{-- Description --}}
                            <x-form.controls.textarea name="description" id="description"
                                label="وصف القالب وملاحظات التصميم" rows="3"
                                placeholder="ملاحظات حول أبعاد القالب أو الغرض منه..." col="col-12" />

                            {{-- Status Switch --}}
                            <x-form.controls.checkbox name="is_active" id="is_active"
                                label="تفعيل القالب فور الحفظ وإتاحته للمستأجرين" :checked="true" col="col-12" />

                            {{-- Action Buttons: Draft and Save enclosed together inside d-flex gap-2 per Golden Pattern --}}
                            <div class="col-12 pt-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="{{ route('admin.auction-templates.index') }}" class="btn btn-label-secondary">
                                        <i class="ti ti-arrow-right me-1"></i>عودة
                                    </a>
                                    <div class="d-flex gap-2">
                                        <x-form.wizard.draft-button step="1" />
                                        <x-form.wizard.save-button text="حفظ وتفعيل القالب" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Guide Sidebar Column --}}
        <div class="col-lg-5 col-xl-4">
            {{-- Instructions Card --}}
            <div class="card mb-4 border-primary shadow-none">
                <div class="card-header bg-label-primary py-3">
                    <h6 class="mb-0 text-primary fw-bold">
                        <i class="ti ti-wand me-1"></i>نظام توليد القوالب التلقائي الذكي
                    </h6>
                </div>
                <div class="card-body pt-3">
                    <ol class="ps-3 mb-0 small text-primary">
                        <li class="mb-2"><strong>رفع مباشر دون تعقيد:</strong> ارفع أي ملف تصميم بصيغة <strong>PDF</strong> أو <strong>صورة عالية الدقة (PNG/JPG/WEBP)</strong> مباشرة دون الحاجة لأي تعديلات يدوية على الحقول في برامج خارجية.</li>
                        <li class="mb-2"><strong>طباعة تلقائية بدقة 100%:</strong> يدمج النظام تلقائياً بيانات المزاد، الخطوط العربية الأنيقة، صورة الغلاف، ورمز الاستجابة السريع (QR Code) التفاعلي على القالب.</li>
                        <li class="mb-2"><strong>الأبعاد الموصى بها:</strong>
                            <ul class="ps-3 mt-1 text-muted">
                                <li>البروشور: A4 طولي (210 × 297 مم).</li>
                                <li>اللوحة الإعلانية: A4 عرضي (297 × 210 مم).</li>
                                <li>منشور التواصل: مربع (210 × 210 مم).</li>
                            </ul>
                        </li>
                        <li>بمجرد حفظ القالب، سيتم تفعيله فوراً ليظهر في قوائم اختيار القوالب أثناء إنشاء وتعديل المزادات للمستأجرين.</li>
                    </ol>
                </div>
            </div>

            {{-- Dynamic Field Map Guide --}}
            <div class="card shadow-sm">
                <div class="card-header py-3">
                    <h6 class="mb-0 fw-bold">
                        <i class="ti ti-list-check me-1 text-primary"></i>البيانات التي تُطبع تلقائياً على القالب
                    </h6>
                </div>
                <div class="card-body p-0">
                    <div class="accordion accordion-flush" id="fieldGuideAccordion">
                        @foreach ($fieldGuide as $key => $guide)
                            <div class="accordion-item">
                                <h2 class="accordion-header" id="heading-{{ $key }}">
                                    <button class="accordion-button {{ $loop->first ? '' : 'collapsed' }} py-2"
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
    </div>
@endsection

@push('scripts')
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
@endpush
