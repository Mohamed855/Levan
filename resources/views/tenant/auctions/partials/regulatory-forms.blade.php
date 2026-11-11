@php
    $savedRequest = $auction->establishmentRequest;
    $formService = app(\App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService::class);
    $formTypes = [
        'establishment' => [
            'label' => 'نموذج طلب إقامة مزاد',
            'permission' => 'auction_establishment_requests',
            'icon' => 'ti-file-description',
        ],
        'closing' => [
            'label' => 'نموذج إقفال المزاد',
            'permission' => 'auction_closings',
            'icon' => 'ti-file-check',
        ],
    ];
@endphp

@foreach ($formTypes as $formType => $formOptions)
    @if ($savedRequest?->exists && ($formType === 'establishment' || ($savedRequest->isApproved() && $auction->closing?->saved_at)))
        @can($formOptions['permission'] . '.view')
            @php
                $formGeneration = $formService->latest($auction, $formType);
                $publishedForm = $formService->published($auction, $formType);
                $formProcessing = (bool) $formGeneration?->isProcessing();
                $formReady = !$formProcessing && (bool) $publishedForm;
                $formFailed = !$formProcessing && $formGeneration?->status === 'failed';
                $formProgress = (int) ($formGeneration?->progress ?? 0);
                $formDownload = tenant_route_name('auctions.forms.download', ['auction' => $auction->id, 'type' => $formType]);
            @endphp
            <div class="card mb-4" data-regulatory-form="{{ $formType }}"
                data-status="{{ $formGeneration?->status ?? 'none' }}"
                data-status-url="{{ tenant_route_name('auctions.forms.status', ['auction' => $auction->id, 'type' => $formType]) }}"
                data-retry-url="{{ tenant_route_name('auctions.forms.retry', ['auction' => $auction->id, 'type' => $formType]) }}">
                <div class="card-header pb-2 mb-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="ti {{ $formOptions['icon'] }} text-primary me-2"></i>{{ $formOptions['label'] }}
                    </h6>
                    <span data-form-badge
                        class="badge {{ $formProcessing ? 'bg-label-info' : ($formFailed ? 'bg-label-danger' : ($formReady ? 'bg-label-success' : 'bg-label-secondary')) }}">
                        @if ($formProcessing)
                            <i class="ti ti-loader ti-spin me-1"></i>قيد التوليد
                        @elseif ($formFailed)
                            فشل الإعداد
                        @elseif ($formReady)
                            جاهز للتحميل
                        @else
                            غير مجهز بعد
                        @endif
                    </span>
                </div>
                <div class="card-body">
                    <div data-form-error class="text-danger small mb-3 {{ $formFailed ? '' : 'd-none' }}">
                        <i class="ti ti-alert-circle me-1"></i>
                        <span data-form-error-text>{{ $formGeneration?->publicErrorMessage() }}</span>
                    </div>

                    <div data-form-timeout class="alert alert-warning p-2 small mb-3 d-none">
                        <i class="ti ti-clock-exclamation me-1"></i>
                        <span>انتهت المهلة المحددة لتحضير النموذج تلقائياً. يمكنك الضغط على زر إعادة التوليد للمحاولة مرة أخرى.</span>
                    </div>

                    <div data-form-loading class="p-3 mb-3 border rounded bg-light-subtle {{ $formProcessing ? '' : 'd-none' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="fw-semibold small text-primary">
                                <i class="ti ti-loader ti-spin me-1"></i>جارٍ تحضير وتجهيز النموذج...
                            </span>
                            <span data-form-percent class="fw-bold text-primary small">{{ $formProgress }}%</span>
                        </div>
                        <div class="progress" style="height: 10px;" role="progressbar"
                            aria-valuenow="{{ $formProgress }}" aria-valuemin="0" aria-valuemax="100"
                            aria-label="تقدم تجهيز النموذج">
                            <div data-form-progress
                                class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                                style="width: {{ max(6, $formProgress) }}%;"></div>
                        </div>
                    </div>

                    <div class="d-grid gap-2">
                        <a data-form-download
                            href="{{ $formReady ? $formDownload : 'javascript:void(0)' }}"
                            class="btn btn-primary d-flex align-items-center justify-content-between {{ $formReady ? '' : 'disabled' }}"
                            @if (!$formReady) disabled aria-disabled="true" @else aria-disabled="false" @endif>
                            <span><i class="ti {{ $formOptions['icon'] }} me-2"></i>تحميل {{ $formOptions['label'] }}</span>
                            <i class="ti ti-download"></i>
                        </a>
                        @can($formOptions['permission'] . '.regenerate')
                            <button type="button" data-form-retry
                                class="btn btn-outline-primary mt-2 {{ $formProcessing ? 'd-none' : '' }}">
                                <i data-form-retry-icon class="ti ti-refresh me-1"></i>
                                <span data-form-retry-text>
                                    {{ $publishedForm ? 'إعادة توليد النموذج' : ($formFailed ? 'إعادة محاولة الإعداد' : 'بدء تجهيز النموذج') }}
                                </span>
                            </button>
                        @endcan
                    </div>
                </div>
            </div>
        @endcan
    @endif
@endforeach

@once
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const AUTO_TIMEOUT_MS = 45000;
            const MANUAL_TIMEOUT_MS = 68000;

            document.querySelectorAll('[data-regulatory-form]').forEach(function(card) {
                const badge = card.querySelector('[data-form-badge]');
                const errorBox = card.querySelector('[data-form-error]');
                const errorText = card.querySelector('[data-form-error-text]');
                const timeoutBox = card.querySelector('[data-form-timeout]');
                const loadingBox = card.querySelector('[data-form-loading]');
                const progressBar = card.querySelector('[data-form-progress]');
                const progressPercent = card.querySelector('[data-form-percent]');
                const downloadBtn = card.querySelector('[data-form-download]');
                const retryBtn = card.querySelector('[data-form-retry]');
                const retryIcon = card.querySelector('[data-form-retry-icon]');
                const retryText = card.querySelector('[data-form-retry-text]');

                let pollInterval = null;
                let failures = 0;

                function updateProgressUI(pct) {
                    const safePct = Math.max(5, Math.min(100, Math.round(Number(pct) || 0)));
                    if (progressBar) {
                        progressBar.style.width = safePct + '%';
                        progressBar.parentElement?.setAttribute('aria-valuenow', String(safePct));
                    }
                    if (progressPercent) {
                        progressPercent.textContent = safePct + '%';
                    }
                }

                function disableDownloadButton() {
                    if (!downloadBtn) return;
                    downloadBtn.classList.add('disabled');
                    downloadBtn.setAttribute('disabled', 'disabled');
                    downloadBtn.setAttribute('aria-disabled', 'true');
                    downloadBtn.href = 'javascript:void(0)';
                }

                function enableDownloadButton(url) {
                    if (!downloadBtn) return;
                    downloadBtn.classList.remove('disabled');
                    downloadBtn.removeAttribute('disabled');
                    downloadBtn.setAttribute('aria-disabled', 'false');
                    downloadBtn.href = url || 'javascript:void(0)';
                }

                function resetRetryButton(text = 'إعادة محاولة الإعداد') {
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

                function setProcessingUI() {
                    if (retryBtn) {
                        retryBtn.disabled = true;
                        retryBtn.classList.add('d-none');
                    }
                    if (timeoutBox) timeoutBox.classList.add('d-none');
                    if (errorBox) errorBox.classList.add('d-none');
                    if (loadingBox) loadingBox.classList.remove('d-none');
                    if (badge) {
                        badge.className = 'badge bg-label-info';
                        badge.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i>قيد التوليد';
                    }
                    disableDownloadButton();
                }

                function showError(message, badgeText = 'فشل الإعداد', retryLabel = 'إعادة محاولة الإعداد') {
                    window.toastr?.error?.(message);
                    if (errorText) {
                        errorText.textContent = message;
                    }
                    if (errorBox) {
                        errorBox.classList.remove('d-none');
                    }
                    if (loadingBox) {
                        loadingBox.classList.add('d-none');
                    }
                    if (badge) {
                        badge.className = 'badge bg-label-danger';
                        badge.textContent = badgeText;
                    }
                    resetRetryButton(retryLabel);
                }

                function render(state) {
                    const previousStatus = card.dataset.status;
                    card.dataset.status = state.status;
                    const pending = ['queued', 'processing'].includes(state.status);
                    const failed = state.status === 'failed';
                    const ready = !pending && Boolean(state.has_file);

                    if (pending) {
                        setProcessingUI();
                        if (state.progress !== undefined && state.progress !== null) {
                            updateProgressUI(state.progress);
                        }
                        return true;
                    }

                    if (loadingBox) loadingBox.classList.add('d-none');

                    if (ready && state.status === 'ready') {
                        updateProgressUI(100);
                    }

                    if (errorBox) {
                        errorBox.classList.toggle('d-none', !failed);
                    }
                    if (errorText) {
                        errorText.textContent = state.error_message || '';
                    }
                    if (failed && previousStatus !== 'failed') {
                        window.toastr?.error?.(state.error_message || 'تعذر تجهيز النموذج.');
                    }

                    if (badge) {
                        badge.className = 'badge ' + (failed ? 'bg-label-danger' : (ready ? 'bg-label-success' : 'bg-label-secondary'));
                        badge.textContent = failed ? 'فشل الإعداد' : (ready ? 'جاهز للتحميل' : 'غير مجهز بعد');
                    }

                    if (ready && state.download_url) {
                        enableDownloadButton(state.download_url);
                    } else {
                        disableDownloadButton();
                    }

                    resetRetryButton(ready ? 'إعادة توليد النموذج' : (failed ? 'إعادة محاولة الإعداد' : 'بدء تجهيز النموذج'));
                    return false;
                }

                function startPolling(timeoutLimit, isManualRetry = false) {
                    if (pollInterval) {
                        clearInterval(pollInterval);
                    }
                    setProcessingUI();
                    const startTime = Date.now();
                    failures = 0;

                    pollInterval = setInterval(async function() {
                        const elapsed = Date.now() - startTime;
                        if (elapsed >= timeoutLimit) {
                            clearInterval(pollInterval);
                            pollInterval = null;
                            const timeoutMsg = isManualRetry
                                ? 'انتهت المهلة المحددة للتوليد اليدوي للنموذج. يمكنك المحاولة مجدداً.'
                                : 'انتهت المهلة المحددة لتحضير النموذج تلقائياً. يمكنك الضغط على زر إعادة التوليد للمحاولة مرة أخرى.';

                            window.toastr?.error?.(timeoutMsg);
                            if (loadingBox) loadingBox.classList.add('d-none');
                            if (timeoutBox) timeoutBox.classList.remove('d-none');
                            if (badge) {
                                badge.className = 'badge bg-label-danger';
                                badge.textContent = 'انتهت المهلة';
                            }
                            resetRetryButton('توليد النموذج الآن');
                            return;
                        }

                        try {
                            const response = await fetch(card.dataset.statusUrl, {
                                headers: { Accept: 'application/json' },
                                credentials: 'same-origin'
                            });
                            if (!response.ok) {
                                throw new Error('تعذر قراءة حالة النموذج.');
                            }
                            const state = await response.json();
                            failures = 0;

                            const pending = render(state);
                            if (!pending) {
                                clearInterval(pollInterval);
                                pollInterval = null;
                                if (state.status === 'ready') {
                                    window.toastr?.success?.('تم تحضير وتجهيز النموذج بنجاح!');
                                    setTimeout(() => location.reload(), 900);
                                }
                            }
                        } catch (exception) {
                            failures++;
                            if (failures >= 3) {
                                clearInterval(pollInterval);
                                pollInterval = null;
                                showError('تعذر الاتصال بخدمة تجهيز النموذج. يرجى تحديث الصفحة.');
                            }
                        }
                    }, 2000);
                }

                async function startFormGeneration() {
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = null;
                    }
                    setProcessingUI();
                    if (retryIcon) retryIcon.className = 'ti ti-loader ti-spin me-1';
                    if (retryText) retryText.textContent = 'جارٍ بدء التوليد...';
                    updateProgressUI(10);

                    let simulatedProgress = 10;
                    const progressTimer = setInterval(function() {
                        if (simulatedProgress < 85) {
                            simulatedProgress += 5;
                            updateProgressUI(simulatedProgress);
                        }
                    }, 400);

                    try {
                        const response = await fetch(card.dataset.retryUrl, {
                            method: 'POST',
                            credentials: 'same-origin',
                            headers: {
                                'Content-Type': 'application/json',
                                Accept: 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            }
                        });
                        clearInterval(progressTimer);

                        const state = await response.json().catch(() => ({}));
                        if (!response.ok || state.status === 'failed') {
                            showError(state.message || state.error_message || 'تعذر إعادة توليد النموذج. يرجى إعادة المحاولة.');
                            return;
                        }

                        if (state.status === 'ready') {
                            render(state);
                            window.toastr?.success?.('تم تحضير وتجهيز النموذج بنجاح!');
                            setTimeout(() => location.reload(), 700);
                            return;
                        }

                        window.toastr?.info?.('تم بدء تجهيز النموذج.');
                        updateProgressUI(state.progress || 25);
                        startPolling(MANUAL_TIMEOUT_MS, true);
                    } catch (exception) {
                        clearInterval(progressTimer);
                        showError('حدث خطأ في الاتصال بالخادم أثناء إعادة توليد النموذج.');
                    }
                }

                if (retryBtn) {
                    retryBtn.addEventListener('click', startFormGeneration);
                }

                if (['queued', 'processing'].includes(card.dataset.status)) {
                    startPolling(AUTO_TIMEOUT_MS, false);
                }

                window.addEventListener('pagehide', function() {
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = null;
                    }
                }, { once: true });
            });
        });
    </script>
@endonce
