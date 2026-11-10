@extends('tenant.layouts.layoutMaster')

@php
    $channelDefinitions = config('auction-marketing-campaigns', []);
    $channelFields = config('auction-marketing-fields', []);
    $textFields = $channelFields['text'] ?? [];
    $linkChannels = $channelFields['links'] ?? [];
    $evidenceChannels = $channelFields['evidence'] ?? [];
    $integerMetrics = config('auction-report-inputs.integer_metrics', []);
    $signedMetrics = config('auction-report-inputs.signed_metrics', []);
    $channelLabels = __('auction_reports.channels', [], 'ar') ?: [];

    $channelIcons = [
        'planning' => 'ti ti-clipboard-list',
        'promotion' => 'ti ti-speakerphone',
        'brochure' => 'ti ti-book',
        'platform' => 'ti ti-device-laptop',
        'board_before' => 'ti ti-ad-2',
        'brokers' => 'ti ti-users',
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

    $currentAuctionId = old('auction_id', $auction->id);
    $flatProperties = collect($propertyOptions)->collapse()->all();
@endphp

@section('title', $is_edit ? 'تعديل الحملات التسويقية' : 'إضافة حملات تسويقية')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('page-style')
    <style>
        .campaign-tabs-scroll-container {
            overflow-x: auto;
            overflow-y: hidden;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
            padding-bottom: 6px;
        }

        .campaign-tabs-scroll-container::-webkit-scrollbar {
            height: 4px;
        }

        .campaign-tabs-scroll-container::-webkit-scrollbar-thumb {
            background: rgba(var(--bs-primary-rgb), 0.25);
            border-radius: 4px;
        }

        .campaign-scrolled-tabs {
            display: flex;
            flex-wrap: nowrap;
            gap: 0.5rem;
            padding: 2px 2px 4px 2px;
            margin-bottom: 0;
            border-bottom: none !important;
        }

        /* Kill all button animations & border-bottom effects */
        .campaign-scrolled-tabs .nav-link,
        .campaign-scrolled-tabs .nav-link:hover,
        .campaign-scrolled-tabs .nav-link:focus,
        .campaign-scrolled-tabs .nav-link:active,
        .campaign-scrolled-tabs .nav-link.active {
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.55rem 1.1rem !important;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid var(--bs-border-color, #dbdade) !important;
            border-radius: 0.375rem !important;
            transition: none !important;
            animation: none !important;
            cursor: pointer;
            box-shadow: none !important;
        }

        .campaign-scrolled-tabs .nav-link:not(.active) {
            background-color: var(--bs-card-bg, #fff) !important;
            color: var(--bs-heading-color, #566a7f) !important;
            border-color: var(--bs-border-color, #dbdade) !important;
        }

        .campaign-scrolled-tabs .nav-link:hover:not(.active) {
            background-color: rgba(var(--bs-primary-rgb), 0.08) !important;
            color: var(--bs-primary) !important;
            border-color: rgba(var(--bs-primary-rgb), 0.35) !important;
        }

        .campaign-scrolled-tabs .nav-link.active {
            background-color: var(--bs-primary) !important;
            color: #fff !important;
            border-color: var(--bs-primary) !important;
        }

        .campaign-scrolled-tabs .nav-link::before,
        .campaign-scrolled-tabs .nav-link::after,
        .campaign-scrolled-tabs .nav-item::before,
        .campaign-scrolled-tabs .nav-item::after,
        .campaign-scrolled-tabs .tab-slider {
            display: none !important;
            content: none !important;
            transition: none !important;
            animation: none !important;
        }

        .campaign-scrolled-tabs .waves-ripple {
            display: none !important;
        }

        /* Kill all tab pane & input appearing animations */
        .campaign-tabs-content {
            background-color: var(--bs-card-bg, #fff);
            min-height: 220px;
        }

        #campaign-tab-content .tab-pane,
        #campaign-tab-content .tab-pane.fade,
        #campaign-tab-content .tab-pane.show,
        #campaign-tab-content .tab-pane * {
            opacity: 1 !important;
            transform: none !important;
            transition: none !important;
            animation: none !important;
        }
    </style>
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenant/auctions/cycle-forms.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <div class="card-body">
                    <form id="report-cycle-form" method="POST" enctype="multipart/form-data" autocomplete="off"
                        data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                        data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                        data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
                        data-draft-resource-key="tenant.marketing-campaign.{{ $auction->id }}"
                        action="{{ tenant_route_name('marketing-campaigns.update', $auction->id) }}"
                        data-campaign-metrics='@json($channelDefinitions)'
                        data-campaign-fields='@json($channelFields)'
                        data-campaign-properties='@json($propertyOptions)'>
                        @csrf
                        @method('PUT')

                        @php
                            $activeChannels = (array) old('channels', $selectedChannels);
                            $firstActiveChannel = null;
                            $campaignErrors = $errors ?? view()->shared('errors');
                            foreach ($channelDefinitions as $ch => $m) {
                                if (
                                    in_array($ch, $activeChannels, true) &&
                                    $campaignErrors &&
                                    ($campaignErrors->has('campaigns.' . $ch . '.*') ||
                                        $campaignErrors->has('campaigns.' . $ch))
                                ) {
                                    $firstActiveChannel = $ch;
                                    break;
                                }
                            }
                            if (!$firstActiveChannel) {
                                foreach ($channelDefinitions as $ch => $m) {
                                    if (in_array($ch, $activeChannels, true)) {
                                        $firstActiveChannel = $ch;
                                        break;
                                    }
                                }
                            }
                            $hasActiveSelection = count($activeChannels) > 0;
                        @endphp

                        <div id="campaign-tabs-empty"
                            class="alert alert-warning d-flex align-items-center mb-5 {{ $hasActiveSelection ? 'd-none' : '' }}">
                            <i class="ti ti-alert-triangle ti-md me-1 mt-1"></i>
                            <span>يجب اختيار نوع أو أكثر من أنواع الحملات التسويقية لعرض وتعديل بياناتها.</span>
                        </div>

                        {{-- Top Controls: Auction & Channel Multi-Select --}}
                        <div class="row g-4 mb-4">
                            @if ($is_edit)
                                <x-form.controls.input name="_auction_display_name" label="المزاد" :value="$auction->name"
                                    readonly disabled col="col-md-6" />
                                <input type="hidden" name="auction_id" id="campaign-auction" value="{{ $auction->id }}">
                            @else
                                <x-form.controls.select name="auction_id" id="campaign-auction" label="المزاد"
                                    :options="$auctionOptions" :value="$currentAuctionId" required col="col-md-6" />
                            @endif

                            <x-form.controls.select name="channels" id="campaign-channels-select"
                                label="أنواع الحملات التسويقية" :options="$channelSelectOptions" :value="old('channels', $selectedChannels)" multiple required
                                col="col-md-6" />
                        </div>

                        {{-- Scrolled Tabs Wrapper --}}
                        <div id="campaign-tabs-wrapper" class="{{ $hasActiveSelection ? '' : 'd-none' }}">
                            {{-- Horizontal Scrollable Tabs Header --}}
                            <div class="campaign-tabs-scroll-container">
                                <ul class="nav nav-pills flex-nowrap campaign-scrolled-tabs" id="campaign-nav-pills"
                                    role="tablist">
                                    @foreach ($channelDefinitions as $channel => $metrics)
                                        @php
                                            $channelLabel = $channelLabels[$channel] ?? $channel;
                                            $isSelectedChannel = in_array($channel, $activeChannels, true);
                                            $isActiveTab = $channel === $firstActiveChannel;
                                            $hasError =
                                                $errors->has('campaigns.' . $channel . '.*') ||
                                                $errors->has('campaigns.' . $channel);
                                        @endphp
                                        <li class="nav-item campaign-channel-tab {{ $isSelectedChannel ? '' : 'd-none' }}"
                                            id="channel-tab-item-{{ $channel }}" data-channel="{{ $channel }}"
                                            role="presentation">
                                            <button type="button"
                                                class="nav-link text-nowrap {{ $isActiveTab ? 'active' : '' }}"
                                                id="tab-btn-{{ $channel }}" data-bs-toggle="pill"
                                                data-bs-target="#channel-pane-{{ $channel }}" role="tab"
                                                aria-controls="channel-pane-{{ $channel }}"
                                                aria-selected="{{ $isActiveTab ? 'true' : 'false' }}">
                                                @if (!empty($channelIcons[$channel]))
                                                    <i class="{{ $channelIcons[$channel] }} fs-5"></i>
                                                @endif
                                                <span>{{ $channelLabel }}</span>
                                                @if ($hasError)
                                                    <span class="badge bg-danger rounded-pill p-1 ms-1 tab-error-dot"
                                                        style="width: 8px; height: 8px; display: inline-block;"></span>
                                                @endif
                                            </button>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>

                            <div class="tab-content border rounded p-4 mb-4 campaign-tabs-content"
                                id="campaign-tab-content">
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
                                        id="channel-pane-{{ $channel }}" data-channel="{{ $channel }}"
                                        role="tabpanel" aria-labelledby="tab-btn-{{ $channel }}"
                                        {!! $isSelectedChannel ? '' : 'style="display: none;"' !!}>

                                        <div class="row g-4">
                                            {{-- URL Field (if applicable) --}}
                                            @if ($hasLink)
                                                @php
                                                    $urlCol = in_array(
                                                        $channel,
                                                        ['youtube', 'x', 'snapchat', 'marketing_evidence'],
                                                        true,
                                                    )
                                                        ? 'col-12'
                                                        : 'col-md-6';
                                                @endphp
                                                <x-form.controls.input :name="'campaigns[' . $channel . '][url]'" type="url" label="رابط الحملة"
                                                    :value="old('campaigns.' . $channel . '.url', $campaign?->url)" :col="$urlCol" required />
                                            @endif

                                            {{-- Date Field (if applicable) --}}
                                            @if ($hasDate)
                                                <x-form.controls.input :name="'campaigns[' . $channel . '][campaign_date]'" label="تاريخ الحملة" type="date"
                                                    :value="old(
                                                        'campaigns.' . $channel . '.campaign_date',
                                                        $campaign?->campaign_date?->format('Y-m-d'),
                                                    )" col="col-md-6" required />
                                            @endif

                                            {{-- Property Select (if applicable) --}}
                                            @if (in_array('property_id', $metrics, true))
                                                <x-form.controls.select :name="'campaigns[' . $channel . '][metrics][property_id]'" label="العقار" :options="$flatProperties"
                                                    :value="old(
                                                        'campaigns.' . $channel . '.metrics.property_id',
                                                        $campaign?->metrics['property_id'] ?? null,
                                                    )" data-campaign-metric="property_id" required
                                                    col="col-md-6" />
                                            @endif

                                            {{-- Metrics Fields --}}
                                            @foreach ($metrics as $metric)
                                                @continue($metric === 'property_id')

                                                @php
                                                    $metricCol = match (true) {
                                                        $channel === 'planning' && $metric === 'brochure_methodology'
                                                            => 'col-12',
                                                        $channel === 'google' && $metric === 'views' => 'col-12',
                                                        $channel === 'youtube' &&
                                                            in_array(
                                                                $metric,
                                                                ['views', 'subscribers', 'watch_hours'],
                                                                true,
                                                            )
                                                            => 'col-md-4',
                                                        $channel === 'x' &&
                                                            in_array($metric, ['views', 'impressions', 'posts'], true)
                                                            => 'col-md-4',
                                                        $channel === 'tiktok' &&
                                                            in_array(
                                                                $metric,
                                                                [
                                                                    'impressions',
                                                                    'reach',
                                                                    'profile_views',
                                                                    'profile_change',
                                                                    'reach_change',
                                                                    'views_change',
                                                                ],
                                                                true,
                                                            )
                                                            => 'col-md-4',
                                                        $channel === 'whatsapp' &&
                                                            in_array(
                                                                $metric,
                                                                [
                                                                    'messages',
                                                                    'delivered',
                                                                    'failed',
                                                                    'pending',
                                                                    'total',
                                                                    'views',
                                                                ],
                                                                true,
                                                            )
                                                            => 'col-md-4',
                                                        default => 'col-md-6',
                                                    };
                                                @endphp

                                                @if ($channel === 'brokers' && in_array($metric, ['display_name', 'broker_second_name'], true))
                                                    <x-form.controls.input :name="'campaigns[' . $channel . '][metrics][' . $metric . ']'" :label="$textFields[$metric] ?? ($metric === 'display_name' ? 'اسم الوسيط' : 'اسم الوسيط الثاني')"
                                                        type="text" :value="old(
                                                            'campaigns.' . $channel . '.metrics.' . $metric,
                                                            $campaign?->metrics[$metric] ?? null,
                                                        )" :data-campaign-metric="$metric"
                                                        :col="$metricCol" required />
                                                @elseif (array_key_exists($metric, $textFields))
                                                    <x-form.controls.textarea :name="'campaigns[' . $channel . '][metrics][' . $metric . ']'" :label="$textFields[$metric]"
                                                        :value="old(
                                                            'campaigns.' . $channel . '.metrics.' . $metric,
                                                            $campaign?->metrics[$metric] ?? null,
                                                        )" :data-campaign-metric="$metric" rows="3"
                                                        :col="$metricCol" required />
                                                @else
                                                    @php
                                                        $metricTitle =
                                                            $metric === 'views'
                                                                ? 'عدد المشاهدات'
                                                                : (__('auction_reports.metrics.' . $metric, [], 'ar') ?:
                                                                $metric);
                                                    @endphp
                                                    <x-form.controls.input :name="'campaigns[' . $channel . '][metrics][' . $metric . ']'" :label="$metricTitle"
                                                        type="number" :value="old(
                                                            'campaigns.' . $channel . '.metrics.' . $metric,
                                                            $campaign?->metrics[$metric] ?? null,
                                                        )" :data-campaign-metric="$metric"
                                                        :step="in_array($metric, $integerMetrics, true)
                                                            ? '1'
                                                            : 'any'" :min="in_array($metric, $signedMetrics, true) ? null : 0" :col="$metricCol" required />
                                                @endif
                                            @endforeach

                                            {{-- Evidence Upload Field (if applicable) --}}
                                            @if ($hasEvidence)
                                                @php
                                                    $evidenceCol = in_array(
                                                        $channel,
                                                        ['brochure', 'x', 'whatsapp'],
                                                        true,
                                                    )
                                                        ? 'col-12'
                                                        : 'col-md-6';
                                                @endphp
                                                <x-form.controls.file :name="'campaigns[' . $channel . '][evidence][]'" label="أدلة الحملة" multiple
                                                    accept=".pdf,.png,.jpg,.jpeg,.webp" help="صور أو PDF، حتى 10MB للملف"
                                                    :col="$evidenceCol" />
                                            @endif

                                            {{-- Existing Evidence Cards (if editing) --}}
                                            @if ($campaign && $campaign->evidence->isNotEmpty())
                                                @foreach ($campaign->evidence as $file)
                                                    <div class="col-md-6">
                                                        <x-ui.file-card :file="$file" />
                                                        <x-form.controls.checkbox :name="'campaigns[' . $channel . '][remove_evidence][]'" :id="'remove-evidence-' . $file->id"
                                                            :value="$file->id" label="حذف هذا الدليل عند الحفظ"
                                                            col="mt-3" />
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        {{-- Actions --}}
                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('marketing-campaigns.index')" />
                            <div class="d-flex gap-2">
                                <x-form.wizard.draft-button step="1" />
                                <x-form.wizard.save-button />
                            </div>
                        </x-form.wizard.actions>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
