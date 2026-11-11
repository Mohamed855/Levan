@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل المزاد - ' . $auction->name)


@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ tenant_route_name(auth('tenant')->user()->can('auctions.view') ? 'auctions.index' : (auth('tenant')->user()->can('auction_establishment_requests.view') ? 'auction-establishment-requests.index' : 'auction-closings.index')) }}" class="btn btn-secondary">
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
                        <span class="badge bg-label-{{ $auction->completion_status->color() }} fs-6">حالة الاكتمال: {{ $auction->completion_status->label() }}</span>
                    </div>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ $auction->properties->count() }}</h5>
                            <small class="text-muted">العقارات</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success">{{ number_format((float) $auction->properties->sum('estimated_value')) }}
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

            @include('tenant.auctions.partials.establishment-request')

            {{-- كارت ملفات وتقارير المزاد --}}
            @php
                $gen = $auction->latestReportGeneration;
                $published = $auction->publishedReportGeneration;
                $isProcessing = (bool) $gen?->isProcessing();
                $isReady = ! $isProcessing && (bool) $published?->isReady();
                $isFailed = ! $isProcessing && (bool) $gen?->isFailed();
                $initialProgress = $gen ? (int) ($gen->progress ?? 0) : 0;
            @endphp
            <div class="card mb-4" id="auction-reports-card">
                <div class="card-header pb-2 mb-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-file-analytics text-primary me-2"></i>ملفات وتقارير المزاد
                    </h6>
                    <div id="reports-header-badge">
                        @if ($isProcessing)
                            <span class="badge bg-label-info"><i class="ti ti-loader ti-spin me-1"></i>قيد التوليد</span>
                        @elseif ($isReady)
                            <span class="badge bg-label-success">جاهز للتحميل</span>
                        @elseif ($isFailed)
                            <span class="badge bg-label-danger">فشل الإعداد</span>
                        @else
                            <span class="badge bg-label-secondary">غير مجهز بعد</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    {{-- تنبيه الفشل --}}
                    <div id="reports-error-alert"
                        class="text-danger small mb-3 {{ $isFailed ? '' : 'd-none' }}">
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
                            </span>
                            <span class="fw-bold text-primary small"
                                id="reports-progress-percent">{{ $initialProgress }}%</span>
                        </div>
                        <div class="progress" style="height: 10px;" role="progressbar"
                            aria-valuenow="{{ $initialProgress }}" aria-valuemin="0" aria-valuemax="100">
                            <div id="reports-progress-bar"
                                class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                style="width: {{ max(6, $initialProgress) }}%;"></div>
                        </div>
                    </div>

                    <div class="d-grid gap-2" id="reports-actions-group">
                        <a href="{{ $isReady && filled($published?->outputs_path) ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'outputs']) : 'javascript:void(0)' }}"
                            id="btn-download-outputs"
                            class="btn btn-primary d-flex align-items-center justify-content-between {{ !$isReady || blank($published?->outputs_path) ? 'disabled' : '' }}"
                            @if (!$isReady || blank($published?->outputs_path)) disabled aria-disabled="true" @endif>
                            <span><i class="ti ti-file-text me-2"></i>تحميل مخرجات المزاد</span>
                            <i class="ti ti-download"></i>
                        </a>

                        <a href="{{ $isReady && filled($published?->report_path) ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'report']) : 'javascript:void(0)' }}"
                            id="btn-download-report"
                            class="btn btn-success d-flex align-items-center justify-content-between {{ !$isReady || blank($published?->report_path) ? 'disabled' : '' }}"
                            @if (!$isReady || blank($published?->report_path)) disabled aria-disabled="true" @endif>
                            <span><i class="ti ti-file-analytics me-2"></i>تحميل تقرير المزاد</span>
                            <i class="ti ti-download"></i>
                        </a>

                        @can('auctions.edit')
                            <button type="button" id="btn-retry-reports"
                                class="btn btn-outline-primary mt-2 {{ $isProcessing ? 'd-none' : '' }}">
                                <i id="btn-retry-icon" class="ti ti-refresh me-1"></i>
                                <span id="btn-retry-text">
                                    {{ $isReady ? 'إعادة توليد الملفات' : ($isFailed ? 'إعادة محاولة الإعداد' : 'بدء تجهيز ملفات المزاد') }}
                                </span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            @include('tenant.auctions.partials.regulatory-forms')

            @php
                $auctionDocs = $auction->attachments->where('category', 'auction_doc');
            @endphp
            @if ($auctionDocs->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header pb-2 mb-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-paperclip text-primary me-2"></i>وثائق ومرفقات المزاد
                        </h6>
                        <span class="badge bg-label-primary">{{ $auctionDocs->count() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="assignment-file-list d-flex flex-wrap gap-2">
                            @foreach ($auctionDocs as $attachment)
                                <x-ui.file-card :file="$attachment" layout="card" />
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- تفاصيل العقارات المشمولة (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- العقارات المدرجة --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-building-estate text-primary me-2"></i>العقارات المدرجة في المزاد ({{ $auction->properties->count() }})
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

            @include('tenant.auctions.partials.cycle-details')

        </div>
    </div>
@endsection

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusUrl = @json(tenant_route_name('auctions.reports.status', $auction->id));
            const retryUrl = @json(tenant_route_name('auctions.reports.retry', $auction->id));
            const csrfToken = '{{ csrf_token() }}';

            // مهلة التوليد: التلقائي 45 ثانية، واليدوي 68 ثانية (أكبر بـ 50%)
            const AUTO_TIMEOUT_MS = 45000;
            const MANUAL_TIMEOUT_MS = 68000;

            const loadingBox = document.getElementById('reports-loading-box');
            const progressBar = document.getElementById('reports-progress-bar');
            const progressPercent = document.getElementById('reports-progress-percent');
            const timeoutAlert = document.getElementById('reports-timeout-alert');
            const errorAlert = document.getElementById('reports-error-alert');
            const errorText = document.getElementById('reports-error-text');
            const headerBadge = document.getElementById('reports-header-badge');
            const retryBtn = document.getElementById('btn-retry-reports');
            const retryIcon = document.getElementById('btn-retry-icon');
            const retryText = document.getElementById('btn-retry-text');

            function disableDownloadButtons() {
                const btnOutputs = document.getElementById('btn-download-outputs');
                const btnReport = document.getElementById('btn-download-report');
                if (btnOutputs) {
                    btnOutputs.classList.add('disabled');
                    btnOutputs.setAttribute('disabled', 'disabled');
                    btnOutputs.setAttribute('aria-disabled', 'true');
                    btnOutputs.href = 'javascript:void(0)';
                }
                if (btnReport) {
                    btnReport.classList.add('disabled');
                    btnReport.setAttribute('disabled', 'disabled');
                    btnReport.setAttribute('aria-disabled', 'true');
                    btnReport.href = 'javascript:void(0)';
                }
            }

            function setProcessingUI() {
                if (retryBtn) {
                    retryBtn.disabled = true;
                    retryBtn.classList.add('d-none');
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
                if (headerBadge) {
                    headerBadge.innerHTML =
                        '<span class="badge bg-info"><i class="ti ti-loader ti-spin me-1"></i>قيد التوليد</span>';
                }
                disableDownloadButtons();
            }

            function resetRetryButton(text = 'إعادة المحاولة') {
                if (retryBtn) {
                    retryBtn.disabled = false;
                    retryBtn.classList.remove('d-none');
                }
                if (retryIcon) {
                    retryIcon.className = 'ti ti-refresh me-1';
                }
                if (retryText) {
                    retryText.textContent = text;
                }
            }

            let pollInterval = null;

            function updateProgressUI(pct) {
                const safePct = Math.max(5, Math.min(100, Math.round(pct)));
                if (progressBar) {
                    progressBar.style.width = safePct + '%';
                    progressBar.setAttribute('aria-valuenow', safePct);
                }
                if (progressPercent) {
                    progressPercent.textContent = safePct + '%';
                }
            }

            function startPolling(timeoutLimit, isManualRetry = false) {
                if (pollInterval) {
                    clearInterval(pollInterval);
                }

                setProcessingUI();

                const startTime = Date.now();

                pollInterval = setInterval(function() {
                    const elapsed = Date.now() - startTime;

                    if (elapsed >= timeoutLimit) {
                        clearInterval(pollInterval);
                        pollInterval = null;

                        // إظهار الخطأ في Toastr وإيقاف الإنشاء التلقائي
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
                        resetRetryButton('توليد ملفات المزاد الآن');
                        if (headerBadge) {
                            headerBadge.innerHTML =
                                '<span class="badge bg-danger text-white">انتهت المهلة</span>';
                        }

                        return;
                    }

                    fetch(statusUrl, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'processing' || data.status === 'queued') {
                                setProcessingUI();
                                if (data.progress !== undefined && data.progress !== null) {
                                    updateProgressUI(data.progress);
                                }
                            } else if (data.status === 'ready') {
                                clearInterval(pollInterval);
                                pollInterval = null;
                                updateProgressUI(100);

                                if (headerBadge) {
                                    headerBadge.innerHTML =
                                        '<span class="badge bg-success">جاهز للتحميل</span>';
                                }

                                if (window.toastr) {
                                    toastr.success('تم تحضير وتجهيز تقارير المزاد بنجاح!');
                                }
                                setTimeout(() => location.reload(), 900);
                            } else if (data.status === 'failed') {
                                clearInterval(pollInterval);
                                pollInterval = null;

                                if (window.toastr) {
                                    toastr.error(data.error_message || 'فشل إعداد ملفات المزاد.');
                                }
                                if (loadingBox) {
                                    loadingBox.classList.add('d-none');
                                }
                                if (errorAlert) {
                                    if (errorText) {
                                        errorText.textContent = data.error_message ||
                                            'تعذر تجهيز الملفات تلقائياً.';
                                    }
                                    errorAlert.classList.remove('d-none');
                                }
                                resetRetryButton('إعادة محاولة الإعداد');
                                if (headerBadge) {
                                    headerBadge.innerHTML =
                                        '<span class="badge bg-danger">فشل الإعداد</span>';
                                }
                            }
                        })
                        .catch(() => {});
                }, 2000);
            }

            function startReportGeneration() {
                setProcessingUI();
                if (retryIcon) {
                    retryIcon.className = 'ti ti-loader ti-spin me-1';
                }
                if (retryText) {
                    retryText.textContent = 'جارٍ بدء التوليد...';
                }
                updateProgressUI(10);

                // تقدم تدريجي متحرك أثناء انتظار الاستجابة
                let simulatedProgress = 10;
                const progressTimer = setInterval(() => {
                    if (simulatedProgress < 85) {
                        simulatedProgress += 5;
                        updateProgressUI(simulatedProgress);
                    }
                }, 400);

                fetch(retryUrl, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken,
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        clearInterval(progressTimer);

                        if (data.success) {
                            if (data.status === 'ready') {
                                if (headerBadge) {
                                    headerBadge.innerHTML =
                                        '<span class="badge bg-success">جاهز للتحميل</span>';
                                }
                                updateProgressUI(100);
                                if (window.toastr) {
                                    toastr.success(data.message ||
                                        'تم تحضير وتجهيز تقارير المزاد بنجاح!');
                                }
                                setTimeout(() => location.reload(), 700);
                                return;
                            }

                            if (window.toastr) {
                                toastr.info(data.message || 'تم بدء تجهيز ملفات المزاد.');
                            }

                            updateProgressUI(data.progress || 25);

                            // تشغيل المتابعة بمهلة اليدوي الأطول بنسبة 50%
                            startPolling(MANUAL_TIMEOUT_MS, true);
                        } else {
                            if (loadingBox) {
                                loadingBox.classList.add('d-none');
                            }
                            if (window.toastr) {
                                toastr.error(data.message || 'حدث خطأ أثناء بدء التوليد.');
                            }
                            if (errorAlert) {
                                if (errorText) {
                                    errorText.textContent = data.message || 'تعذر تجهيز الملفات.';
                                }
                                errorAlert.classList.remove('d-none');
                            }
                            resetRetryButton('إعادة المحاولة');
                        }
                    })
                    .catch(() => {
                        clearInterval(progressTimer);
                        if (loadingBox) {
                            loadingBox.classList.add('d-none');
                        }
                        if (window.toastr) {
                            toastr.error('حدث خطأ في الاتصال بالخادم.');
                        }
                        resetRetryButton('إعادة المحاولة');
                    });
            }

            if (retryBtn) {
                retryBtn.addEventListener('click', startReportGeneration);
            }

            // متابعة الإصدار الموجود بعد ظهور التفاصيل؛ طلب الحالة يبدأ الإصدار المنتظر دون إنشاء نسخة إضافية.
            @if ($isProcessing)
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
