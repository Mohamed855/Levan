@php
    $isEdit = isset($auction) && $auction->exists;
    $marketing = old('marketing', $isEdit ? ($auction->marketing_data ?? []) : []);
@endphp

<div class="row g-4 assignment-review">
    <div class="col-12 d-flex flex-column gap-4">
        <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
            <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
            <span>برجاء مراجعة بيانات المزاد والإسناد والعقارات والتقارير والمرفقات قبل الاعتماد النهائي.</span>
        </div>

        <div class="row g-3">
            {{-- 1. البيانات الأساسية والإسناد --}}
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

                        <dt class="col-5 text-muted">المدينة الرئيسية</dt>
                        <dd class="col-7 fw-semibold" id="review-city">{{ $isEdit ? $auction->city : '-' }}</dd>

                        <dt class="col-5 text-muted">بداية المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-start-datetime">{{ $isEdit && $auction->start_date ? ($auction->start_date->format('Y-m-d') . ' ' . ($auction->start_time ?: '')) : '-' }}</dd>

                        <dt class="col-5 text-muted">نهاية المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-end-datetime">{{ $isEdit && $auction->end_date ? ($auction->end_date->format('Y-m-d') . ' ' . ($auction->end_time ?: '')) : '-' }}</dd>

                        <dt class="col-5 text-muted">عدد العقارات المتوقع</dt>
                        <dd class="col-7 fw-semibold" id="review-expected-properties">{{ $isEdit ? $auction->expected_properties_count : '-' }}</dd>

                        <dt class="col-5 text-muted">الوصف</dt>
                        <dd class="col-7 fw-semibold text-break" id="review-description">{{ $isEdit ? ($auction->description ?: '-') : '-' }}</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            {{-- 2. المنصة وإدارة المشروع --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="المنصة وإدارة المشروع" step="2" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">مدير المشروع</dt>
                        <dd class="col-7 fw-semibold" id="review-project-manager">{{ $isEdit ? ($auction->project_manager_name ?: '-') : '-' }}</dd>

                        <dt class="col-5 text-muted">منصة المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-platform">{{ $isEdit ? ($auction->platform_name ?: '-') : '-' }}</dd>

                        <dt class="col-5 text-muted">رابط المنصة</dt>
                        <dd class="col-7 fw-semibold text-truncate" id="review-platform-url">{{ $isEdit && $auction->platform_url ? $auction->platform_url : '-' }}</dd>

                        <dt class="col-5 text-muted">ملاحظات المنصة</dt>
                        <dd class="col-7 fw-semibold text-break" id="review-notes">{{ $isEdit ? ($auction->notes ?: '-') : '-' }}</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            {{-- 3. العقارات المحددة --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="العقارات المحددة" step="3" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">عدد العقارات المحددة</dt>
                        <dd class="col-7 fw-semibold" id="review-selected-props-count">{{ $isEdit ? $auction->properties->count() : 0 }} عقار</dd>

                        <dt class="col-5 text-muted">إجمالي المساحة</dt>
                        <dd class="col-7 fw-semibold" id="review-selected-props-area">{{ $isEdit ? number_format((float) $auction->properties->sum('area')) . ' م²' : '0 م²' }}</dd>

                        <dt class="col-5 text-muted">إجمالي القيمة التقديرية</dt>
                        <dd class="col-7 fw-semibold" id="review-selected-props-val">{{ $isEdit ? number_format((float) $auction->properties->sum('estimated_value')) . ' ر.س' : '0 ر.س' }}</dd>

                        <dt class="col-5 text-muted">قائمة العقارات</dt>
                        <dd class="col-7 fw-semibold" id="review-selected-props-names">
                            @if ($isEdit && $auction->properties->isNotEmpty())
                                {{ $auction->properties->pluck('name')->join('، ') }}
                            @else
                                -
                            @endif
                        </dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            {{-- 4. بيانات التقرير --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="بيانات التقرير" step="4" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">مقدمة التقرير</dt>
                        <dd class="col-7 fw-semibold text-break" id="review-report-introduction">{{ !empty($marketing['introduction']) ? $marketing['introduction'] : '—' }}</dd>

                    </dl>
                </x-form.wizard.review-card>
            </div>

            {{-- 5. الملفات والمستندات --}}
            <div class="col-12">
                <x-form.wizard.review-card title="الملفات والمستندات" step="4" col="" class="h-100 assignment-review__card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <section class="assignment-selection" aria-labelledby="review-covers-documents-title">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 id="review-covers-documents-title" class="mb-0">أغلفة المزاد والتقرير (غلاف التقرير وغلاف المزاد)</h6>
                                    <span class="badge bg-primary">
                                        <span id="review-covers-documents-count">{{ ($isEdit && ($auction->report_image || $auction->cover_image)) ? (($auction->report_image ? 1 : 0) + ($auction->cover_image ? 1 : 0)) : 0 }}</span> صورة
                                    </span>
                                </div>
                                <div id="review-covers-documents" class="assignment-file-list">
                                    @if ($isEdit && ($auction->report_image || $auction->cover_image))
                                        @if ($auction->report_image)
                                            <x-ui.file-card :url="tasset($auction->report_image)" name="غلاف التقرير" meta="صورة غلاف التقرير" />
                                        @endif
                                        @if ($auction->cover_image)
                                            <x-ui.file-card :url="tasset($auction->cover_image)" name="غلاف المزاد" meta="صورة غلاف المزاد" />
                                        @endif
                                    @else
                                        <p class="text-muted mb-0" data-empty>لم يتم إرفاق أغلفة بعد.</p>
                                    @endif
                                </div>
                            </section>
                        </div>

                        <div class="col-md-6">
                            <section class="assignment-selection" aria-labelledby="review-additional-attachments-title">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 id="review-additional-attachments-title" class="mb-0">مستندات وملفات إضافية</h6>
                                    <span class="badge bg-primary">
                                        <span id="review-additional-attachments-count">{{ $isEdit ? $auction->attachments->where('category', 'auction_doc')->count() : 0 }}</span> ملف
                                    </span>
                                </div>
                                <div id="review-additional-attachments" class="assignment-file-list">
                                    @if ($isEdit && $auction->attachments->where('category', 'auction_doc')->isNotEmpty())
                                        @foreach ($auction->attachments->where('category', 'auction_doc') as $attachment)
                                            <x-ui.file-card :file="$attachment" />
                                        @endforeach
                                    @else
                                        <p class="text-muted mb-0" data-empty>لم يتم إرفاق ملفات إضافية بعد.</p>
                                    @endif
                                </div>
                            </section>
                        </div>

                        @php
                            $boardAttachments = $isEdit ? $auction->attachments->whereIn('report_section', ['board_before', 'board_after']) : collect();
                            $marketingEvidenceAttachments = $isEdit ? $auction->attachments->where('report_section', 'marketing_evidence') : collect();
                        @endphp
                        <div class="col-md-6 {{ $boardAttachments->isEmpty() ? 'd-none' : '' }}" id="review-board-documents-wrapper">
                            <section class="assignment-selection" aria-labelledby="review-board-documents-title">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 id="review-board-documents-title" class="mb-0">صور اللوحات الإعلانية الميدانية</h6>
                                    <span class="badge bg-primary"><span id="review-board-documents-count">{{ $boardAttachments->count() }}</span> صورة</span>
                                </div>
                                <div id="review-board-documents" class="assignment-file-list">
                                    @forelse ($boardAttachments as $attachment)
                                        <x-ui.file-card :file="$attachment" />
                                    @empty
                                        <p class="text-muted mb-0" data-empty>لم يتم إرفاق صور لوحات بعد.</p>
                                    @endforelse
                                </div>
                            </section>
                        </div>

                        <div class="col-md-6 {{ $marketingEvidenceAttachments->isEmpty() ? 'd-none' : '' }}" id="review-marketing-evidence-wrapper">
                            <section class="assignment-selection" aria-labelledby="review-marketing-evidence-title">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 id="review-marketing-evidence-title" class="mb-0">أدلة ومواد الحملات التسويقية</h6>
                                    <span class="badge bg-primary"><span id="review-marketing-evidence-count">{{ $marketingEvidenceAttachments->count() }}</span> ملف</span>
                                </div>
                                <div id="review-marketing-evidence" class="assignment-file-list">
                                    @forelse ($marketingEvidenceAttachments as $attachment)
                                        <x-ui.file-card :file="$attachment" />
                                    @empty
                                        <p class="text-muted mb-0" data-empty>لم يتم إرفاق أدلة تسويقية بعد.</p>
                                    @endforelse
                                </div>
                            </section>
                        </div>
                    </div>
                </x-form.wizard.review-card>
            </div>

            {{-- 5. النتائج واللوحات (معطلة مؤقتاً) --}}
            {{--
            <div class="col-lg-6">
                <x-form.wizard.review-card title="النتائج واللوحات" step="5" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">نتائج العقارات</dt>
                        <dd class="col-7 fw-semibold" id="review-results-summary">
                            @if ($isEdit && $auction->properties->isNotEmpty())
                                {{ $auction->properties->where('pivot.result_status', 'sold')->count() }} مباع / {{ $auction->properties->where('pivot.result_status', 'unsold')->count() }} لم يُبع
                            @else
                                قيد الانتظار
                            @endif
                        </dd>

                        <dt class="col-5 text-muted">لوحات قبل المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-board-before-count">{{ $isEdit ? $auction->attachments->where('report_section', 'board_before')->count() : 0 }} صور</dd>

                        <dt class="col-5 text-muted">لوحات بعد المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-board-after-count">{{ $isEdit ? $auction->attachments->where('report_section', 'board_after')->count() : 0 }} صور</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>
            --}}

            {{-- 6. الحملات التسويقية (معطلة مؤقتاً) --}}
            {{--
            <div class="col-lg-6">
                <x-form.wizard.review-card title="الحملات التسويقية" step="6" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">عدد الحملات</dt>
                        <dd class="col-7 fw-semibold" id="review-campaigns-count">{{ count($marketing['campaigns'] ?? []) }} حملة</dd>

                        <dt class="col-5 text-muted">المنصات الإعلانية</dt>
                        <dd class="col-7 fw-semibold" id="review-campaigns-platforms">
                            @php
                                $platforms = collect($marketing['campaigns'] ?? [])->pluck('platform')->filter()->map(fn($p) => __('auction_reports.channels.'.$p, [], 'ar') ?: $p)->join('، ');
                            @endphp
                            {{ $platforms ?: '—' }}
                        </dd>

                        <dt class="col-5 text-muted">صور وأدلة التسويق</dt>
                        <dd class="col-7 fw-semibold" id="review-evidence-count">{{ $isEdit ? $auction->attachments->where('report_section', 'marketing_evidence')->count() : 0 }} ملف</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>
            --}}
        </div>
    </div>
</div>
