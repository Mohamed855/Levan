@php
    $isEdit = isset($auction) && $auction->exists;
@endphp

<div class="row g-4">
    {{-- صور المزاد والتقرير --}}
    <div class="col-12">
        <h6 class="fw-bold mb-3"><i class="ti ti-photo me-1"></i>صور التتقرير والمزاد</h6>
    </div>

    {{-- صورة التقرير --}}
    <div class="col-md-6">
        @if ($isEdit)
            <label class="form-label fw-semibold">
                صورة غلاف التقرير
                @if ($auction->report_image)
                    <span class="badge bg-label-success ms-1">محفوظة</span>
                @else
                    <span class="badge bg-label-danger ms-1">مطلوبة</span>
                @endif
            </label>
            <x-form.controls.file 
                name="report_image" 
                label="صورة غلاف التقرير"
                :value="$auction->report_image ? tasset($auction->report_image) : null"
                help="{{ $auction->report_image ? 'اختياري للتحديث — ارفع صورة جديدة للاستبدال' : 'مطلوبة — تظهر في غلاف ملف تقرير المزاد (JPG/PNG/WebP، بحد أقصى 10MB)' }}"
                :required="!$auction->report_image" 
                accept="image/jpeg,image/png,image/webp" 
                col="col-12" 
            />
        @else
            <x-form.controls.file 
                name="report_image" 
                label="صورة غلاف التقرير"
                help="مطلوبة — تظهر في غلاف ملف تقرير المزاد (JPG/PNG/WebP، بحد أقصى 10MB)"
                required 
                col="col-12" 
                accept="image/jpeg,image/png,image/webp" 
            />
        @endif
    </div>

    {{-- صورة الغلاف --}}
    <div class="col-md-6">
        <x-form.controls.file 
            name="cover_image" 
            label="صورة غلاف المزاد"
            :value="$isEdit && $auction->cover_image ? tasset($auction->cover_image) : null"
            help="تظهر في مخرجات المزاد الرئيسية (اختيارية)"
            col="col-12" 
            accept="image/*" 
        />
    </div>

    {{-- نتائج العقارات --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3"><i class="ti ti-chart-bar me-1"></i>نتائج عقارات المزاد</h6>
        <p class="text-muted small mb-3">سجّل أو حدّث نتيجة كل عقار في المزاد.</p>
        <div id="property-results-container">
            @if ($isEdit)
                @forelse($auction->properties as $prop)
                    @php
                        $pivot = $prop->pivot;
                        $resultStatus = $pivot->result_status ?? 'pending';
                    @endphp
                    <div class="card mb-3 border" data-property-result="{{ $prop->id }}">
                        <div class="card-header d-flex justify-content-between align-items-center py-2">
                            <h6 class="mb-0">{{ $prop->name }} <code class="ms-1">{{ $prop->property_number }}</code></h6>
                            <span class="badge {{ $resultStatus === 'sold' ? 'bg-success' : ($resultStatus === 'unsold' ? 'bg-danger' : 'bg-warning') }}">
                                {{ $resultStatus === 'sold' ? 'مُباع' : ($resultStatus === 'unsold' ? 'لم يُباع' : 'قيد الانتظار') }}
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label">الحالة</label>
                                    <select name="property_results[{{ $prop->id }}][status]" class="form-select">
                                        <option value="pending" {{ $resultStatus === 'pending' ? 'selected' : '' }}>قيد الانتظار</option>
                                        <option value="sold" {{ $resultStatus === 'sold' ? 'selected' : '' }}>مُباع</option>
                                        <option value="unsold" {{ $resultStatus === 'unsold' ? 'selected' : '' }}>لم يُباع</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">سعر الافتتاح</label>
                                    <input type="number" class="form-control" name="property_results[{{ $prop->id }}][opening_price]"
                                        value="{{ $pivot->opening_price }}" min="0" step="0.01">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">سعر البيع</label>
                                    <input type="number" class="form-control" name="property_results[{{ $prop->id }}][sale_price]"
                                        value="{{ $pivot->sale_price }}" min="0" step="0.01">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">عدد المشاركين</label>
                                    <input type="number" class="form-control" name="property_results[{{ $prop->id }}][participants_count]"
                                        value="{{ $pivot->participants_count }}" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">عدد المزايدات</label>
                                    <input type="number" class="form-control" name="property_results[{{ $prop->id }}][bids_count]"
                                        value="{{ $pivot->bids_count }}" min="0">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">سبب عدم البيع</label>
                                    <input type="text" class="form-control" name="property_results[{{ $prop->id }}][unsold_reason]"
                                        value="{{ $pivot->unsold_reason }}">
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-muted" id="results-empty-msg">لا توجد عقارات مرتبطة بعد.</p>
                @endforelse
            @else
                <p class="text-muted" id="results-empty-msg">اختر العقارات في الخطوة السابقة أولاً.</p>
            @endif
        </div>
    </div>

    {{-- بيانات التسويق --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3"><i class="ti ti-speakerphone me-1"></i>بيانات التسويق والحملات</h6>

        <section class="assignment-properties-table-card mb-4" aria-live="polite">
            <header class="assignment-properties-table-card__header">
                <h6 class="mb-0">حملات التسويق</h6>
                <button type="button" class="btn btn-sm btn-primary" id="add-campaign-btn">
                    <i class="ti ti-plus me-1"></i> إضافة حملة
                </button>
            </header>
            <div class="p-3">
                <div id="campaigns-container">
                    @if ($isEdit && !empty($auction->marketing_data['campaigns']))
                        @foreach($auction->marketing_data['campaigns'] as $i => $campaign)
                            <div class="row g-2 mb-3 border-bottom pb-3 campaign-row" data-index="{{ $i }}">
                                <div class="col-md-3">
                                    <input type="text" class="form-control" name="marketing[campaigns][{{ $i }}][platform]"
                                        placeholder="المنصة" value="{{ $campaign['platform'] ?? '' }}" required>
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="marketing[campaigns][{{ $i }}][reach]"
                                        placeholder="الوصول" value="{{ $campaign['reach'] ?? '' }}" min="0">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="marketing[campaigns][{{ $i }}][impressions]"
                                        placeholder="المشاهدات" value="{{ $campaign['impressions'] ?? '' }}" min="0">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="marketing[campaigns][{{ $i }}][engagements]"
                                        placeholder="التفاعل" value="{{ $campaign['engagements'] ?? '' }}" min="0">
                                </div>
                                <div class="col-md-2">
                                    <input type="number" class="form-control" name="marketing[campaigns][{{ $i }}][clicks]"
                                        placeholder="النقرات" value="{{ $campaign['clicks'] ?? '' }}" min="0">
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-icon btn-sm btn-label-danger remove-campaign">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                                <div class="col-12">
                                    <input type="url" class="form-control" name="marketing[campaigns][{{ $i }}][url]"
                                        placeholder="رابط الحملة" value="{{ $campaign['url'] ?? '' }}">
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0" id="campaigns-empty">لا توجد حملات مضافة بعد.</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="assignment-properties-table-card mb-4" aria-live="polite">
            <header class="assignment-properties-table-card__header">
                <h6 class="mb-0">روابط التسويق</h6>
                <button type="button" class="btn btn-sm btn-primary" id="add-link-btn">
                    <i class="ti ti-plus me-1"></i> إضافة رابط
                </button>
            </header>
            <div class="p-3">
                <div id="links-container">
                    @if ($isEdit && !empty($auction->marketing_data['links']))
                        @foreach($auction->marketing_data['links'] as $i => $link)
                            <div class="row g-2 mb-2 link-row" data-index="{{ $i }}">
                                <div class="col-md-4">
                                    <input type="text" class="form-control" name="marketing[links][{{ $i }}][label]"
                                        placeholder="التسمية" value="{{ $link['label'] ?? '' }}" required>
                                </div>
                                <div class="col-md-6">
                                    <input type="url" class="form-control" name="marketing[links][{{ $i }}][url]"
                                        placeholder="الرابط" value="{{ $link['url'] ?? '' }}" required>
                                </div>
                                <div class="col-md-1">
                                    <button type="button" class="btn btn-icon btn-sm btn-label-danger remove-link">
                                        <i class="ti ti-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <p class="text-muted mb-0" id="links-empty">لا توجد روابط مضافة بعد.</p>
                    @endif
                </div>
            </div>
        </section>

        <x-form.controls.file 
            name="marketing_evidence[]" 
            label="أدلة وصور حملات التسويق" 
            multiple
            :required="false" 
            accept=".pdf,.png,.jpg,.jpeg,.webp"
            help="صور الإعلانات والمنشورات والإثباتات (الحد الأقصى لكل ملف: 10MB)"
            col="col-md-6" 
        />
    </div>

    {{-- صور اللوحات --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3"><i class="ti ti-billboard me-1"></i>صور اللوحات الإعلانية</h6>
        @if ($isEdit && $auction->attachments->whereIn('report_section', ['board_before', 'board_after'])->isNotEmpty())
            <div class="alert alert-info py-2">الصور المسجلة حاليًا محفوظة، ويمكن إضافة صور أخرى لكل عقار أدناه.</div>
        @endif
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">صور اللوحات قبل المزاد</label>
                <div id="board-before-container">
                    <p class="text-muted small">اختر العقارات أولاً لإضافة صور لوحاتها.</p>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">صور اللوحات بعد المزاد</label>
                <div id="board-after-container">
                    <p class="text-muted small">اختر العقارات أولاً لإضافة صور لوحاتها.</p>
                </div>
            </div>
        </div>
    </div>

    {{-- المرفقات الرسمية --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3"><i class="ti ti-paperclip me-1"></i>مرفقات ووثائق المزاد</h6>
        @if ($isEdit && $auction->attachments->where('category', 'auction_doc')->isNotEmpty())
            <div class="card bg-label-secondary mb-3">
                <div class="card-body p-3">
                    <h6 class="mb-2 fw-semibold">المرفقات الحالية المسجلة:</h6>
                    <ul class="mb-0 ps-3">
                        @foreach ($auction->attachments->where('category', 'auction_doc') as $attachment)
                            <li>{{ $attachment->file_name }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif
        <x-form.controls.file 
            name="attachments[]" 
            label="ملفات إضافية" 
            multiple
            :required="false" 
            accept=".pdf,.png,.jpg,.jpeg,.doc,.docx,.xls,.xlsx"
            help="الحد الأقصى لكل ملف: 10MB" 
            col="col-12" 
        />
    </div>
</div>
