@once
    <script>
        window.AuctionGenerationUI = window.AuctionGenerationUI || (function() {
            const STATUS_MAP = @json(\App\Enums\Tenants\Auctions\AuctionFilePreparationStatus::clientMap());

            function resolveStatusKey(status, hasPublishedFile) {
                if (status === 'queued' || status === 'processing') {
                    return 'not_prepared';
                }
                if (status === 'failed') {
                    return 'failed';
                }
                return hasPublishedFile ? 'ready' : 'not_prepared';
            }

            function renderBadge(targetEl, statusKey) {
                if (!targetEl) {
                    return;
                }
                const entry = STATUS_MAP[statusKey] || STATUS_MAP.not_prepared;
                if (targetEl.tagName === 'SPAN' && targetEl.hasAttribute('data-form-badge')) {
                    targetEl.className = entry.class;
                    targetEl.textContent = entry.label;
                    return;
                }
                targetEl.innerHTML = `<span class="${entry.class}">${entry.label}</span>`;
            }

            function createProgressTracker({
                barEl,
                percentEl,
                stageEl = null,
                stages = [],
                initialGenerationId = null,
                initialProgress = 0,
            }) {
                let currentGenerationId = initialGenerationId !== null && initialGenerationId !== undefined ?
                    String(initialGenerationId) :
                    null;
                let displayedProgress = normalize(initialProgress);

                function normalize(pct) {
                    const numeric = Number(pct);
                    if (!Number.isFinite(numeric)) {
                        return 0;
                    }
                    return Math.max(0, Math.min(100, Math.round(numeric)));
                }

                function resolveStageText(pct) {
                    for (let i = stages.length - 1; i >= 0; i--) {
                        if (pct >= stages[i].min) {
                            return stages[i].label;
                        }
                    }
                    return stages[0]?.label || '';
                }

                function paint(pct) {
                    if (barEl) {
                        barEl.style.width = pct + '%';
                        barEl.setAttribute('aria-valuenow', String(pct));
                        barEl.parentElement?.setAttribute('aria-valuenow', String(pct));
                    }
                    if (percentEl) {
                        percentEl.textContent = pct + '%';
                    }
                    if (stageEl && stages.length > 0) {
                        stageEl.textContent = resolveStageText(pct);
                    }
                }

                return {
                    reset(pct = 0, generationId = null) {
                        currentGenerationId = generationId !== null && generationId !== undefined ?
                            String(generationId) :
                            null;
                        displayedProgress = normalize(pct);
                        paint(displayedProgress);
                    },
                    update(pct, generationId = null) {
                        const incomingId = generationId !== null && generationId !== undefined ?
                            String(generationId) :
                            null;
                        const safePct = normalize(pct);

                        if (incomingId && incomingId !== currentGenerationId) {
                            currentGenerationId = incomingId;
                            displayedProgress = safePct;
                        } else {
                            displayedProgress = Math.max(displayedProgress, safePct);
                        }

                        paint(displayedProgress);
                    },
                    current() {
                        return displayedProgress;
                    },
                };
            }

            return {
                STATUS_MAP,
                resolveStatusKey,
                renderBadge,
                createProgressTracker,
            };
        })();
    </script>
@endonce
