@php
    $latest = $template->versions?->first();
    $schema = old('schema', $latest ? json_encode($latest->schema, JSON_UNESCAPED_UNICODE) : null);
    $isEdit = $template->exists;
    $typeValue = $isEdit
        ? ($template->type instanceof \BackedEnum
            ? $template->type->value
            : (string) $template->type)
        : old('type', 'brochure');
@endphp

<div class="row g-4" data-template-designer data-type="{{ $typeValue }}" data-schema="{{ e($schema) }}">
    <input type="hidden" name="schema" data-schema-input value="{{ $schema }}">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-body">
                <x-form.controls.input name="name" label="اسم القالب" :value="$template->name" required col="col-12" />
                @if ($isEdit)
                    <x-form.controls.input name="type_display" label="نوع القالب" :value="$template->type instanceof \BackedEnum
                        ? $template->type->label()
                        : \App\Enums\Admin\AuctionTemplates\AuctionTemplateType::tryFrom(
                                (string) $template->type,
                            )?->label() ?? (string) $template->type" disabled
                        col="col-12" />
                    <input type="hidden" name="type" id="type" value="{{ $typeValue }}">
                @else
                    <x-form.controls.select name="type" id="type" label="نوع القالب" :value="$typeValue"
                        :options="[
                            'brochure' => 'كتيب مزاد متعدد الصفحات (A4 رأسي)',
                            'billboard' => 'لوحة إعلانية (16:9 عريض)',
                            'social' => 'منشور تواصل اجتماعي (1:1 مربع)',
                        ]" required col="col-12" />
                @endif

                <x-form.controls.textarea name="description" label="وصف القالب" :value="$template->description" col="col-12"
                    class="mt-3" />
                <input type="hidden" name="is_active" value="0">
                <div class="form-check form-switch mt-2">
                    <input class="form-check-input" id="is_active" name="is_active" value="1" type="checkbox"
                        @checked(old('is_active', $template->is_active ?? true))>
                    <label class="form-check-label" for="is_active">القالب نشط</label>
                </div>

                {{-- كارت المعايير والمواصفات المعتمدة لملف القالب والتصميم --}}
                <div class="col-12 mt-3" id="specifications-guide-wrapper">
                    <div class="card border border-primary-subtle shadow-none bg-label-primary mb-0">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div
                                    class="avatar avatar-md bg-primary text-white rounded d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="ti ti-ruler-measure fs-3"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 fw-bold text-primary">المعايير المعتمدة لهذا القالب والتصميم:</h6>

                                    <div id="spec-guide-brochure"
                                        class="spec-guide-item {{ $typeValue === 'brochure' ? '' : 'd-none' }}">
                                        <p class="mb-1 text-heading small">
                                            <i class="ti ti-file-type-pdf text-danger me-1"></i>
                                            <strong>قالب البروشور:</strong> مقاس صفحات <strong>A4 رأسي (210×297
                                                مم)</strong> بدقة <strong>794×1123 بكسل</strong>. كتيب تسويقي متعدد
                                            الصفحات يُطبع أو يُعرض رقمياً ويشمل تفاصيل المزاد والعقارات المشمولة.
                                        </p>
                                        <span class="badge bg-primary text-white">A4 رأسي (متعدد الصفحات)</span>
                                    </div>

                                    <div id="spec-guide-billboard"
                                        class="spec-guide-item {{ $typeValue === 'billboard' ? '' : 'd-none' }}">
                                        <p class="mb-1 text-heading small">
                                            <i class="ti ti-ad text-warning me-1"></i>
                                            <strong>قالب اللوحة الإعلانية:</strong> تنسيق عرضي عريض <strong>Landscape
                                                بنسبة 16:9</strong> بدقة <strong>1200×628 بكسل</strong>. مخصص للشاشات
                                            واللوحات الإعلانية الخارجية (صفحة واحدة).
                                        </p>
                                        <span class="badge bg-warning text-dark">شاشة عريضة 16:9 (صفحة واحدة)</span>
                                    </div>

                                    <div id="spec-guide-social"
                                        class="spec-guide-item {{ $typeValue === 'social' ? '' : 'd-none' }}">
                                        <p class="mb-1 text-heading small">
                                            <i class="ti ti-brand-instagram text-info me-1"></i>
                                            <strong>قالب منصات التواصل:</strong> أبعاد <strong>مربعة 1:1</strong> بدقة
                                            <strong>1080×1080 بكسل</strong>. مخصص للنشر الرقمي والمشاركة عبر منصات
                                            التواصل (صفحة واحدة).
                                        </p>
                                        <span class="badge bg-success text-white">مربع 1:1 (صفحة واحدة)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12 mt-3" id="approved-fields-guide-wrapper">
                    <div class="card border shadow-none bg-secondary mb-3">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 fw-bold small text-heading">
                                    <i class="ti ti-list-check me-1 text-primary"></i>البيانات المعتمدة لهذا النوع
                                </h6>
                                <span class="badge bg-label-primary font-monospace" id="approved-fields-count"></span>
                            </div>
                            <p class="text-muted small mb-2">الحقول التي يدعمها هذا النوع وتُستبدل تلقائياً بقيم المزاد
                                (* إلزامي):</p>
                            <div id="approved-fields-badges" class="d-flex flex-wrap gap-1"></div>
                        </div>
                    </div>
                </div>
                <hr>
                <h6 class="mb-3">خصائص العنصر المحدد</h6>
                <div data-properties></div>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">مصمم القالب</h5>
                    <small class="text-muted">ارسم المحتوى وحدد مواضع البيانات والصور على كل صفحة من القالب.</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-label-info font-monospace" data-canvas-dims>794 × 1123 px (A4 رأسي)</span>
                    <div class="d-flex gap-2 flex-wrap" data-page-tabs></div>
                </div>
            </div>
            <div class="card-body">
                @include('admin.auction-designs.partials.designer')
                @error('schema')
                    <div class="text-danger small mt-2">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>
<div class="d-flex justify-content-between gap-2 mt-4">
    <a href="{{ route('admin.auction-templates.index') }}" class="btn btn-label-secondary">السابق</a>
    <div class="d-flex gap-2">
        @if (!$isEdit)
            <x-form.wizard.draft-button />
        @endif
        <button class="btn btn-primary" type="submit">{{ $isEdit ? 'حفظ التعديلات' : 'إنشاء القالب' }}</button>
    </div>
</div>

<div class="modal fade" id="designer-preview-modal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">معاينة القالب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" data-preview-body></div>
        </div>
    </div>
</div>
