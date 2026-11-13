@php
    $selectedRights = $data['disclosures']['disposition_rights'] ?? [];
    $rightsLabels = collect($selectedRights)->map(fn($r) => $schema->dispositionRights()[$r] ?? $r)->implode('، ');
    $fallbackImage = asset('static/brand/logos/logo.svg');
@endphp

<div class="row g-4 assignment-review">
    <div class="col-12 d-flex flex-column gap-4">
        {{-- تنبيه الإقرار الرسمي بدلاً من النص الإرشادي العادي وبدلاً من الشيك بوكس --}}
        <div class="alert alert-primary mb-0 d-flex align-items-center gap-3 border shadow-none" role="alert">
            <i class="ti ti-file-certificate fs-2 text-primary" aria-hidden="true"></i>
            <div class="small leading-normal">
                <strong>إقرار وتعهد نظامي:</strong>
                مراجعتك لبيانات المنشأة والمسؤولين والمزاد والعقارات والإفصاحات والمرفقات وإرسال هذا النموذج تُعد إقراراً وتعهداً رسمياً منك بصحة واكتمال كافة البيانات المسجلة، وسريان رخصة فال وعقود المزاد، والالتزام بمتطلبات واشتراطات الهيئة العامة للعقار.
            </div>
        </div>

        {{-- حقل الإقرار المخفي المفعل دائماً --}}
        <input type="hidden" name="declaration_accepted" value="1">

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

                        <dt class="col-5 text-muted">مدة الترسية</dt>
                        <dd class="col-7 fw-semibold" id="review-auction-award-period">
                            {{ !empty($data['auction']['award_period']) ? $data['auction']['award_period'] . ' دقيقة' : '—' }}
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

            {{-- 3. الإفصاحات والنظام --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="الإفصاحات والنظام" step="4" col="" class="h-100 assignment-review__card">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">حقوق التصرف</dt>
                        <dd class="col-7 fw-semibold" id="review-disclosures-rights">{{ $rightsLabels ?: '—' }}</dd>

                        <dt class="col-5 text-muted">الممثل النظامي</dt>
                        <dd class="col-7 fw-semibold" id="review-declaration-rep">{{ $data['declaration']['representative_name'] ?? '—' }}</dd>

                        <dt class="col-5 text-muted">حقوق عينية</dt>
                        <dd class="col-7 fw-semibold" id="review-real-rights">
                            {{ filled($data['disclosures']['real_rights_details'] ?? null) ? $data['disclosures']['real_rights_details'] : 'لا يوجد' }}
                        </dd>

                        <dt class="col-5 text-muted">نزاع على العقار</dt>
                        <dd class="col-7 fw-semibold" id="review-dispute">
                            {{ filled($data['disclosures']['dispute_details'] ?? null) ? $data['disclosures']['dispute_details'] : 'لا يوجد' }}
                        </dd>

                        <dt class="col-5 text-muted">معلومات مؤثرة على القيمة</dt>
                        <dd class="col-7 fw-semibold" id="review-value-info">
                            {{ filled($data['disclosures']['value_information_details'] ?? null) ? $data['disclosures']['value_information_details'] : 'لا يوجد' }}
                        </dd>

                        <dt class="col-5 text-muted">بيع على الخارطة</dt>
                        <dd class="col-7 fw-semibold" id="review-off-plan">
                            {{ filled($data['disclosures']['off_plan_details'] ?? null) ? $data['disclosures']['off_plan_details'] : 'لا يوجد' }}
                        </dd>

                        <dt class="col-5 text-muted">مساهمة عقارية</dt>
                        <dd class="col-7 fw-semibold" id="review-contribution">
                            {{ filled($data['disclosures']['contribution_details'] ?? null) ? $data['disclosures']['contribution_details'] : 'لا يوجد' }}
                        </dd>
                    </dl>
                </x-form.wizard.review-card>
            </div>

            {{-- 4. المستندات والمرفقات العامة --}}
            <div class="col-lg-6">
                <x-form.wizard.review-card title="شهادات مسؤولي المزاد" step="5" col="" class="h-100 assignment-review__card">
                    <section class="assignment-selection" aria-labelledby="review-certificates-title">
                        <div class="assignment-file-list" id="review-certificates-list">
                            @foreach (['manager_certificate', 'auctioneer_certificate'] as $type)
                                @php
                                    $existing = $data['attachments'][$type][0] ?? null;
                                    $attachmentUrl = $existing ? tenant_route_name('auction-establishment-requests.attachment', ['auction' => $auction, 'type' => $type]) : null;
                                @endphp
                                @if ($existing)
                                    <div class="mb-2">
                                        <x-ui.file-card :name="$existing['name']" :url="$attachmentUrl" :meta="$schema->attachmentTypes()[$type]" />
                                    </div>
                                @else
                                    <div class="p-2 border rounded d-flex align-items-center justify-content-between small text-muted mb-2">
                                        <span>{{ $schema->attachmentTypes()[$type] }}</span>
                                        <span class="badge bg-label-warning" data-review-file-badge="{{ $type }}">بانتظار الرفع</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </section>
                </x-form.wizard.review-card>
            </div>
        </div>
    </div>
</div>
