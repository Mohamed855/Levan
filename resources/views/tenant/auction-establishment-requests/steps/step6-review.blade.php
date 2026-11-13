@php
    $selectedRights = $data['disclosures']['disposition_rights'] ?? [];
    $rightsLabels = collect($selectedRights)->map(fn($r) => $schema->dispositionRights()[$r] ?? $r)->implode('، ');
    $totalAttachmentsCount = count($data['attachments']['manager_certificate'] ?? [])
        + count($data['attachments']['auctioneer_certificate'] ?? [])
        + count($data['attachments']['deed'] ?? [])
        + count($data['attachments']['valuation'] ?? [])
        + count($data['attachments']['representative_authority'] ?? []);
    $fallbackImage = asset('static/brand/logos/logo.svg');
@endphp

<div class="row g-4 assignment-review">
    <div class="col-12 d-flex flex-column gap-4">
        <div class="alert alert-warning mb-0 d-flex align-items-center gap-2" role="alert">
            <i class="ti ti-alert-triangle fs-3" aria-hidden="true"></i>
            <span>برجاء مراجعة بيانات المنشأة والمسؤولين والمزاد والعقارات والإفصاحات والمرفقات قبل الاعتماد النهائي للطلب.</span>
        </div>

        <div class="row g-3">
            {{-- 1. المنشأة ومسؤولو المزاد --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="المنشأة ومسؤولو المزاد" step="1" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">اسم المنشأة</dt>
                        <dd class="col-7 fw-semibold" id="review-org-name">{{ $data['organization']['name'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">السجل التجاري</dt>
                        <dd class="col-7 fw-semibold" id="review-org-cr">{{ $data['organization']['commercial_registration'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">الرقم الموحد (700)</dt>
                        <dd class="col-7 fw-semibold" id="review-org-unified">{{ $data['organization']['unified_number'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">رخصة فال</dt>
                        <dd class="col-7 fw-semibold" id="review-org-fal">{{ $data['organization']['fal_license'] ?? '—' }}</dd>

                        <dt class="col-12 mt-2 pt-2 border-top text-primary fw-bold">
                            <i class="ti ti-user-check me-1"></i>مدير المزاد
                        </dt>
                        <dt class="col-5 text-muted">الاسم</dt>
                        <dd class="col-7 fw-semibold" id="review-mgr-name">{{ $data['manager']['name'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">رقم الهوية</dt>
                        <dd class="col-7 fw-semibold" id="review-mgr-id">{{ $data['manager']['identity_number'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">رقم الجوال</dt>
                        <dd class="col-7 fw-semibold" id="review-mgr-mobile">{{ $data['manager']['mobile'] ?? '—' }}</dd>

                        <dt class="col-12 mt-2 pt-2 border-top text-primary fw-bold">
                            <i class="ti ti-gavel me-1"></i>محرّج المزاد
                        </dt>
                        <dt class="col-5 text-muted">الاسم</dt>
                        <dd class="col-7 fw-semibold" id="review-auc-name">{{ $data['auctioneer']['name'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">رقم الهوية</dt>
                        <dd class="col-7 fw-semibold" id="review-auc-id">{{ $data['auctioneer']['identity_number'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">رقم الجوال</dt>
                        <dd class="col-7 fw-semibold" id="review-auc-mobile">{{ $data['auctioneer']['mobile'] ?? '—' }}</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            {{-- 2. تفاصيل إقامة المزاد والأموال --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="تفاصيل المزاد والأموال" step="2" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">اسم المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-auction-name">{{ $data['auction']['name'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">طريقة الإقامة</dt>
                        <dd class="col-7 fw-semibold" id="review-auction-method">
                            {{ match($data['auction']['method'] ?? '') { 'electronic' => 'إلكتروني', 'in_person' => 'حضوري', 'hybrid' => 'هجين', default => $data['auction']['method'] ?? '—' } }}
                        </dd>

                        <dt class="col-5 text-muted">المدينة</dt>
                        <dd class="col-7 fw-semibold" id="review-auction-city">{{ $data['auction']['city'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">بداية ونهاية المزاد</dt>
                        <dd class="col-7 fw-semibold" id="review-auction-start">
                            {{ system_date($data['auction']['start_date'] ?? null, '') . ' ' . ($data['auction']['start_time'] ?? '') }} إلى {{ system_date($data['auction']['end_date'] ?? null, '') }}
                        </dd>

                        <dt class="col-5 text-muted">نوع التصرف</dt>
                        <dd class="col-7 fw-semibold" id="review-auction-disposition">
                            {{ match($data['auction']['disposition'] ?? '') { 'sale' => 'بيع', 'lease' => 'تأجير', default => $data['auction']['disposition'] ?? '—' } }}
                        </dd>

                        <dt class="col-12 mt-2 pt-2 border-top text-primary fw-bold">
                            <i class="ti ti-cash me-1"></i>أموال المتنافسين
                        </dt>
                        <dt class="col-5 text-muted">طريقة الاستلام</dt>
                        <dd class="col-7 fw-semibold" id="review-funds-receipt">
                            {{ match($data['funds']['receipt_method'] ?? '') { 'transfer' => 'حوالة بنكية', 'cheque' => 'شيك', 'other' => 'أخرى', default => $data['funds']['receipt_method'] ?? '—' } }}
                        </dd>

                        <dt class="col-5 text-muted">آلية حفظ الأموال</dt>
                        <dd class="col-7 fw-semibold text-break" id="review-funds-safekeeping">{{ $data['funds']['safekeeping'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">طريقة الإعادة</dt>
                        <dd class="col-7 fw-semibold" id="review-funds-refund">
                            {{ match($data['funds']['refund_method'] ?? '') { 'transfer' => 'حوالة بنكية', 'cheque' => 'شيك', 'other' => 'أخرى', default => $data['funds']['refund_method'] ?? '—' } }}
                        </dd>

                        <dt class="col-5 text-muted">نوع مبلغ الدخول</dt>
                        <dd class="col-7 fw-semibold" id="review-funds-entry">
                            {{ match($data['funds']['entry_type'] ?? '') { 'earnest' => 'عربون', 'advance' => 'مقدم ثمن', 'refundable' => 'مبلغ مسترد', 'none' => 'بدون مبلغ', default => $data['funds']['entry_type'] ?? '—' } }}
                        </dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            {{-- 3. بيانات العقارات --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="العقارات المحددة" step="3" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-3 small">
                        <dt class="col-5 text-muted">عدد العقارات</dt>
                        <dd class="col-7 fw-semibold" id="review-selected-props-count">{{ count($data['properties']) }} عقار</dd>

                        <dt class="col-5 text-muted">إجمالي المساحة</dt>
                        <dd class="col-7 fw-semibold" id="review-selected-props-area">{{ number_format((float) $auction->properties->sum('area')) }} م²</dd>

                        <dt class="col-5 text-muted">إجمالي القيمة التقديرية</dt>
                        <dd class="col-7 fw-semibold" id="review-selected-props-val">{{ number_format((float) $auction->properties->sum('estimated_value')) }} ر.س</dd>
                    </dl>

                    <div id="review-properties" class="assignment-selection__items">
                        @foreach ($auction->properties as $propObj)
                            @php
                                $image = $propObj->main_image_url ?? $propObj->image_url ?? $fallbackImage;
                            @endphp
                            <article class="assignment-property-card" data-property-card="{{ $propObj->id }}">
                                <img class="assignment-property-card__image"
                                    src="{{ $image }}"
                                    alt="{{ $propObj->name }}"
                                    onerror="this.onerror=null;this.src='{{ $fallbackImage }}';">
                                <div class="assignment-property-card__content">
                                    <div class="d-flex flex-wrap align-items-center gap-2 mb-1">
                                        <code class="copyable-code">{{ $propObj->property_number ?? '-' }}</code>
                                        @if (!empty($propObj->type_label))
                                            <span class="badge bg-info">{{ $propObj->type_label }}</span>
                                        @endif
                                        <span class="badge bg-label-success property-review-status-badge"
                                            data-review-badge="{{ $propObj->id }}">
                                            <i class="ti ti-check me-1"></i>مكتمل
                                        </span>
                                    </div>
                                    <h6 class="mb-1 text-body text-truncate" title="{{ $propObj->name }}">
                                        {{ $propObj->name ?? 'عقار' }}
                                    </h6>
                                    <p class="mb-0 text-muted small">
                                        <i class="ti ti-map-pin me-1"></i>{{ $propObj->city ?? '-' }}{{ $propObj->district ? ' - ' . $propObj->district : '' }} &bull; {{ number_format((float) ($propObj->area ?? 0)) }} م²
                                    </p>
                                </div>
                            </article>
                        @endforeach
                    </div>
                </x-form.wizard.review-card>
            </div>

            {{-- 4. الإفصاحات والتعهدات --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="الإفصاحات والنظام" step="4" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">حقوق التصرف</dt>
                        <dd class="col-7 fw-semibold" id="review-disclosures-rights">{{ $rightsLabels ?: '—' }}</dd>

                        <dt class="col-5 text-muted">الممثل النظامي</dt>
                        <dd class="col-7 fw-semibold" id="review-declaration-rep">{{ $data['declaration']['representative_name'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">حقوق عينية</dt>
                        <dd class="col-7 fw-semibold">{{ ($data['disclosures']['real_rights'] ?? 'no') === 'yes' ? 'نعم' : 'لا' }}</dd>

                        <dt class="col-5 text-muted">نزاع على العقار</dt>
                        <dd class="col-7 fw-semibold">{{ ($data['disclosures']['dispute'] ?? 'no') === 'yes' ? 'نعم' : 'لا' }}</dd>

                        <dt class="col-5 text-muted">بيع على الخارطة</dt>
                        <dd class="col-7 fw-semibold">{{ ($data['disclosures']['off_plan'] ?? 'no') === 'yes' ? 'نعم' : 'لا' }}</dd>

                        <dt class="col-5 text-muted">مساهمة عقارية</dt>
                        <dd class="col-7 fw-semibold">{{ ($data['disclosures']['contribution'] ?? 'no') === 'yes' ? 'نعم' : 'لا' }}</dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            {{-- 5. المرفقات والمستندات --}}
            <div class="col-12">
                <x-form.wizard.review-card title="المستندات والمرفقات" step="5" col="" class="assignment-review__card">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <section class="assignment-selection" aria-labelledby="review-certificates-title">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 id="review-certificates-title" class="mb-0">
                                        <i class="ti ti-certificate text-primary me-1"></i>شهادات مسؤولي المزاد
                                    </h6>
                                </div>
                                <div class="assignment-file-list" id="review-certificates-list">
                                    @foreach (['manager_certificate', 'auctioneer_certificate'] as $type)
                                        @php
                                            $existing = $data['attachments'][$type][0] ?? null;
                                            $attachmentUrl = $existing ? tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => $type]) : null;
                                        @endphp
                                        @if ($existing)
                                            <x-ui.file-card :name="$existing['name']" :url="$attachmentUrl" :meta="$schema->attachmentTypes()[$type]" />
                                        @else
                                            <div class="p-2 border rounded d-flex align-items-center justify-content-between small text-muted">
                                                <span>{{ $schema->attachmentTypes()[$type] }}</span>
                                                <span class="badge bg-label-warning" data-review-file-badge="{{ $type }}">بانتظار الرفع</span>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        </div>

                        <div class="col-md-6">
                            <section class="assignment-selection" aria-labelledby="review-properties-docs-title">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h6 id="review-properties-docs-title" class="mb-0">
                                        <i class="ti ti-files text-primary me-1"></i>مستندات عقارات المزاد
                                    </h6>
                                    <span class="badge bg-primary" id="review-attachments-summary">
                                        {{ $totalAttachmentsCount }} مرفق مسجل
                                    </span>
                                </div>
                                <div class="assignment-file-list" id="review-property-docs-list">
                                    @foreach ($data['properties'] as $index => $propertyData)
                                        @php
                                            $property = $auction->properties->firstWhere('id', $propertyData['property_id']);
                                            $propAttachments = [];
                                            foreach (['deed', 'valuation', 'representative_authority'] as $type) {
                                                if (isset($data['attachments'][$type][$propertyData['property_id']])) {
                                                    $propAttachments[$type] = $data['attachments'][$type][$propertyData['property_id']];
                                                }
                                            }
                                        @endphp
                                        <div class="border rounded p-2 mb-2">
                                            <div class="fw-semibold small mb-1">
                                                <i class="ti ti-building me-1"></i>{{ $property?->name ?? 'عقار' }} ({{ $property?->property_number ?? '-' }})
                                            </div>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach (['deed', 'valuation', 'representative_authority'] as $type)
                                                    @php
                                                        $att = $propAttachments[$type] ?? null;
                                                    @endphp
                                                    <span class="badge {{ $att ? 'bg-label-success' : 'bg-label-secondary' }}">
                                                        {{ $schema->attachmentTypes()[$type] }}: {{ $att ? 'مرفوع' : 'غير مرفوع' }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </section>
                        </div>
                    </div>
                </x-form.wizard.review-card>
            </div>
        </div>

        {{-- خانة الإقرار والتعهد النهائي --}}
        <div class="card border border-primary-subtle bg-light-subtle p-3 mb-2">
            <div class="d-flex align-items-start gap-3">
                <i class="ti ti-file-certificate fs-2 text-primary mt-1"></i>
                <div class="flex-grow-1">
                    <h6 class="fw-bold mb-1">الإقرار والتعهد النهائي لمطابقة طلب إقامة المزاد</h6>
                    <x-form.controls.checkbox name="declaration_accepted" value="1"
                        label="أقر بصحة البيانات والمرفقات، وسريان رخصة فال وعقود المزاد، والالتزام بمتطلبات وإقرار وتعهد نموذج طلب إقامة المزاد العقاري وفق اشتراطات الهيئة العامة للعقار."
                        required col="col-12" />
                </div>
            </div>
        </div>
    </div>
</div>
