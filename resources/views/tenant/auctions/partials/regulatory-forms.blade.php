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
    @php
        $canRegenerateForm = ! auth('tenant')->check() || (bool) auth('tenant')->user()?->can($formOptions['permission'] . '.regenerate');
        $isActionable = false;
        $disabledAlert = null;

        if ($formType === 'establishment') {
            if ($savedRequest?->exists && is_array($savedRequest->data) && ! empty($savedRequest->data)) {
                $isActionable = true;
            } else {
                $disabledAlert = 'يرجى حفظ وتقديم طلب إقامة المزاد لتوليد النموذج.';
            }
        } elseif ($formType === 'closing') {
            if (! $savedRequest?->isApproved()) {
                $disabledAlert = 'يرجى اعتماد طلب إقامة المزاد أولاً لتتمكن من إقفال المزاد وتوليد النموذج.';
            } elseif (! $auction->isClosed() || ! $auction->closing?->saved_at) {
                $disabledAlert = 'برجاء إكمال الإقفال لتوليد نموذج الإقفال.';
            } else {
                $isActionable = true;
            }
        }

        $formGeneration = $isActionable ? $formService->latest($auction, $formType) : null;
        $publishedForm = $isActionable ? $formService->published($auction, $formType) : null;
        $formProcessing = $isActionable && (bool) $formGeneration?->isProcessing();
        $hasPublishedForm = $isActionable && (bool) $publishedForm?->isReady();
        $formReadyToDownload = $isActionable && ! $formProcessing && $hasPublishedForm;
        $formFailed = $isActionable && ! $formProcessing && $formGeneration?->status === 'failed';
        $formPrepStatus = $isActionable
            ? \App\Enums\Tenants\Auctions\AuctionFilePreparationStatus::resolve(
                $formGeneration?->status,
                $hasPublishedForm,
            )
            : \App\Enums\Tenants\Auctions\AuctionFilePreparationStatus::NOT_PREPARED;
        $formProgress = ($isActionable && $formGeneration) ? max(0, min(100, (int) ($formGeneration->progress ?? 0))) : 0;
        $formDownload = $isActionable ? tenant_route_name('auctions.forms.download', ['auction' => $auction->id, 'type' => $formType]) : null;
    @endphp
    <div class="card mb-4" data-regulatory-form="{{ $formType }}"
        data-actionable="{{ $isActionable ? '1' : '0' }}"
        data-status="{{ $formGeneration?->status ?? 'none' }}"
        data-generation-id="{{ $formGeneration?->id }}"
        data-progress="{{ $formProgress }}"
        data-has-file="{{ $hasPublishedForm ? '1' : '0' }}"
        data-download-url="{{ $formDownload ?? '' }}"
        data-status-url="{{ tenant_route_name('auctions.forms.status', ['auction' => $auction->id, 'type' => $formType]) }}"
        data-retry-url="{{ tenant_route_name('auctions.forms.retry', ['auction' => $auction->id, 'type' => $formType]) }}">
        <div class="card-header pb-2 mb-3 d-flex justify-content-between align-items-center">
            <h6 class="card-title mb-0">
                <i class="ti {{ $formOptions['icon'] }} text-primary me-2"></i>{{ $formOptions['label'] }}
            </h6>
            <span data-form-badge class="badge {{ $formPrepStatus->badgeClass() }}">{{ $formPrepStatus->label() }}</span>
        </div>
        <div class="card-body">
            @if (! $isActionable && $disabledAlert)
                <div class="alert alert-warning p-2 small mb-3">
                    <i class="ti ti-alert-triangle me-1"></i>
                    <span>{{ $disabledAlert }}</span>
                </div>
            @endif

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
                        <span data-form-stage class="text-muted fw-normal"></span>
                    </span>
                    <span data-form-percent class="fw-bold text-primary small">{{ $formProgress }}%</span>
                </div>
                <div class="progress" style="height: 10px;" role="progressbar"
                    aria-valuenow="{{ $formProgress }}" aria-valuemin="0" aria-valuemax="100"
                    aria-label="تقدم تجهيز النموذج">
                    <div data-form-progress
                        class="progress-bar progress-bar-striped progress-bar-animated bg-primary"
                        style="width: {{ $formProgress }}%;"></div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <a data-form-download
                    href="{{ $formReadyToDownload ? $formDownload : 'javascript:void(0)' }}"
                    class="btn btn-primary d-flex align-items-center justify-content-between {{ $formReadyToDownload ? '' : 'disabled' }}"
                    @if (! $formReadyToDownload) disabled aria-disabled="true" @else aria-disabled="false" @endif>
                    <span><i class="ti {{ $formOptions['icon'] }} me-2"></i>تحميل {{ $formOptions['label'] }}</span>
                    <i class="ti ti-download"></i>
                </a>
                <button type="button" data-form-retry
                    class="btn btn-outline-primary mt-2"
                    @disabled(! $isActionable || $formProcessing || ! $canRegenerateForm)
                    @if (! $isActionable || $formProcessing || ! $canRegenerateForm) aria-disabled="true" @endif>
                    <i data-form-retry-icon class="ti {{ $formProcessing ? 'ti-loader ti-spin' : 'ti-refresh' }} me-1"></i>
                    <span data-form-retry-text>
                        {{ $formProcessing ? 'جارٍ التوليد...' : ($hasPublishedForm ? 'إعادة توليد النموذج' : ($formFailed ? 'إعادة محاولة التجهيز' : 'بدء تجهيز النموذج')) }}
                    </span>
                </button>
            </div>
        </div>
    </div>
@endforeach

@once
    @include('tenant.auctions.partials._generation-helpers')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const ui = window.AuctionGenerationUI;
            const AUTO_TIMEOUT_MS = 45000;
            const MANUAL_TIMEOUT_MS = 68000;
            const FORM_STAGES = [
                { min: 0, label: '(في انتظار بدء المعالجة)' },
                { min: 5, label: '(تمت الجدولة)' },
                { min: 10, label: '(بدء المعالجة)' },
                { min: 30, label: '(توليد ملف النموذج PDF)' },
                { min: 85, label: '(التحقق من الملف)' },
                { min: 95, label: '(حفظ النموذج)' },
                { min: 100, label: '(اكتمل التجهيز)' },
            ];

            document.querySelectorAll('[data-regulatory-form]').forEach(function(card) {
                if (card.dataset.actionable === '0') {
                    return;
                }

                const badge = card.querySelector('[data-form-badge]');
                const errorBox = card.querySelector('[data-form-error]');
                const errorText = card.querySelector('[data-form-error-text]');
                const timeoutBox = card.querySelector('[data-form-timeout]');
                const loadingBox = card.querySelector('[data-form-loading]');
                const progressBar = card.querySelector('[data-form-progress]');
                const progressPercent = card.querySelector('[data-form-percent]');
                const stageLabel = card.querySelector('[data-form-stage]');
                const downloadBtn = card.querySelector('[data-form-download]');
                const retryBtn = card.querySelector('[data-form-retry]');
                const retryIcon = card.querySelector('[data-form-retry-icon]');
                const retryText = card.querySelector('[data-form-retry-text]');

                const tracker = ui.createProgressTracker({
                    barEl: progressBar,
                    percentEl: progressPercent,
                    stageEl: stageLabel,
                    stages: FORM_STAGES,
                    initialGenerationId: card.dataset.generationId || null,
                    initialProgress: card.dataset.progress || 0,
                });

                let lastKnownFile = {
                    has_file: card.dataset.hasFile === '1',
                    download_url: card.dataset.downloadUrl || null,
                };
                let pollInterval = null;
                let pollInFlight = false;
                let failures = 0;

                function stopPolling() {
                    if (pollInterval) {
                        clearInterval(pollInterval);
                        pollInterval = null;
                    }
                    pollInFlight = false;
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

                function syncDownloadButton(state) {
                    if (state && state.has_file !== undefined) {
                        lastKnownFile = {
                            has_file: Boolean(state.has_file),
                            download_url: state.download_url || card.dataset.downloadUrl || null,
                        };
                    }
                    if (lastKnownFile.has_file && lastKnownFile.download_url) {
                        enableDownloadButton(lastKnownFile.download_url);
                    } else {
                        disableDownloadButton();
                    }
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
                    if (timeoutBox) timeoutBox.classList.add('d-none');
                    if (errorBox) errorBox.classList.add('d-none');
                    if (loadingBox) loadingBox.classList.remove('d-none');
                    ui.renderBadge(badge, 'not_prepared');
                    disableDownloadButton();
                }

                function showError(message, state = null) {
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
                    ui.renderBadge(badge, 'failed');
                    syncDownloadButton(state);
                    resetRetryButton('إعادة محاولة التجهيز');
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
                            tracker.update(state.progress, state.generation_id);
                        }
                        return true;
                    }

                    if (loadingBox) loadingBox.classList.add('d-none');

                    if (state.status === 'ready') {
                        tracker.update(100, state.generation_id);
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

                    ui.renderBadge(badge, ui.resolveStatusKey(state.status, ready));
                    syncDownloadButton(state);

                    resetRetryButton(ready ? 'إعادة توليد النموذج' : (failed ? 'إعادة محاولة التجهيز' : 'بدء تجهيز النموذج'));
                    return false;
                }

                function startPolling(timeoutLimit, isManualRetry = false, pollImmediately = true) {
                    stopPolling();
                    setProcessingUI();
                    const startTime = Date.now();
                    failures = 0;

                    async function pollOnce() {
                        const elapsed = Date.now() - startTime;
                        if (elapsed >= timeoutLimit) {
                            stopPolling();
                            const timeoutMsg = isManualRetry
                                ? 'انتهت المهلة المحددة للتوليد اليدوي للنموذج. يمكنك المحاولة مجدداً.'
                                : 'انتهت المهلة المحددة لتحضير النموذج تلقائياً. يمكنك الضغط على زر إعادة التوليد للمحاولة مرة أخرى.';

                            window.toastr?.error?.(timeoutMsg);
                            if (loadingBox) loadingBox.classList.add('d-none');
                            if (timeoutBox) timeoutBox.classList.remove('d-none');
                            ui.renderBadge(badge, 'not_prepared');
                            syncDownloadButton(null);
                            resetRetryButton(lastKnownFile.has_file ? 'إعادة توليد النموذج' : 'إعادة محاولة التجهيز');
                            return;
                        }

                        if (pollInFlight) {
                            return;
                        }
                        pollInFlight = true;

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
                                stopPolling();
                                if (state.status === 'ready') {
                                    window.toastr?.success?.('تم تحضير وتجهيز النموذج بنجاح!');
                                    setTimeout(() => location.reload(), 900);
                                }
                            }
                        } catch (exception) {
                            failures++;
                            if (failures >= 3) {
                                stopPolling();
                                showError('تعذر الاتصال بخدمة تجهيز النموذج. يرجى تحديث الصفحة.');
                            }
                        } finally {
                            pollInFlight = false;
                        }
                    }

                    if (pollImmediately) {
                        pollOnce();
                    }
                    pollInterval = setInterval(pollOnce, 2000);
                }

                async function startFormGeneration() {
                    stopPolling();
                    tracker.reset(0, null);
                    setProcessingUI();
                    if (retryText) retryText.textContent = 'جارٍ بدء التوليد...';

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

                        const state = await response.json().catch(() => ({}));
                        if (!response.ok || state.status === 'failed') {
                            showError(state.message || state.error_message || 'تعذر إعادة توليد النموذج. يرجى إعادة المحاولة.', state);
                            return;
                        }

                        if (state.status === 'ready') {
                            render(state);
                            window.toastr?.success?.('تم تحضير وتجهيز النموذج بنجاح!');
                            setTimeout(() => location.reload(), 700);
                            return;
                        }

                        window.toastr?.info?.('تم بدء تجهيز النموذج.');
                        tracker.update(state.progress ?? 0, state.generation_id);
                        startPolling(MANUAL_TIMEOUT_MS, true, false);
                    } catch (exception) {
                        showError('حدث خطأ في الاتصال بالخادم أثناء إعادة توليد النموذج.');
                    }
                }

                if (retryBtn) {
                    retryBtn.addEventListener('click', startFormGeneration);
                }

                if (['queued', 'processing'].includes(card.dataset.status)) {
                    tracker.reset(card.dataset.progress || 0, card.dataset.generationId || null);
                    startPolling(AUTO_TIMEOUT_MS, false, true);
                }

                window.addEventListener('pagehide', stopPolling, { once: true });
            });
        });
    </script>
@endonce
