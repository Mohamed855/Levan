@php
    $isEdit = isset($auction) && $auction->exists;
    $marketing = old('marketing', $isEdit ? $auction->marketing_data ?? [] : []);
    $cityContextRaw = old('marketing.city_context', $isEdit ? ($auction->marketing_data['city_context'] ?? []) : []);
    $cityContextValues = is_array($cityContextRaw)
        ? $cityContextRaw
        : array_values(array_filter(array_map('trim', preg_split('/[،,]+/u', (string) $cityContextRaw) ?: [])));

    $existingBrochureEvidence = $isEdit
        ? $auction->attachments
            ->where('report_section', 'brochure')
            ->whereNull('marketing_campaign_id')
            ->sortBy('display_order')
            ->values()
            ->map(fn ($file): array => [
                'id' => $file->id,
                'url' => tasset($file->file_path),
                'name' => $file->original_name ?? $file->file_name ?? basename((string) $file->file_path),
                'mime' => $file->mime_type ?? '',
                'remove_name' => 'remove_brochure_evidence',
                'remove_value' => 1,
            ])
            ->first()
        : null;

    $existingPlatformEvidence = $isEdit
        ? $auction->attachments
            ->where('report_section', 'platform')
            ->values()
            ->map(fn ($file): array => [
                'id' => $file->id,
                'url' => tasset($file->file_path),
                'name' => $file->original_name ?? $file->file_name ?? basename((string) $file->file_path),
                'mime' => $file->mime_type ?? '',
                'remove_name' => 'remove_platform_evidence[]',
                'remove_value' => $file->id,
            ])
            ->all()
        : null;

    $brokerRows = old(
        'marketing.brokers',
        $isEdit
            ? app(\App\Services\Tenants\Auctions\AuctionBrokersManager::class)->rows($auction)
            : [],
    );
    $brokerRows = is_array($brokerRows) ? array_values($brokerRows) : [];
    $brokerAttachments = $isEdit ? $auction->attachments->where('report_section', 'brokers') : collect();

    // إعدادات قنوات الحملات التسويقية
    $channelDefinitions = config('auction-marketing-campaigns', []);
    $channelFields = config('auction-marketing-fields', []);
    $textFields = $channelFields['text'] ?? [];
    $linkChannels = $channelFields['links'] ?? [];
    $evidenceChannels = $channelFields['evidence'] ?? [];
    $integerMetrics = config('auction-report-inputs.integer_metrics', []);
    $signedMetrics = config('auction-report-inputs.signed_metrics', []);
    $channelLabels = __('auction_reports.channels', [], 'ar') ?: [];

    $channelIcons = [
        'youtube' => 'ti ti-brand-youtube',
        'x' => 'ti ti-brand-x',
        'snapchat' => 'ti ti-brand-snapchat',
        'tiktok' => 'ti ti-brand-tiktok',
        'whatsapp' => 'ti ti-brand-whatsapp',
        'google' => 'ti ti-brand-google',
        'marketing_evidence' => 'ti ti-link',
    ];

    $channelSelectOptions = collect($channelDefinitions)
        ->mapWithKeys(fn($metrics, $key) => [$key => $channelLabels[$key] ?? $key])
        ->all();

    $existingCampaigns = $isEdit ? $auction->marketingCampaigns->keyBy('channel') : collect();
    $activeChannels = (array) old('channels', $existingCampaigns->pluck('channel')->all());
    $firstActiveChannel = $activeChannels[0] ?? array_key_first($channelDefinitions);
    $hasActiveSelection = count($activeChannels) > 0;
@endphp

<div class="row g-4">
    {{-- 1. التخطيط والنطاق الجغرافي --}}
    <x-form.controls.select name="marketing[city_context]" id="marketing_city_context" label="نطاق الحملة الجغرافي"
        :options="$regionOptions ?? []" :value="$cityContextValues" multiple placeholder="اختر النطاقات الجغرافية للحملة..."
        col="col-12" />

    <x-form.controls.textarea name="marketing[planning_methodology]" id="marketing_planning_methodology"
        label="منهجية التخطيط" placeholder="أدخل منهجية التخطيط للحملة التسويقية..." :value="$marketing['planning_methodology'] ?? null" col="col-md-6"
        rows="3" />

    <x-form.controls.textarea name="marketing[brochure_methodology]" id="marketing_brochure_methodology"
        label="إعداد الكتيب والهوية البصرية" placeholder="أدخل تفاصيل إعداد الكتيب والهوية البصرية..." :value="$marketing['brochure_methodology'] ?? null" col="col-md-6"
        rows="3" />

    <x-form.controls.textarea name="marketing[audience_methodology]" id="marketing_audience_methodology"
        label="الفئات المستهدفة" placeholder="أدخل تفاصيل الفئات المستهدفة للحملة..." :value="$marketing['audience_methodology'] ?? null" col="col-md-6"
        rows="3" />

    <x-form.controls.textarea name="marketing[messages_methodology]" id="marketing_messages_methodology"
        label="الرسائل التسويقية" placeholder="أدخل تفاصيل الرسائل التسويقية الموجهة..." :value="$marketing['messages_methodology'] ?? null" col="col-md-6"
        rows="3" />

    {{-- 2. الكتيب والمنصة --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3"><i class="ti ti-book me-1 text-primary"></i>كتيب المزاد ومرفقات المنصة</h6>
    </div>

    <x-form.controls.input name="marketing[brochure_context]" id="marketing_brochure_context"
        label="سياق كتيب المزاد" placeholder="تفاصيل توزيع ونشر الكتيب..." :value="$marketing['brochure_context'] ?? null" col="col-md-6" />

    <div class="col-md-6">
        <x-form.controls.file name="brochure_evidence" id="brochure_evidence" label="ملف الكتيب الإلكتروني"
            :value="$existingBrochureEvidence" accept=".pdf,.png,.jpg,.jpeg,.webp" help="ملف واحد PDF أو صورة، حتى 10MB. يُحدَّد رابط الكتيب تلقائيًا من الملف المرفق عند الحفظ."
            col="col-12" />
    </div>

    <div class="col-md-6">
        <x-form.controls.file name="platform_evidence[]" id="platform_evidence" label="إثباتات منصة المزاد"
            multiple :value="$existingPlatformEvidence" accept=".pdf,.png,.jpg,.jpeg,.webp" help="صور أو PDF، حتى 10MB لكل ملف"
            col="col-12" />
    </div>

    {{-- 3. اللوحات الإعلانية --}}
    <div class="col-12 border-top pt-3">
        <h6 class="fw-bold mb-3"><i class="ti ti-ad-2 me-1 text-primary"></i>لوحات المزاد العامة</h6>
    </div>

    <x-form.controls.input name="marketing[board_url]" id="marketing_board_url" type="url"
        label="رابط اللوحات الخارجية للمزاد" placeholder="https://maps.google.com/..." :value="$marketing['board_url'] ?? null" col="col-md-6" />

    <x-form.controls.textarea name="marketing[board_context]" id="marketing_board_context" label="سياق اللوحات الخارجية"
        placeholder="تفاصيل مواقع اللوحات وتوزيعها الجغرافي..." :value="$marketing['board_context'] ?? null" col="col-md-6" rows="2" />

    {{-- 4. المسوقون والوسطاء --}}
    <div class="col-12 border-top pt-3">
        <div class="card border mb-0" data-auction-brokers-card data-next-index="{{ count($brokerRows) }}">
            <div class="card-header d-flex justify-content-between align-items-center py-3 bg-light-subtle">
                <h6 class="card-title mb-0 fw-bold"><i class="ti ti-users me-1 text-primary"></i>المسوقون والوسطاء العقاريون</h6>
                <button type="button" class="btn btn-sm btn-primary" id="btn-add-auction-broker">
                    <i class="ti ti-plus me-1"></i>إضافة وسيط
                </button>
            </div>
            <div class="card-body pt-3">
                <div class="row g-3 mb-3">
                    <x-form.controls.textarea name="marketing[contract_text]" id="marketing_contract_text"
                        label="نص ونطاق التعاقد مع المسوقين" placeholder="تفاصيل ونطاق التعاقد مع المسوقين والوسطاء..."
                        :value="$marketing['contract_text'] ?? null" col="col-12" rows="2" />
                </div>

                <div id="auction-brokers-list" class="mt-2">
                    @forelse($brokerRows as $index => $row)
                        @php
                            $rowKey = $row['key'] ?? 'broker_' . $index;
                            $attId = $row['evidence_id'] ?? null;
                            $attModel = $attId ? $brokerAttachments->firstWhere('id', (int) $attId) : null;
                            $attValue = $attModel
                                ? [
                                    'id' => $attModel->id,
                                    'name' => $attModel->file_name ?? $attModel->original_name,
                                    'url' => tasset($attModel->file_path),
                                    'mime' => $attModel->mime_type ?? '',
                                ]
                                : null;
                        @endphp
                        <div class="broker-item card border p-3 mb-3" data-auction-broker-row data-broker-key="{{ $rowKey }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0 text-muted broker-number">الوسيط #{{ $index + 1 }}</h6>
                                <button type="button" class="btn btn-icon btn-sm btn-danger btn-remove-auction-broker"
                                    aria-label="حذف الوسيط">
                                    <i class="ti ti-trash"></i>
                                </button>
                            </div>
                            <input type="hidden" name="marketing[brokers][{{ $index }}][key]" value="{{ $rowKey }}" data-broker-key-input>
                            <input type="hidden" name="marketing[brokers][{{ $index }}][evidence_id]" value="{{ $attId }}" data-broker-evidence-id>
                            <div class="row g-3">
                                <x-form.controls.input name="marketing[brokers][{{ $index }}][name]" label="اسم الوسيط"
                                    placeholder="أدخل اسم الوسيط" :value="$row['name'] ?? null" col="col-md-6" />
                                <x-form.controls.file name="broker_evidence[{{ $rowKey }}]" label="مستند عقد الوسيط"
                                    :value="$attValue" accept=".pdf,.png,.jpg,.jpeg,.webp" help="صورة أو PDF، حتى 10MB" col="col-md-6" />
                            </div>
                        </div>
                    @empty
                        <p class="text-muted mb-0 text-center py-2" id="auction-brokers-empty">
                            لم تتم إضافة وسطاء بعد. انقر على "إضافة وسيط" لإضافة بيانات وعقد الوسيط.
                        </p>
                    @endforelse
                </div>

                <template id="auction-broker-row-template">
                    <div class="broker-item card border p-3 mb-3" data-auction-broker-row data-broker-key="__KEY__">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0 text-muted broker-number">الوسيط #__NUM__</h6>
                            <button type="button" class="btn btn-icon btn-sm btn-danger btn-remove-auction-broker"
                                aria-label="حذف الوسيط">
                                <i class="ti ti-trash"></i>
                            </button>
                        </div>
                        <input type="hidden" name="marketing[brokers][__INDEX__][key]" value="__KEY__" data-broker-key-input>
                        <input type="hidden" name="marketing[brokers][__INDEX__][evidence_id]" value="" data-broker-evidence-id>
                        <div class="row g-3">
                            <x-form.controls.input name="marketing[brokers][__INDEX__][name]" label="اسم الوسيط"
                                placeholder="أدخل اسم الوسيط" col="col-md-6" />
                            <x-form.controls.file name="broker_evidence[__KEY__]" label="مستند عقد الوسيط"
                                accept=".pdf,.png,.jpg,.jpeg,.webp" help="صورة أو PDF، حتى 10MB" col="col-md-6" />
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>

    {{-- 5. قنوات الحملات التسويقية ومقاييس الأداء --}}
    <div class="col-12 border-top pt-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
            <div>
                <h6 class="fw-bold mb-1"><i class="ti ti-speakerphone me-1 text-primary"></i>قنوات الحملات التسويقية التفصيلية</h6>
                <p class="text-muted small mb-0">حدد المنصات والقنوات المستخدمة في الترويج وسجل مقاييس الأداء والإثباتات</p>
            </div>
        </div>

        <div class="row g-4 mb-3">
            <x-form.controls.select name="channels[]" id="campaign-channels-select"
                label="اختر قنوات الحملات التسويقية المنفذة" :options="$channelSelectOptions" :value="$activeChannels" multiple
                col="col-12" placeholder="اختر منصة أو أكثر (سناب شات، يوتيوب، إكس، تيك توك، واتساب، جوجل...)" />
        </div>

        <div id="campaign-tabs-empty" class="alert alert-info d-flex align-items-center {{ $hasActiveSelection ? 'd-none' : '' }}">
            <i class="ti ti-info-circle ti-md me-2"></i>
            <span>يمكنك اختيار منصة أو أكثر من القائمة أعلاه لإدخال روابطها ومقاييس الأداء وإثباتات الحملة.</span>
        </div>

        <div id="campaign-tabs-wrapper" class="{{ $hasActiveSelection ? '' : 'd-none' }}">
            <ul class="nav nav-pills gap-2 mb-3" id="campaign-nav-pills" role="tablist">
                @foreach ($channelDefinitions as $channel => $metrics)
                    @php
                        $channelLabel = $channelLabels[$channel] ?? $channel;
                        $isSelectedChannel = in_array($channel, $activeChannels, true);
                        $isActiveTab = $channel === $firstActiveChannel;
                    @endphp
                    <li class="nav-item campaign-channel-tab {{ $isSelectedChannel ? '' : 'd-none' }}"
                        id="channel-tab-item-{{ $channel }}" data-channel="{{ $channel }}" role="presentation">
                        <button type="button" class="nav-link {{ $isActiveTab ? 'active' : '' }}"
                            id="tab-btn-{{ $channel }}" data-bs-toggle="pill"
                            data-bs-target="#channel-pane-{{ $channel }}" role="tab"
                            aria-controls="channel-pane-{{ $channel }}" aria-selected="{{ $isActiveTab ? 'true' : 'false' }}">
                            @if (!empty($channelIcons[$channel]))
                                <i class="{{ $channelIcons[$channel] }} me-1"></i>
                            @endif
                            <span>{{ $channelLabel }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>

            <div class="tab-content border rounded p-4 bg-light-subtle" id="campaign-tab-content">
                @foreach ($channelDefinitions as $channel => $metrics)
                    @php
                        $channelLabel = $channelLabels[$channel] ?? $channel;
                        $campaign = $existingCampaigns->get($channel);
                        $hasLink = in_array($channel, $linkChannels, true);
                        $hasEvidence = in_array($channel, $evidenceChannels, true);
                        $hasDate = $channel === 'whatsapp';
                        $isSelectedChannel = in_array($channel, $activeChannels, true);
                        $isActiveTab = $channel === $firstActiveChannel;
                    @endphp

                    <div class="tab-pane campaign-channel-box campaign-channel-pane {{ $isActiveTab && $isSelectedChannel ? 'show active' : '' }}"
                        id="channel-pane-{{ $channel }}" data-channel="{{ $channel }}" role="tabpanel"
                        aria-labelledby="tab-btn-{{ $channel }}">

                        <div class="row g-4">
                            @if ($hasLink)
                                <x-form.controls.input :name="'campaigns[' . $channel . '][url]'" type="url" label="رابط الحملة / التغريدة / الإعلان"
                                    :value="old('campaigns.' . $channel . '.url', $campaign?->url)" col="col-md-6" placeholder="https://..." />
                            @endif

                            @if ($hasDate)
                                <x-form.controls.input :name="'campaigns[' . $channel . '][campaign_date]'" label="تاريخ الحملة" type="date"
                                    :value="old('campaigns.' . $channel . '.campaign_date', $campaign?->campaign_date?->format('Y-m-d'))" col="col-md-6" />
                            @endif

                            @foreach ($metrics as $metric)
                                @php
                                    $metricCol = match (true) {
                                        $channel === 'google' && $metric === 'views' => 'col-12',
                                        $channel === 'youtube' && in_array($metric, ['views', 'subscribers', 'watch_hours'], true) => 'col-md-4',
                                        $channel === 'x' && in_array($metric, ['views', 'posts'], true) => 'col-md-6',
                                        $channel === 'tiktok' => 'col-md-4',
                                        $channel === 'whatsapp' => 'col-md-4',
                                        $metric === 'x_context' || $metric === 'video_context' => 'col-12',
                                        default => 'col-md-6',
                                    };
                                    $metricTitle = $metric === 'views' ? 'عدد المشاهدات' : (__('auction_reports.metrics.' . $metric, [], 'ar') ?: $metric);
                                @endphp

                                @if (array_key_exists($metric, $textFields))
                                    <x-form.controls.textarea :name="'campaigns[' . $channel . '][metrics][' . $metric . ']'" :label="$textFields[$metric]"
                                        :value="old('campaigns.' . $channel . '.metrics.' . $metric, $campaign?->metrics[$metric] ?? null)"
                                        rows="2" :col="$metricCol" />
                                @else
                                    <x-form.controls.input :name="'campaigns[' . $channel . '][metrics][' . $metric . ']'" :label="$metricTitle"
                                        type="number" :value="old('campaigns.' . $channel . '.metrics.' . $metric, $campaign?->metrics[$metric] ?? null)"
                                        :step="in_array($metric, $integerMetrics, true) ? '1' : 'any'" :min="in_array($metric, $signedMetrics, true) ? null : 0"
                                        :col="$metricCol" />
                                @endif
                            @endforeach

                            @if ($hasEvidence)
                                @php
                                    $existingChannelEvidence = $campaign && $campaign->evidence->isNotEmpty()
                                        ? $campaign->evidence->map(fn ($file): array => [
                                            'id' => $file->id,
                                            'url' => tasset($file->file_path),
                                            'name' => $file->original_name ?? $file->file_name ?? basename((string) $file->file_path),
                                            'mime' => $file->mime_type ?? '',
                                            'remove_name' => 'campaigns[' . $channel . '][remove_evidence][]',
                                            'remove_value' => $file->id,
                                        ])->values()->all()
                                        : null;
                                @endphp
                                <div class="col-12">
                                    <x-form.controls.file :name="'campaigns[' . $channel . '][evidence][]'" label="مرفقات وإثباتات الحملة (Screenshots / Reports)"
                                        multiple :value="$existingChannelEvidence" accept=".pdf,.png,.jpg,.jpeg,.webp"
                                        help="صور أو ملفات إثبات نشر الحملة والتفاعل (حتى 10MB لكل ملف)" col="col-12" />
                                </div>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
