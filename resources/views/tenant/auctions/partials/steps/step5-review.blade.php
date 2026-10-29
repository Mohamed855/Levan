@php
    $isEdit = isset($auction) && $auction->exists;
@endphp

<div class="row g-4 assignment-review">
    <div class="col-12 d-flex flex-column gap-4">
        <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
            <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
            <span>برجاء مراجعة بيانات المزاد والإسناد والعقارات والنتائج قبل الاعتماد النهائي.</span>
        </div>
        <div class="row g-3">
            <div class="col-lg-6">
                <x-form.wizard.review-card title="البيانات الأساسية والإسناد" step="1" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">اسم المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-name">{{ $isEdit ? $auction->name : '-' }}</dd>
                        <dt class="col-5 text-muted">الإسناد المرتبط</dt>
                        <dd class="col-7 fw-semibold" id="review-assignment-name">{{ $isEdit ? ($auction->assignment?->name ?: '-') : '-' }}</dd>
                        <dt class="col-5 text-muted">الجهة المشرفة</dt>
                        <dd class="col-7 fw-semibold" id="review-supervising">{{ $isEdit ? ($auction->supervising_entity ?: '-') : '-' }}</dd>
                        <dt class="col-5 text-muted">النوع والتصنيف</dt>
                        <dd class="col-7 fw-semibold"><span id="review-type">{{ $isEdit ? $auction->type_label : '-' }}</span> / <span id="review-category">{{ $isEdit ? $auction->category_label : '-' }}</span></dd>
                        <dt class="col-5 text-muted">المدينة</dt>
                        <dd class="col-7 fw-semibold" id="review-city">{{ $isEdit ? $auction->city : '-' }}</dd>
                        <dt class="col-5 text-muted">الفترة</dt>
                        <dd class="col-7 fw-semibold" id="review-dates">{{ $isEdit ? ($auction->start_date?->format('Y-m-d') . ' إلى ' . $auction->end_date?->format('Y-m-d')) : '-' }}</dd>
                        <dt class="col-5 text-muted">الوصف</dt>
                        <dd class="col-7 fw-semibold" id="review-description">{{ $isEdit ? ($auction->description ?: '-') : '-' }}</dd>
                        <dt class="col-5 text-muted">عدد العقارات المتوقع</dt>
                        <dd class="col-7 fw-semibold" id="review-expected-properties">{{ $isEdit ? $auction->expected_properties_count : '-' }}</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            <div class="col-lg-6">
                <x-form.wizard.review-card title="المنصة وإدارة المشروع" step="2" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">مدير المشروع</dt>
                        <dd class="col-7 fw-semibold" id="review-project-manager">{{ $isEdit ? ($auction->project_manager_name ?: '-') : '-' }}</dd>
                        <dt class="col-5 text-muted">منصة المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-platform">{{ $isEdit ? ($auction->platform_name ?: '-') : '-' }}</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            <div class="col-lg-6">
                <x-form.wizard.review-card title="العقارات المشمولة" step="3" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">العقارات المحددة</dt>
                        <dd class="col-7 fw-semibold" id="review-selected-props-count">{{ $isEdit ? $auction->properties->count() : 0 }} عقارات</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            <div class="col-lg-6">
                <x-form.wizard.review-card title="النتائج والملفات" step="4" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">صورة التقرير</dt>
                        <dd class="col-7 fw-semibold" id="review-report-image">{{ $isEdit && $auction->report_image ? 'محفوظة' : 'صورة المثال الأصلية' }}</dd>
                        <dt class="col-5 text-muted">صورة الغلاف</dt>
                        <dd class="col-7 fw-semibold" id="review-cover-image">{{ $isEdit ? ($auction->cover_image ? 'محفوظة' : 'غير محددة') : 'غير محددة' }}</dd>
                        <dt class="col-5 text-muted">المرفقات</dt>
                        <dd class="col-7 fw-semibold" id="review-attachments-count">{{ $isEdit ? $auction->attachments->count() : 0 }} ملف</dd>
                        <dt class="col-5 text-muted">الحملات والروابط</dt>
                        <dd class="col-7 fw-semibold" id="review-marketing-counts">—</dd>
                        <dt class="col-5 text-muted">مقدمة التقرير</dt>
                        <dd class="col-7 fw-semibold" id="review-report-introduction">—</dd>
                        <dt class="col-5 text-muted">تفاصيل النتائج</dt>
                        <dd class="col-7 fw-semibold" id="review-results-narrative">—</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>
        </div>
    </div>
</div>
