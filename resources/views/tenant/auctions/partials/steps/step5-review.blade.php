@php
    $isEdit = isset($auction) && $auction->exists;
@endphp

<div class="row g-4 mb-4 assignment-review">
    <div class="col-lg-9 d-flex flex-column gap-4">
        <x-form.wizard.review-card title="البيانات الأساسية" step="1" class="assignment-review__card">
            <div><strong>اسم المزاد:</strong> <span id="review-name">{{ $isEdit ? $auction->name : '-' }}</span></div>
            <div><strong>النوع والتصنيف:</strong> 
                <span id="review-type">{{ $isEdit ? $auction->type_label : '-' }}</span> / 
                <span id="review-category">{{ $isEdit ? $auction->category_label : '-' }}</span>
            </div>
            <div><strong>المدينة:</strong> <span id="review-city">{{ $isEdit ? $auction->city : '-' }}</span></div>
            <div><strong>الفترة:</strong> <span id="review-dates">{{ $isEdit ? ($auction->start_date?->format('Y-m-d') . ' إلى ' . $auction->end_date?->format('Y-m-d')) : '-' }}</span></div>
            <div><strong>الوصف:</strong> <span id="review-description">{{ $isEdit ? ($auction->description ?: '-') : '-' }}</span></div>
            <div><strong>عدد العقارات المتوقع:</strong> <span id="review-expected-properties">{{ $isEdit ? $auction->expected_properties_count : '-' }}</span></div>
        </x-form.wizard.review-card>

        <x-form.wizard.review-card title="الإسناد والمنصة" step="2" class="assignment-review__card">
            <div><strong>الإسناد:</strong> <span id="review-assignment-name">{{ $isEdit ? ($auction->assignment?->name ?: '-') : '-' }}</span></div>
            <div><strong>الجهة المشرفة:</strong> <span id="review-supervising">{{ $isEdit ? ($auction->supervising_entity ?: '-') : '-' }}</span></div>
            <div><strong>مدير المشروع:</strong> <span id="review-project-manager">{{ $isEdit ? ($auction->project_manager_name ?: '-') : '-' }}</span></div>
            <div><strong>المنصة:</strong> <span id="review-platform">{{ $isEdit ? ($auction->platform_name ?: '-') : '-' }}</span></div>
        </x-form.wizard.review-card>

        <x-form.wizard.review-card title="العقارات المشمولة" step="3" class="assignment-review__card">
            <div><strong>العقارات المحددة:</strong> <span id="review-selected-props-count">{{ $isEdit ? $auction->properties->count() : 0 }} عقارات</span></div>
        </x-form.wizard.review-card>

        <x-form.wizard.review-card title="النتائج والملفات" step="4" class="assignment-review__card">
            <div><strong>صورة التقرير:</strong> <span id="review-report-image">{{ $isEdit ? ($auction->report_image ? 'محفوظة' : 'غير محددة (مطلوبة)') : 'غير محددة' }}</span></div>
            <div><strong>صورة الغلاف:</strong> <span id="review-cover-image">{{ $isEdit ? ($auction->cover_image ? 'محفوظة' : 'غير محددة') : 'غير محددة' }}</span></div>
            <div><strong>المرفقات:</strong> <span id="review-attachments-count">{{ $isEdit ? $auction->attachments->count() : 0 }} ملف</span></div>
        </x-form.wizard.review-card>
    </div>

    <div class="col-lg-3">
        <article class="card assignment-preview h-100">
            <div class="assignment-preview__image bg-primary d-flex flex-column justify-content-center p-4 text-white">
                <h5 class="text-white mb-1" id="preview-auction-name">{{ $isEdit ? $auction->name : 'اسم المزاد' }}</h5>
                <small class="text-white-50" id="preview-auction-category">{{ $isEdit ? $auction->category_label : 'التصنيف' }}</small>
            </div>
            <div class="card-body">
                <p class="mb-1" id="preview-auction-city">{{ $isEdit ? $auction->city : 'المدينة' }}</p>
                <p class="text-muted mb-0" id="preview-auction-date">{{ $isEdit && $auction->start_date ? $auction->start_date->format('Y-m-d') : 'تاريخ الانطلاق' }}</p>
            </div>
        </article>
    </div>
</div>
