@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل المزاد - ' . $auction->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-gavel me-2 text-primary"></i>تفاصيل المزاد: {{ $auction->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <code class="copyable-code fs-6"
                    onclick="navigator.clipboard.writeText('AUC-{{ str_pad((string) $auction->id, 5, '0', STR_PAD_LEFT) }}'); toastr.success('تم نسخ رقم المزاد');">
                    AUC-{{ str_pad((string) $auction->id, 5, '0', STR_PAD_LEFT) }}
                </code>
                <span class="badge {{ $auction->status_badge_class }}">{{ $auction->status_label }}</span>
                <span class="badge bg-info">{{ $auction->type_label }}</span>
                @if ($auction->city)
                    <span class="badge bg-secondary"><i class="ti ti-map-pin me-1"></i>{{ $auction->city }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('auctions.edit')
                <a href="{{ tenant_route_name('auctions.edit', $auction->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل المزاد
                </a>
            @endcan
            <a href="{{ tenant_route_name('auctions.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت الغلاف والمعلومات الرئيسية --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3 text-center">
                        <img src="{{ $auction->cover_image ? tasset($auction->cover_image) : asset('assets/img/illustrations/page-pricing-enterprise.png') }}"
                            alt="{{ $auction->name }}" class="rounded-circle"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="mb-1">{{ $auction->name }}</h5>
                    <span class="badge bg-primary mb-3">{{ $auction->category_label }}</span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ $auction->properties->count() }}</h5>
                            <small class="text-muted">العقارات</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ number_format((float) $auction->properties->sum('estimated_value')) }}
                            </h5>
                            <small class="text-muted">إجمالي التقدير (ر.س)</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            @if ($auction->description)
                                <li class="mb-2"><span class="fw-bold me-1">الوصف:</span>
                                    <span class="text-muted small">{{ $auction->description }}</span>
                                </li>
                            @endif
                            <li class="mb-2"><span class="fw-bold me-1">الإسناد المرتبط:</span>
                                @if ($auction->assignment)
                                    <a href="{{ tenant_route_name('assignments.show', $auction->assignment_id) }}"
                                        class="text-primary fw-medium">
                                        {{ $auction->assignment->name }} ({{ $auction->assignment->assignment_number }})
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </li>
                            <li class="mb-2"><span class="fw-bold me-1">الجهة المشرفة:</span>
                                <span>{{ $auction->supervising_entity ?? '-' }}</span>
                            </li>
                            <li class="mb-2"><span class="fw-bold me-1">مدير المشروع:</span>
                                <span>{{ $auction->project_manager_name ?? '-' }}</span>
                            </li>
                            <li class="mb-2"><span class="fw-bold me-1">منصة المزاد:</span>
                                <span>{{ $auction->platform_name ?? '-' }}</span>
                            </li>
                            @if ($auction->platform_url)
                                <li class="mb-2"><span class="fw-bold me-1">رابط المزاد:</span>
                                    <a href="{{ $auction->platform_url }}" target="_blank" class="text-primary">زيارة
                                        المنصة <i class="ti ti-external-link"></i></a>
                                </li>
                            @endif
                            <li class="mb-2"><span class="fw-bold me-1">تاريخ البداية:</span>
                                <span>{{ $auction->start_date?->format('Y-m-d') }} {{ $auction->start_time }}</span>
                            </li>
                            <li class="mb-0"><span class="fw-bold me-1">تاريخ النهاية:</span>
                                <span>{{ $auction->end_date?->format('Y-m-d') }} {{ $auction->end_time }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- كارت ملفات وتقارير المزاد --}}
            @php
                $gen = $auction->latestReportGeneration;
                $isReady = $gen?->isReady();
                $isProcessing = $gen?->isProcessing();
                $isFailed = $gen?->isFailed();
                $initialProgress = $gen ? (int) ($gen->progress ?? 0) : 0;
            @endphp
            <div class="card mb-4 shadow-sm border-primary" id="auction-reports-card">
                <div class="card-header bg-primary py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 text-white fw-bold">
                        <i class="ti ti-file-analytics me-1"></i>ملفات وتقارير المزاد
                    </h6>
                    <div id="reports-header-badge">
                        @if ($isReady)
                            <span class="badge bg-success">جاهز للتحميل</span>
                        @elseif($isProcessing)
                            <span class="badge bg-warning"><i class="ti ti-loader ti-spin me-1"></i>قيد الإعداد</span>
                        @elseif($isFailed)
                            <span class="badge bg-danger">فشل الإعداد</span>
                        @else
                            <span class="badge bg-secondary">غير مجهز بعد</span>
                        @endif
                    </div>
                </div>
                <div class="card-body pt-3">
                    {{-- تنبيه الفشل --}}
                    <div id="reports-error-alert" class="alert alert-danger p-2 small mb-3 {{ $isFailed ? '' : 'd-none' }}">
                        <i class="ti ti-alert-circle me-1"></i>
                        <span id="reports-error-text">{{ $gen->error_message ?? 'تعذر تجهيز الملفات تلقائياً.' }}</span>
                    </div>

                    {{-- تنبيه انتهاء مهلة الإعداد التلقائي --}}
                    <div id="reports-timeout-alert" class="alert alert-warning p-2 small mb-3 d-none">
                        <i class="ti ti-clock-exclamation me-1"></i>
                        <span id="reports-timeout-text">انتهت المهلة المحددة لتحضير التقارير تلقائياً. يمكنك الضغط على زر توليد الملفات للمحاولة مرة أخرى.</span>
                    </div>

                    {{-- واجهة التحميل الأكبر وعرض النسبة المئوية الحقيقية --}}
                    <div id="reports-loading-box" class="p-3 mb-3 border rounded bg-lighter text-center {{ $isProcessing ? '' : 'd-none' }}">
                        <div class="mb-2">
                            <div class="spinner-border text-primary" role="status" style="width: 2.8rem; height: 2.8rem;">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <h6 class="fw-bold mb-2 text-primary fs-6" id="reports-loading-title">
                            برجاء الانتظار، جار تحضير تقارير المزاد
                        </h6>
                        <div class="progress mb-2" style="height: 14px;" role="progressbar" aria-valuenow="{{ $initialProgress }}" aria-valuemin="0" aria-valuemax="100">
                            <div id="reports-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                style="width: {{ max(6, $initialProgress) }}%;"></div>
                        </div>
                        <div class="d-flex justify-content-between text-muted small px-1">
                            <span>نسبة الإنجاز:</span>
                            <span class="fw-bold text-primary" id="reports-progress-percent">{{ $initialProgress }}%</span>
                        </div>
                    </div>

                    <div class="d-grid gap-2" id="reports-actions-group">
                        <a href="{{ $isReady && filled($gen->outputs_path) ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'outputs']) : 'javascript:void(0)' }}"
                            id="btn-download-outputs"
                            class="btn btn-primary d-flex align-items-center justify-content-between {{ !$isReady || blank($gen->outputs_path) ? 'disabled' : '' }}"
                            @if (!$isReady || blank($gen->outputs_path)) disabled aria-disabled="true" @endif>
                            <span><i class="ti ti-file-text me-2"></i>تحميل مخرجات المزاد</span>
                            <i class="ti ti-download"></i>
                        </a>

                        <a href="{{ $isReady && filled($gen->report_path) ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'report']) : 'javascript:void(0)' }}"
                            id="btn-download-report"
                            class="btn btn-success d-flex align-items-center justify-content-between {{ !$isReady || blank($gen->report_path) ? 'disabled' : '' }}"
                            @if (!$isReady || blank($gen->report_path)) disabled aria-disabled="true" @endif>
                            <span><i class="ti ti-file-analytics me-2"></i>تحميل تقرير المزاد</span>
                            <i class="ti ti-download"></i>
                        </a>

                        @can('auctions.edit')
                            <button type="button" id="btn-retry-reports" class="btn btn-outline-primary mt-2 {{ $isProcessing ? 'd-none' : '' }}">
                                <i class="ti ti-refresh me-1"></i>
                                <span id="btn-retry-text">
                                    {{ $isReady ? 'إعادة توليد الملفات' : ($isFailed ? 'إعادة محاولة الإعداد' : 'بدء تجهيز ملفات المزاد') }}
                                </span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>

            @if ($auction->attachments->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h6 class="card-title mb-0">وثائق ومرفقات المزاد</h6>
                    </div>
                    <div class="card-body">
                        <div class="assignment-file-list d-flex flex-wrap gap-2">
                            @foreach ($auction->attachments as $attachment)
                                <x-ui.file-card :file="$attachment" layout="card" />
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- تفاصيل العقارات المشمولة --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- العقارات المدرجة --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">العقارات المدرجة في المزاد ({{ $auction->properties->count() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم العقار</th>
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
                                    <td><span class="badge bg-info">{{ $prop->type_label }}</span></td>
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
            const retryText = document.getElementById('btn-retry-text');

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

                const startTime = Date.now();

                pollInterval = setInterval(function() {
                    const elapsed = Date.now() - startTime;

                    if (elapsed >= timeoutLimit) {
                        clearInterval(pollInterval);
                        pollInterval = null;

                        // إظهار الخطأ في Toastr وإيقاف الإنشاء التلقائي
                        const errorMsg = isManualRetry
                            ? 'انتهت المهلة المحددة للتوليد اليدوي لتقارير المزاد. يمكنك المحاولة مجدداً.'
                            : 'انتهت المهلة المحددة لتحضير التقارير تلقائياً. يمكنك الضغط على زر توليد الملفات للمحاولة مرة أخرى.';

                        if (window.toastr) {
                            toastr.error(errorMsg);
                        }

                        if (loadingBox) {
                            loadingBox.classList.add('d-none');
                        }
                        if (timeoutAlert) {
                            timeoutAlert.classList.remove('d-none');
                        }
                        if (retryBtn) {
                            retryBtn.classList.remove('d-none');
                            retryBtn.disabled = false;
                            if (retryText) {
                                retryText.textContent = 'توليد ملفات المزاد الآن';
                            }
                        }
                        if (headerBadge) {
                            headerBadge.innerHTML = '<span class="badge bg-warning">انتهت المهلة</span>';
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
                            if (data.progress !== undefined && data.progress !== null) {
                                updateProgressUI(data.progress);
                            }
                        } else if (data.status === 'ready') {
                            clearInterval(pollInterval);
                            pollInterval = null;
                            updateProgressUI(100);

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
                                    errorText.textContent = data.error_message || 'تعذر تجهيز الملفات تلقائياً.';
                                }
                                errorAlert.classList.remove('d-none');
                            }
                            if (retryBtn) {
                                retryBtn.classList.remove('d-none');
                                retryBtn.disabled = false;
                                if (retryText) {
                                    retryText.textContent = 'إعادة محاولة الإعداد';
                                }
                            }
                            if (headerBadge) {
                                headerBadge.innerHTML = '<span class="badge bg-danger">فشل الإعداد</span>';
                            }
                        }
                    })
                    .catch(() => {});
                }, 2000);
            }

            if (retryBtn) {
                retryBtn.addEventListener('click', function() {
                    retryBtn.disabled = true;
                    if (retryText) {
                        retryText.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> جارٍ بدء التوليد...';
                    }

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
                        if (data.success) {
                            if (window.toastr) {
                                toastr.info(data.message || 'تم بدء تجهيز ملفات المزاد.');
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
                                headerBadge.innerHTML = '<span class="badge bg-warning"><i class="ti ti-loader ti-spin me-1"></i>قيد الإعداد</span>';
                            }

                            updateProgressUI(data.progress || 5);
                            retryBtn.classList.add('d-none');

                            // تشغيل المتابعة بمهلة اليدوي الأطول بنسبة 50%
                            startPolling(MANUAL_TIMEOUT_MS, true);
                        } else {
                            if (window.toastr) {
                                toastr.error(data.message || 'حدث خطأ أثناء بدء التوليد.');
                            }
                            retryBtn.disabled = false;
                            if (retryText) {
                                retryText.textContent = 'إعادة المحاولة';
                            }
                        }
                    })
                    .catch(() => {
                        if (window.toastr) {
                            toastr.error('حدث خطأ في الاتصال بالخادم.');
                        }
                        retryBtn.disabled = false;
                    });
                });
            }

            // إذا كان المزاد قيد الإعداد عند فتح الصفحة، نبدأ الـ polling التلقائي بمهلة AUTO_TIMEOUT_MS
            @if ($auction->latestReportGeneration && in_array($auction->latestReportGeneration->status, ['queued', 'processing']))
                startPolling(AUTO_TIMEOUT_MS, false);
            @endif
        });
    </script>
@endsection
