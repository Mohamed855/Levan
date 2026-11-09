@php
    $selectedRights = $data['disclosures']['disposition_rights'] ?? [];
    $rightsLabels = collect($selectedRights)->map(fn($r) => $schema->dispositionRights()[$r] ?? $r)->implode('، ');
    $totalAttachmentsCount = count($data['attachments']['manager_certificate'] ?? [])
        + count($data['attachments']['auctioneer_certificate'] ?? [])
        + count($data['attachments']['deed'] ?? [])
        + count($data['attachments']['valuation'] ?? [])
        + count($data['attachments']['representative_authority'] ?? []);
@endphp

<div class="alert alert-warning mb-4 d-flex align-items-center gap-2" role="alert">
    <i class="ti ti-alert-triangle fs-4" aria-hidden="true"></i>
    <span>برجاء مراجعة بيانات المنشأة والمسؤولين والمزاد والعقارات والإفصاحات والمرفقات قبل الاعتماد النهائي للطلب.</span>
</div>

<div class="row g-4 mb-4">
    {{-- 1. المنشأة ومسؤولو المزاد --}}
    <div class="col-lg-6">
        <x-form.wizard.review-card title="المنشأة ومسؤولو المزاد" step="1" class="h-100 assignment-review__card">
            <dl class="row mb-0 small">
                <dt class="col-5 text-muted">اسم المنشأة</dt>
                <dd class="col-7 fw-semibold" id="review-org-name">{{ $data['organization']['name'] ?? '—' }}</dd>

                <dt class="col-5 text-muted">السجل التجاري</dt>
                <dd class="col-7 fw-semibold" id="review-org-cr">{{ $data['organization']['commercial_registration'] ?? '—' }}</dd>

                <dt class="col-5 text-muted">الرقم الموحد</dt>
                <dd class="col-7 fw-semibold" id="review-org-unified">{{ $data['organization']['unified_number'] ?? '—' }}</dd>

                <dt class="col-5 text-muted">رخصة فال</dt>
                <dd class="col-7 fw-semibold" id="review-org-fal">{{ $data['organization']['fal_license'] ?? '—' }}</dd>

                <dt class="col-12 mt-2 pt-2 border-top text-primary fw-bold">مدير المزاد</dt>
                <dt class="col-5 text-muted">الاسم</dt>
                <dd class="col-7 fw-semibold" id="review-mgr-name">{{ $data['manager']['name'] ?? '—' }}</dd>

                <dt class="col-5 text-muted">رقم الهوية</dt>
                <dd class="col-7 fw-semibold" id="review-mgr-id">{{ $data['manager']['identity_number'] ?? '—' }}</dd>

                <dt class="col-5 text-muted">رقم الجوال</dt>
                <dd class="col-7 fw-semibold" id="review-mgr-mobile">{{ $data['manager']['mobile'] ?? '—' }}</dd>

                <dt class="col-12 mt-2 pt-2 border-top text-primary fw-bold">محرّج المزاد</dt>
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
        <x-form.wizard.review-card title="تفاصيل المزاد والأموال" step="2" class="h-100 assignment-review__card">
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
                    {{ ($data['auction']['start_date'] ?? '') . ' ' . ($data['auction']['start_time'] ?? '') }} إلى {{ $data['auction']['end_date'] ?? '' }}
                </dd>

                <dt class="col-5 text-muted">نوع التصرف</dt>
                <dd class="col-7 fw-semibold" id="review-auction-disposition">
                    {{ match($data['auction']['disposition'] ?? '') { 'sale' => 'بيع', 'lease' => 'تأجير', default => $data['auction']['disposition'] ?? '—' } }}
                </dd>

                <dt class="col-12 mt-2 pt-2 border-top text-primary fw-bold">أموال المتنافسين</dt>
                <dt class="col-5 text-muted">طريقة الاستلام</dt>
                <dd class="col-7 fw-semibold" id="review-funds-receipt">
                    {{ match($data['funds']['receipt_method'] ?? '') { 'transfer' => 'حوالة بنكية', 'cheque' => 'شيك', 'other' => 'أخرى', default => $data['funds']['receipt_method'] ?? '—' } }}
                </dd>

                <dt class="col-5 text-muted">آلية حفظ الأموال</dt>
                <dd class="col-7 fw-semibold" id="review-funds-safekeeping">{{ $data['funds']['safekeeping'] ?? '—' }}</dd>

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
        <x-form.wizard.review-card title="العقارات المرتبطة" step="3" class="h-100 assignment-review__card">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <span class="text-muted small">عدد العقارات: <strong class="text-body">{{ count($data['properties']) }}</strong></span>
                <span class="text-muted small">إجمالي المساحة: <strong class="text-body">{{ number_format((float) $auction->properties->sum('area')) }} م²</strong></span>
            </div>
            <div class="assignment-selection__items">
                @foreach ($auction->properties as $propObj)
                    <div class="border rounded p-2 d-flex align-items-center gap-2">
                        <i class="ti ti-building text-primary fs-4"></i>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-truncate small">{{ $propObj->name ?? 'عقار' }}</div>
                            <div class="text-muted extra-small">{{ $propObj->property_number ?? '-' }} &bull; {{ $propObj->city ?? '-' }}</div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-form.wizard.review-card>
    </div>

    {{-- 4. الإفصاحات والتعهدات --}}
    <div class="col-lg-6">
        <x-form.wizard.review-card title="الإفصاحات والنظام" step="4" class="h-100 assignment-review__card">
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
            </dl>
        </x-form.wizard.review-card>
    </div>

    {{-- 5. المرفقات والمستندات --}}
    <div class="col-12">
        <x-form.wizard.review-card title="المرفقات والمستندات" step="5" class="assignment-review__card">
            <p class="text-muted small mb-2" id="review-attachments-summary">
                {{ $totalAttachmentsCount }} مرفقات مسجلة في هذا الطلب
            </p>
            <div class="row g-2">
                @foreach (['manager_certificate', 'auctioneer_certificate'] as $type)
                    @php
                        $existing = $data['attachments'][$type][0] ?? null;
                    @endphp
                    <div class="col-md-6">
                        <div class="p-2 border rounded d-flex align-items-center justify-content-between small">
                            <span>{{ $schema->attachmentTypes()[$type] }}</span>
                            <span class="badge {{ $existing ? 'bg-label-success' : 'bg-label-warning' }}">{{ $existing ? 'مرفوع' : 'غير مرفوع' }}</span>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-form.wizard.review-card>
    </div>
</div>

{{-- خانة الإقرار والتعهد النهائي --}}
<div class="mb-4">
    <x-form.controls.checkbox name="declaration_accepted" value="1"
        label="أقر بصحة البيانات والمرفقات، وسريان رخصة فال وعقود المزاد، والالتزام بمتطلبات وإقرار وتعهد نموذج طلب إقامة المزاد العقاري."
        required col="col-12" />
</div>
