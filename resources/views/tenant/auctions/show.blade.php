@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل المزاد - ' . $auction->name)


@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ tenant_route_name(auth('tenant')->user()->can('auctions.view') ? 'auctions.index' : (auth('tenant')->user()->can('auction_establishment_requests.view') ? 'auction-establishment-requests.index' : 'auction-closings.index')) }}"
                class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
            @can('auctions.edit')
                @if (!$auction->isClosed())
                    <a href="{{ tenant_route_name('auctions.edit', $auction->id) }}" class="btn btn-primary">
                        <i class="ti ti-edit me-1"></i> تعديل المزاد
                    </a>
                @endif
            @endcan
        </div>
    </div>

    <div class="row">
        {{-- كارت الغلاف والمعلومات الرئيسية (العمود الأيمن) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3 text-center">
                        <img src="{{ $auction->cover_image ? tasset($auction->cover_image) : asset('static/brand/logos/logo.svg') }}"
                            alt="{{ $auction->name }}" class="rounded-circle border shadow-sm"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h4 class="mb-1 fw-bold text-primary">{{ $auction->name }}</h4>
                    <div class="d-flex justify-content-center flex-wrap gap-2 my-2 mb-3">
                        <span class="badge bg-label-primary fs-6">{{ $auction->category_label }}</span>
                        <span class="badge {{ $auction->status_badge_class }} fs-6">{{ $auction->status_label }}</span>
                        <span class="badge bg-label-{{ $auction->completion_status->color() }} fs-6">حالة الاكتمال:
                            {{ $auction->completion_status->label() }}</span>
                    </div>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ $auction->properties->count() }}</h5>
                            <small class="text-muted">العقارات</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success">
                                {{ number_format((float) $auction->properties->sum('estimated_value')) }}
                            </h5>
                            <small class="text-muted">إجمالي التقدير (ر.س)</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            @if ($auction->description)
                                <li class="mb-3 d-flex align-items-start">
                                    <i class="ti ti-notes text-primary me-2 fs-5"></i>
                                    <div>
                                        <span class="fw-bold me-2">الوصف:</span>
                                        <span class="text-muted small">{{ $auction->description }}</span>
                                    </div>
                                </li>
                            @endif
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-file-certificate text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الإسناد المرتبط:</span>
                                @if ($auction->assignment)
                                    <a href="{{ tenant_route_name('assignments.show', $auction->assignment_id) }}"
                                        class="text-primary fw-medium">
                                        {{ $auction->assignment->name }} ({{ $auction->assignment->assignment_number }})
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-building text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الجهة المشرفة:</span>
                                <span>{{ $auction->supervising_entity ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-user-check text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">مدير المشروع:</span>
                                <span>{{ $auction->project_manager_name ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-device-laptop text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">منصة المزاد:</span>
                                <span>{{ $auction->platform_name ?? '-' }}</span>
                            </li>
                            @if ($auction->platform_url)
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="ti ti-world text-primary me-2 fs-5"></i>
                                    <span class="fw-bold me-2">رابط المزاد:</span>
                                    <a href="{{ $auction->platform_url }}" target="_blank" class="text-primary">زيارة
                                        المنصة <i class="ti ti-external-link"></i></a>
                                </li>
                            @endif
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-plus text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ البداية:</span>
                                <span>@date($auction->start_date) {{ $auction->start_time }}</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-calendar-due text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ النهاية:</span>
                                <span>@date($auction->end_date) {{ $auction->end_time }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- كارت ملفات وتقارير المزاد --}}
            @php
                $gen = $auction->latestReportGeneration;
                $published = $auction->publishedReportGeneration;
                $isProcessing = (bool) $gen?->isProcessing();
                $hasPublishedOutputs = (bool) ($published?->isReady() && filled($published->outputs_path));
                $hasPublishedReport = (bool) ($published?->isReady() && filled($published->report_path));
                $prepStatus = \App\Enums\Tenants\Auctions\AuctionFilePreparationStatus::resolve(
                    $gen?->status,
                    $hasPublishedOutputs || $hasPublishedReport,
                );
                $isReady = $prepStatus === \App\Enums\Tenants\Auctions\AuctionFilePreparationStatus::READY;
                $isFailed = !$isProcessing && (bool) $gen?->isFailed();
                $canDownloadOutputs = !$isProcessing && $hasPublishedOutputs;
                $canDownloadReport = !$isProcessing && $hasPublishedReport;
                $initialProgress = $gen ? max(0, min(100, (int) ($gen->progress ?? 0))) : 0;
            @endphp
            <div class="card mb-4" id="auction-reports-card">
                <div class="card-header pb-2 mb-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-file-analytics text-primary me-2"></i>ملفات وتقارير المزاد
                    </h6>
                    <div id="reports-header-badge">
                        {!! $prepStatus->badgeHtml() !!}
                    </div>
                </div>
                <div class="card-body">
                    {{-- تنبيه الفشل --}}
                    <div id="reports-error-alert" class="text-danger small mb-3 {{ $isFailed ? '' : 'd-none' }}">
                        <i class="ti ti-alert-circle me-1"></i>
                        @include('tenant.auctions.partials.report-error', ['generation' => $gen])
                    </div>

                    {{-- تنبيه انتهاء مهلة الإعداد التلقائي --}}
                    <div id="reports-timeout-alert" class="alert alert-warning p-2 small mb-3 d-none">
                        <i class="ti ti-clock-exclamation me-1"></i>
                        <span id="reports-timeout-text">انتهت المهلة المحددة لتحضير التقارير تلقائياً. يمكنك الضغط على زر
                            توليد الملفات للمحاولة مرة أخرى.</span>
                    </div>

                    {{-- شريط تقدم التوليد وعرض النسبة المئوية --}}
                    <div id="reports-loading-box"
                        class="p-3 mb-3 border rounded bg-light-subtle {{ $isProcessing ? '' : 'd-none' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small text-primary" id="reports-loading-title">
                                <i class="ti ti-loader ti-spin me-1"></i>جارٍ تحضير وتجهيز تقارير المزاد...
                                <span id="reports-stage-label" class="text-muted fw-normal"></span>
                            </span>
                            <span class="fw-bold text-primary small"
                                id="reports-progress-percent">{{ $initialProgress }}%</span>
                        </div>
                        <div class="progress" style="height: 10px;" role="progressbar"
                            aria-valuenow="{{ $initialProgress }}" aria-valuemin="0" aria-valuemax="100">
                            <div id="reports-progress-bar"
                                class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                style="width: {{ $initialProgress }}%;"></div>
                        </div>
                    </div>

                    <div class="d-grid gap-2" id="reports-actions-group">
                        <a href="{{ $canDownloadOutputs ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'outputs']) : 'javascript:void(0)' }}"
                            id="btn-download-outputs"
                            data-download-url="{{ tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'outputs']) }}"
                            class="btn btn-primary d-flex align-items-center justify-content-between {{ !$canDownloadOutputs ? 'disabled' : '' }}"
                            @if (!$canDownloadOutputs) disabled aria-disabled="true" @else aria-disabled="false" @endif>
                            <span><i class="ti ti-file-text me-2"></i>تحميل مخرجات المزاد</span>
                            <i class="ti ti-download"></i>
                        </a>

                        <a href="{{ $canDownloadReport ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'report']) : 'javascript:void(0)' }}"
                            id="btn-download-report"
                            data-download-url="{{ tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'report']) }}"
                            class="btn btn-success d-flex align-items-center justify-content-between {{ !$canDownloadReport ? 'disabled' : '' }}"
                            @if (!$canDownloadReport) disabled aria-disabled="true" @else aria-disabled="false" @endif>
                            <span><i class="ti ti-file-analytics me-2"></i>تحميل تقرير المزاد</span>
                            <i class="ti ti-download"></i>
                        </a>

                        @can('auctions.edit')
                            <button type="button" id="btn-retry-reports" class="btn btn-outline-primary mt-2"
                                @disabled($isProcessing) @if ($isProcessing) aria-disabled="true" @endif>
                                <i id="btn-retry-icon"
                                    class="ti {{ $isProcessing ? 'ti-loader ti-spin' : 'ti-refresh' }} me-1"></i>
                                <span id="btn-retry-text">
                                    {{ $isProcessing ? 'جارٍ التوليد...' : ($isReady || $hasPublishedOutputs || $hasPublishedReport ? 'إعادة توليد الملفات' : ($isFailed ? 'إعادة محاولة التجهيز' : 'بدء تجهيز ملفات المزاد')) }}
                                </span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            @include('tenant.auctions.partials.regulatory-forms')
        </div>

        {{-- تفاصيل المزاد والدورة (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- العقارات المدرجة --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-building-estate text-primary me-2"></i>العقارات المدرجة في المزاد
                        ({{ $auction->properties->count() }})
                    </h5>
                    <span class="badge bg-label-primary">{{ $auction->properties->count() }} عقار</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>رقم الأصل</th>
                                <th>اسم العقار</th>
                                <th>النوع</th>
                                <th>المساحة</th>
                                <th>القيمة التقديرية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auction->properties as $prop)
                                <tr>
                                    <td><code>{{ $prop->property_number }}</code></td>
                                    <td>
                                        <a href="{{ tenant_route_name('properties.show', $prop->id) }}"
                                            class="fw-medium text-body">
                                            {{ $prop->name }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-label-info">{{ $prop->type_label }}</span></td>
                                    <td>{{ number_format((float) $prop->area) }} م²</td>
                                    <td>{{ number_format((float) $prop->estimated_value) }} ر.س</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">لم يتم إدراج عقارات في هذا
                                        المزاد
                                        بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @include('tenant.auctions.partials.establishment-request')

            @php
                $auctionFileGroups = [];

                $coverItems = [];
                if ($auction->cover_image) {
                    $coverExt = pathinfo($auction->cover_image, PATHINFO_EXTENSION) ?: 'jpg';
                    $coverItems[] = [
                        'name' => 'صورة غلاف المزاد.' . $coverExt,
                        'url' => tasset($auction->cover_image),
                        'meta' => 'غلاف المزاد',
                        'isImage' => true,
                    ];
                }
                if ($auction->report_image) {
                    $reportExt = pathinfo($auction->report_image, PATHINFO_EXTENSION) ?: 'jpg';
                    $coverItems[] = [
                        'name' => 'صورة غلاف التقرير.' . $reportExt,
                        'url' => tasset($auction->report_image),
                        'meta' => 'غلاف التقرير',
                        'isImage' => true,
                    ];
                }
                if (!empty($coverItems)) {
                    $auctionFileGroups[] = [
                        'label' => 'أغلفة المزاد والتقرير',
                        'items' => $coverItems,
                    ];
                }

                $allAuctionAttachments = $auction->attachments;
                $docItems = $allAuctionAttachments->where('category', 'auction_doc')->values();
                if ($docItems->isNotEmpty()) {
                    $auctionFileGroups[] = [
                        'label' => 'وثائق ومرفقات المزاد',
                        'items' => $docItems,
                    ];
                }

                $boardBeforeItems = $allAuctionAttachments
                    ->where('report_section', 'board_before')
                    ->map(
                        fn($att) => [
                            'file' => $att,
                            'meta' => $att->property?->name ?: 'لوحة قبل المزاد',
                        ],
                    )
                    ->values()
                    ->all();
                if (!empty($boardBeforeItems)) {
                    $auctionFileGroups[] = [
                        'label' => 'صور اللوحات قبل المزاد',
                        'items' => $boardBeforeItems,
                    ];
                }

                $boardAfterItems = $allAuctionAttachments
                    ->where('report_section', 'board_after')
                    ->map(
                        fn($att) => [
                            'file' => $att,
                            'meta' => $att->property?->name ?: 'لوحة بعد المزاد',
                        ],
                    )
                    ->values()
                    ->all();
                if (!empty($boardAfterItems)) {
                    $auctionFileGroups[] = [
                        'label' => 'صور اللوحات بعد المزاد',
                        'items' => $boardAfterItems,
                    ];
                }

                $channelLabels = __('auction_reports.channels', [], 'ar') ?: [];
                $marketingItems = $allAuctionAttachments
                    ->filter(
                        fn($att) => $att->report_section === 'marketing_evidence' ||
                            ($att->category === 'marketing' &&
                                !in_array($att->report_section, ['board_before', 'board_after'], true)),
                    )
                    ->map(function ($att) use ($channelLabels) {
                        $channelKey = $att->report_metadata['channel'] ?? null;
                        return [
                            'file' => $att,
                            'meta' =>
                                $channelKey && isset($channelLabels[$channelKey])
                                    ? $channelLabels[$channelKey]
                                    : $att->category_label,
                        ];
                    })
                    ->values()
                    ->all();
                if (!empty($marketingItems)) {
                    $auctionFileGroups[] = [
                        'label' => 'الحملات التسويقية',
                        'items' => $marketingItems,
                    ];
                }

                $handledAttachmentIds = $docItems
                    ->pluck('id')
                    ->merge(
                        $allAuctionAttachments
                            ->whereIn('report_section', ['board_before', 'board_after', 'marketing_evidence'])
                            ->pluck('id'),
                    )
                    ->merge($allAuctionAttachments->where('category', 'marketing')->pluck('id'))
                    ->unique();

                $remainingAttachments = $allAuctionAttachments
                    ->reject(fn($att) => $handledAttachmentIds->contains($att->id))
                    ->map(
                        fn($att) => [
                            'file' => $att,
                            'meta' => $att->category_label,
                        ],
                    )
                    ->values()
                    ->all();
                if (!empty($remainingAttachments)) {
                    $auctionFileGroups[] = [
                        'label' => 'ملفات ومرفقات أخرى',
                        'items' => $remainingAttachments,
                    ];
                }

                $establishmentRecord = $auction->establishmentRequest;
                if (
                    $establishmentRecord &&
                    !empty($establishmentRecord->data['attachments']) &&
                    auth('tenant')->user()?->can('auction_establishment_requests.view')
                ) {
                    $estTypeLabels = app(
                        \App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData::class,
                    )->attachmentTypes();
                    $rawEstAttachments = $establishmentRecord->data['attachments'];
                    $orderedEstTypes = array_unique(
                        array_merge(array_keys($estTypeLabels), array_keys($rawEstAttachments)),
                    );
                    $establishmentItems = [];

                    foreach ($orderedEstTypes as $estType) {
                        foreach ($rawEstAttachments[$estType] ?? [] as $propertyId => $estFile) {
                            $estUrl = tenant_route_name('auction-establishment-requests.attachment', [
                                'auction' => $auction,
                                'type' => $estType,
                                'property' => $propertyId ?: null,
                            ]);
                            $estTypeLabel = $estTypeLabels[$estType] ?? $estType;
                            $estPropName = $propertyId
                                ? $auction->properties->firstWhere('id', (int) $propertyId)?->name
                                : null;
                            $establishmentItems[] = [
                                'name' => $estFile['name'] ?? $estTypeLabel,
                                'url' => $estUrl,
                                'downloadUrl' => $estUrl,
                                'meta' => $estPropName ? $estTypeLabel . ' — ' . $estPropName : $estTypeLabel,
                            ];
                        }
                    }

                    if (!empty($establishmentItems)) {
                        $auctionFileGroups[] = [
                            'label' => 'مرفقات طلب إقامة المزاد',
                            'items' => $establishmentItems,
                        ];
                    }
                }

                $totalAuctionFilesCount = collect($auctionFileGroups)->sum(fn($group) => count($group['items']));
            @endphp

            @include('tenant.auctions.partials.cycle-details', [
                'hasFollowingCard' => $totalAuctionFilesCount > 0,
            ])

            @if ($totalAuctionFilesCount > 0)
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-paperclip text-primary me-2"></i>ملفات ومرفقات المزاد
                        </h5>
                        <span class="badge bg-label-primary">{{ $totalAuctionFilesCount }}</span>
                    </div>
                    <x-ui.file-groups :groups="$auctionFileGroups" layout="card" />
                </div>
            @endif
        </div>
    </div>
@endsection

@section('page-script')
    @include('tenant.auctions.partials._generation-helpers')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ui = window.AuctionGenerationUI;
            const statusUrl = @json(tenant_route_name('auctions.reports.status', $auction->id));
            const retryUrl = @json(tenant_route_name('auctions.reports.retry', $auction->id));
            const csrfToken = '{{ csrf_token() }}';

            // مهلة التوليد: التلقائي 45 ثانية، واليدوي 68 ثانية (أكبر بـ 50%)
            const AUTO_TIMEOUT_MS = 45000;
            const MANUAL_TIMEOUT_MS = 68000;

            const loadingBox = document.getElementById('reports-loading-box');
            const progressBar = document.getElementById('reports-progress-bar');
            const progressPercent = document.getElementById('reports-progress-percent');
            const stageLabel = document.getElementById('reports-stage-label');
            const timeoutAlert = document.getElementById('reports-timeout-alert');
            const errorAlert = document.getElementById('reports-error-alert');
            const errorText = document.getElementById('reports-error-text');
            const headerBadge = document.getElementById('reports-header-badge');
            const retryBtn = document.getElementById('btn-retry-reports');
            const retryIcon = document.getElementById('btn-retry-icon');
            const retryText = document.getElementById('btn-retry-text');
            const btnOutputs = document.getElementById('btn-download-outputs');
            const btnReport = document.getElementById('btn-download-report');

            const REPORT_STAGES = [{
                    min: 0,
                    label: '(في انتظار بدء المعالجة)'
                },
                {
                    min: 5,
                    label: '(تمت الجدولة)'
                },
                {
                    min: 10,
                    label: '(بدء المعالجة)'
                },
                {
                    min: 20,
                    label: '(تجهيز البيانات)'
                },
                {
                    min: 30,
                    label: '(إعداد مخرجات المزاد PDF)'
                },
                {
                    min: 60,
                    label: '(تم إعداد المخرجات — إعداد التقرير النهائي)'
                },
                {
                    min: 70,
                    label: '(توليد التقرير النهائي PDF)'
                },
                {
                    min: 90,
                    label: '(التحقق وحفظ الملفات)'
                },
                {
                    min: 100,
                    label: '(اكتمل التجهيز)'
                },
            ];

            const tracker = ui.createProgressTracker({
                barEl: progressBar,
                percentEl: progressPercent,
                stageEl: stageLabel,
                stages: REPORT_STAGES,
                initialGenerationId: @json($gen?->id),
                initialProgress: @json($initialProgress),
            });

            let lastKnownPublished = {
                has_outputs: @json($hasPublishedOutputs),
                has_report: @json($hasPublishedReport),
                outputs_download_url: btnOutputs?.dataset.downloadUrl || null,
                report_download_url: btnReport?.dataset.downloadUrl || null,
            };

            function setDownloadButtonState(btn, enabled, url) {
                if (!btn) return;
                if (enabled && url) {
                    btn.classList.remove('disabled');
                    btn.removeAttribute('disabled');
                    btn.setAttribute('aria-disabled', 'false');
                    btn.href = url;
                } else {
                    btn.classList.add('disabled');
                    btn.setAttribute('disabled', 'disabled');
                    btn.setAttribute('aria-disabled', 'true');
                    btn.href = 'javascript:void(0)';
                }
            }

            function syncDownloadButtons(data) {
                if (data) {
                    lastKnownPublished = {
                        has_outputs: Boolean(data.has_outputs),
                        has_report: Boolean(data.has_report),
                        outputs_download_url: data.outputs_download_url || btnOutputs?.dataset.downloadUrl || null,
                        report_download_url: data.report_download_url || btnReport?.dataset.downloadUrl || null,
                    };
                }
                setDownloadButtonState(btnOutputs, lastKnownPublished.has_outputs, lastKnownPublished.outputs_download_url);
                setDownloadButtonState(btnReport, lastKnownPublished.has_report, lastKnownPublished.report_download_url);
            }

            function disableDownloadButtons() {
                setDownloadButtonState(btnOutputs, false, null);
                setDownloadButtonState(btnReport, false, null);
            }

            function setProcessingUI() {
                if (retryBtn) {
                    retryBtn.disabled = true;
                    retryBtn.setAttribute('aria-disabled', 'true');
                }
                if (retryIcon) {
                    retryIcon.className = 'ti ti-loader ti-spin me-1';
                }
                if (retryText) {
                    retryText.textContent = 'جارٍ التوليد...';
                }
                if (timeoutAlert) {
                    timeoutAlert.classList.add('d-none');
                }
                if (errorAlert) {
                    errorAlert.classList.add('d-none');
                }
                if (loadingBox) {
                    loadingBox.classList.remove('d-none');
                }
                ui.renderBadge(headerBadge, 'not_prepared');
                disableDownloadButtons();
            }

            function resetRetryButton(text = 'إعادة محاولة التجهيز') {
                if (retryBtn) {
                    retryBtn.disabled = false;
                    retryBtn.removeAttribute('aria-disabled');
                }
                if (retryIcon) {
                    retryIcon.className = 'ti ti-refresh me-1';
                }
                if (retryText) {
                    retryText.textContent = text;
                }
            }

            let pollInterval = null;
            let pollInFlight = false;

            function stopPolling() {
                if (pollInterval) {
                    clearInterval(pollInterval);
                    pollInterval = null;
                }
                pollInFlight = false;
            }

            function startPolling(timeoutLimit, isManualRetry = false, pollImmediately = true) {
                stopPolling();
                setProcessingUI();

                const startTime = Date.now();

                async function pollOnce() {
                    const elapsed = Date.now() - startTime;
                    if (elapsed >= timeoutLimit) {
                        stopPolling();

                        const errorMsg = isManualRetry ?
                            'انتهت المهلة المحددة للتوليد اليدوي لتقارير المزاد. يمكنك المحاولة مجدداً.' :
                            'انتهت المهلة المحددة لتحضير التقارير تلقائياً. يمكنك الضغط على زر توليد الملفات للمحاولة مرة أخرى.';

                        if (window.toastr) {
                            toastr.error(errorMsg);
                        }
                        if (loadingBox) {
                            loadingBox.classList.add('d-none');
                        }
                        if (timeoutAlert) {
                            timeoutAlert.classList.remove('d-none');
                        }
                        ui.renderBadge(headerBadge, 'not_prepared');
                        syncDownloadButtons(null);
                        resetRetryButton(lastKnownPublished.has_outputs || lastKnownPublished.has_report ?
                            'إعادة توليد الملفات' :
                            'إعادة محاولة التجهيز');
                        return;
                    }

                    if (pollInFlight) {
                        return;
                    }
                    pollInFlight = true;

                    try {
                        const res = await fetch(statusUrl, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        });
                        if (!res.ok) {
                            return;
                        }
                        const data = await res.json();

                        if (data.status === 'processing' || data.status === 'queued') {
                            setProcessingUI();
                            if (data.progress !== undefined && data.progress !== null) {
                                tracker.update(data.progress, data.generation_id);
                            }
                        } else if (data.status === 'ready') {
                            stopPolling();
                            tracker.update(100, data.generation_id);
                            ui.renderBadge(headerBadge, 'ready');
                            syncDownloadButtons(data);

                            if (window.toastr) {
                                toastr.success('تم تحضير وتجهيز تقارير المزاد بنجاح!');
                            }
                            setTimeout(() => location.reload(), 900);
                        } else if (data.status === 'failed') {
                            stopPolling();
                            if (window.toastr) {
                                toastr.error(data.error_message || 'فشل تجهيز ملفات المزاد.');
                            }
                            if (loadingBox) {
                                loadingBox.classList.add('d-none');
                            }
                            if (errorAlert) {
                                if (errorText) {
                                    errorText.textContent = data.error_message || 'تعذر تجهيز ملفات المزاد.';
                                }
                                errorAlert.classList.remove('d-none');
                            }
                            ui.renderBadge(headerBadge, 'failed');
                            syncDownloadButtons(data);
                            resetRetryButton('إعادة محاولة التجهيز');
                        }
                    } catch (_) {} finally {
                        pollInFlight = false;
                    }
                }

                if (pollImmediately) {
                    pollOnce();
                }
                pollInterval = setInterval(pollOnce, 2000);
            }

            async function startReportGeneration() {
                stopPolling();
                tracker.reset(0, null);
                setProcessingUI();
                if (retryText) {
                    retryText.textContent = 'جارٍ بدء التوليد...';
                }

                try {
                    const res = await fetch(retryUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json().catch(() => ({}));

                    if (res.ok && data.success) {
                        if (data.status === 'ready') {
                            tracker.update(100, data.generation_id);
                            ui.renderBadge(headerBadge, 'ready');
                            syncDownloadButtons(data);
                            if (window.toastr) {
                                toastr.success(data.message || 'تم تحضير وتجهيز تقارير المزاد بنجاح!');
                            }
                            setTimeout(() => location.reload(), 700);
                            return;
                        }

                        if (window.toastr) {
                            toastr.info(data.message || 'تم بدء تجهيز ملفات المزاد.');
                        }

                        tracker.update(data.progress ?? 0, data.generation_id);
                        startPolling(MANUAL_TIMEOUT_MS, true, false);
                    } else {
                        if (loadingBox) {
                            loadingBox.classList.add('d-none');
                        }
                        if (window.toastr) {
                            toastr.error(data.message || 'فشل تجهيز ملفات المزاد.');
                        }
                        if (errorAlert) {
                            if (errorText) {
                                errorText.textContent = data.message || 'تعذر تجهيز ملفات المزاد.';
                            }
                            errorAlert.classList.remove('d-none');
                        }
                        ui.renderBadge(headerBadge, 'failed');
                        syncDownloadButtons(data.has_outputs !== undefined ? data : null);
                        resetRetryButton('إعادة محاولة التجهيز');
                    }
                } catch (_) {
                    if (loadingBox) {
                        loadingBox.classList.add('d-none');
                    }
                    if (window.toastr) {
                        toastr.error('حدث خطأ في الاتصال بالخادم.');
                    }
                    ui.renderBadge(headerBadge, 'failed');
                    syncDownloadButtons(null);
                    resetRetryButton('إعادة محاولة التجهيز');
                }
            }

            if (retryBtn) {
                retryBtn.addEventListener('click', startReportGeneration);
            }

            window.addEventListener('pagehide', stopPolling, {
                once: true
            });

            // متابعة الإصدار الموجود بعد ظهور التفاصيل؛ طلب الحالة يبدأ الإصدار المنتظر دون إنشاء نسخة إضافية.
            @if ($isProcessing)
                tracker.reset(@json($initialProgress), @json($gen?->id));
                startPolling(AUTO_TIMEOUT_MS);
            @endif

            // متابعة حالة الإعلانات الترويجية للمزاد
            const promoStatusUrl = @json(tenant_route_name('auctions.promotional-advertisements.status', $auction->id));
            const hasProcessingAds = document.querySelectorAll(
                '[data-ad-status="processing"], [data-ad-status="pending"]').length > 0;

            if (hasProcessingAds) {
                let promoPollInterval = setInterval(function() {
                    fetch(promoStatusUrl, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (!data || !data.advertisements) return;

                            let anyProcessing = false;
                            data.advertisements.forEach(ad => {
                                const row = document.querySelector(`tr[data-ad-id="${ad.id}"]`);
                                if (!row) return;

                                row.setAttribute('data-ad-status', ad.status);
                                const statusCell = row.querySelector('[data-ad-status-cell]');
                                const actionCell = row.querySelector('[data-ad-action-cell]');

                                if (statusCell) {
                                    statusCell.innerHTML =
                                        `<span class="badge bg-${ad.status_badge}">${ad.status_label}</span>`;
                                }

                                if (actionCell) {
                                    if (ad.is_ready && ad.download_url) {
                                        actionCell.innerHTML =
                                            `<a href="${ad.download_url}" class="btn btn-sm btn-primary"><i class="ti ti-download me-1"></i>تحميل الصورة</a>`;
                                    } else if (ad.is_processing) {
                                        anyProcessing = true;
                                        actionCell.innerHTML =
                                            `<button class="btn btn-sm btn-outline-primary" disabled><i class="ti ti-loader ti-spin me-1"></i>جارٍ التجهيز...</button>`;
                                    } else if (ad.is_failed) {
                                        actionCell.innerHTML =
                                            `<span class="text-danger small" title="${ad.error_message || ''}"><i class="ti ti-alert-triangle me-1"></i>فشل التجهيز</span>`;
                                    }
                                }
                            });

                            if (data.all_ready || !anyProcessing) {
                                clearInterval(promoPollInterval);
                                promoPollInterval = null;
                                if (window.toastr) {
                                    toastr.success('تم اكتمال وتجهيز صورة الإعلان بنجاح!');
                                }
                            }
                        })
                        .catch(() => {});
                }, 3000);
            }
        });
    </script>
@endsection
