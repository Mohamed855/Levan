@php
    $savedRequest = $auction->establishmentRequest;
    $formService = app(\App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService::class);
    $formTypes = [
        'establishment' => ['label' => 'نموذج طلب إقامة مزاد', 'permission' => 'auction_establishment_requests'],
        'closing' => ['label' => 'نموذج إقفال المزاد', 'permission' => 'auction_closings'],
    ];
@endphp
@foreach ($formTypes as $formType => $formOptions)
    @if ($savedRequest?->exists && ($formType === 'establishment' || ($savedRequest->isApproved() && $auction->closing?->saved_at)))
        @can($formOptions['permission'] . '.view')
            @php
                $formGeneration = $formService->latest($auction, $formType);
                $publishedForm = $formService->published($auction, $formType);
                $formProcessing = (bool) $formGeneration?->isProcessing();
                $formProgress = (int) ($formGeneration?->progress ?? 0);
                $formDownload = tenant_route_name('auctions.forms.download', ['auction' => $auction->id, 'type' => $formType]);
            @endphp
            <div class="card mb-4" data-regulatory-form="{{ $formType }}"
                data-status="{{ $formGeneration?->status ?? 'none' }}"
                data-status-url="{{ tenant_route_name('auctions.forms.status', ['auction' => $auction->id, 'type' => $formType]) }}"
                data-retry-url="{{ tenant_route_name('auctions.forms.retry', ['auction' => $auction->id, 'type' => $formType]) }}">
                <div class="card-header pb-2 mb-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0"><i class="ti ti-file-text text-primary me-2"></i>{{ $formOptions['label'] }}</h6>
                    <span data-form-badge class="badge {{ $formProcessing ? 'bg-label-info' : ($formGeneration?->status === 'failed' ? 'bg-label-danger' : ($publishedForm ? 'bg-label-success' : 'bg-label-secondary')) }}">
                        {{ $formProcessing ? 'قيد التوليد' : ($formGeneration?->status === 'failed' ? 'فشل الإعداد' : ($publishedForm ? 'جاهز للتحميل' : 'غير مجهز بعد')) }}
                    </span>
                </div>
                <div class="card-body">
                    <div data-form-error class="text-danger small mb-2 {{ $formGeneration?->status === 'failed' ? '' : 'd-none' }}">{{ $formGeneration?->publicErrorMessage() }}</div>
                    <div data-form-loading class="p-3 mb-3 border rounded bg-light-subtle {{ $formProcessing ? '' : 'd-none' }}">
                        <div class="d-flex justify-content-between align-items-center mb-2 small text-primary">
                            <span><i class="ti ti-loader ti-spin me-1"></i>جارٍ تجهيز النموذج...</span>
                            <span data-form-percent class="fw-bold">{{ $formProgress }}%</span>
                        </div>
                        <div class="progress" style="height: 10px" role="progressbar" aria-valuenow="{{ $formProgress }}" aria-valuemin="0" aria-valuemax="100" aria-label="تقدم تجهيز النموذج">
                            <div data-form-progress class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: {{ $formProgress }}%"></div>
                        </div>
                    </div>
                    <div class="d-grid gap-2">
                        <a data-form-download href="{{ $publishedForm ? $formDownload : '#' }}" class="btn btn-primary {{ $publishedForm ? '' : 'disabled' }}" aria-disabled="{{ $publishedForm ? 'false' : 'true' }}">
                            <i class="ti ti-download me-1"></i>تحميل {{ $formOptions['label'] }}
                        </a>
                        @can($formOptions['permission'] . '.regenerate')
                            <button type="button" data-form-retry class="btn btn-outline-primary {{ $formProcessing ? 'd-none' : '' }}">
                                <i class="ti ti-refresh me-1"></i>إعادة توليد النموذج
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
        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('[data-regulatory-form]').forEach(function (card) {
                const badge = card.querySelector('[data-form-badge]');
                const error = card.querySelector('[data-form-error]');
                const loading = card.querySelector('[data-form-loading]');
                const progress = card.querySelector('[data-form-progress]');
                const percent = card.querySelector('[data-form-percent]');
                const download = card.querySelector('[data-form-download]');
                const retry = card.querySelector('[data-form-retry]');
                let timer;
                let deadline;
                let failures = 0;

                function showError(message) {
                    window.toastr?.error?.(message);
                    error.textContent = message;
                    error.classList.remove('d-none');
                    loading.classList.add('d-none');
                    badge.textContent = 'تعذر الإعداد';
                    badge.className = 'badge bg-label-danger';
                    if (retry) {
                        retry.classList.remove('d-none');
                        retry.disabled = false;
                    }
                }

                function render(state) {
                    const previousStatus = card.dataset.status;
                    card.dataset.status = state.status;
                    const pending = ['queued', 'processing'].includes(state.status);
                    const value = Math.max(0, Math.min(100, Number(state.progress) || 0));
                    loading.classList.toggle('d-none', !pending);
                    progress.style.width = value + '%';
                    progress.parentElement.setAttribute('aria-valuenow', String(value));
                    percent.textContent = value + '%';
                    error.classList.toggle('d-none', state.status !== 'failed');
                    error.textContent = state.error_message || '';
                    if (state.status === 'failed' && previousStatus !== 'failed') {
                        window.toastr?.error?.(state.error_message || 'تعذر تجهيز النموذج.');
                    }
                    badge.textContent = pending ? 'قيد التوليد' : (state.status === 'failed' ? 'فشل الإعداد' : (state.has_file ? 'جاهز للتحميل' : 'غير مجهز بعد'));
                    badge.className = 'badge ' + (pending ? 'bg-label-info' : (state.status === 'failed' ? 'bg-label-danger' : (state.has_file ? 'bg-label-success' : 'bg-label-secondary')));
                    download.classList.toggle('disabled', !state.has_file);
                    download.setAttribute('aria-disabled', state.has_file ? 'false' : 'true');
                    download.href = state.download_url || '#';
                    if (retry) {
                        retry.classList.toggle('d-none', pending);
                        retry.disabled = false;
                    }
                    return pending;
                }

                async function poll() {
                    if (Date.now() > deadline) {
                        showError('انتهت مهلة انتظار النموذج. يمكنك تحديث الصفحة أو إعادة المحاولة.');
                        return;
                    }
                    try {
                        const response = await fetch(card.dataset.statusUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                        if (!response.ok) {
                            throw new Error('تعذر قراءة حالة النموذج.');
                        }
                        const pending = render(await response.json());
                        failures = 0;
                        if (pending) timer = setTimeout(poll, 2000);
                    } catch (exception) {
                        failures++;
                        if (failures < 3) timer = setTimeout(poll, 3000);
                        else showError('تعذر الاتصال بخدمة تجهيز النموذج. يرجى تحديث الصفحة.');
                    }
                }

                if (retry) retry.addEventListener('click', async function () {
                    clearTimeout(timer);
                    retry.disabled = true;
                    error.classList.add('d-none');
                    try {
                        const response = await fetch(card.dataset.retryUrl, {
                            method: 'POST', credentials: 'same-origin',
                            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' }
                        });
                        if (!response.ok) throw new Error('تعذر إعادة توليد النموذج.');
                        const state = await response.json();
                        render(state);
                        deadline = Date.now() + 300000;
                        failures = 0;
                        if (['queued', 'processing'].includes(state.status)) timer = setTimeout(poll, 1000);
                    } catch (exception) {
                        showError('تعذر إعادة توليد النموذج. يرجى إعادة المحاولة.');
                    }
                });

                if (['queued', 'processing'].includes(card.dataset.status)) {
                    deadline = Date.now() + 300000;
                    poll();
                }
                window.addEventListener('pagehide', function () { clearTimeout(timer); }, { once: true });
            });
        });
    </script>
@endonce
