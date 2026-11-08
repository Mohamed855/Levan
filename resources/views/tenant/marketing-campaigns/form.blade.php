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

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenant/auctions/cycle-forms.js'])
@endsection

@section('content')
    <x-ui.validation-errors />
    <div class="row">
        <div class="col-12 mb-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-1">
                        {{ $is_edit ? 'تعديل الحملات التسويقية: ' . $auction->name : 'إضافة حملات تسويقية للمزاد' }}</h5>
                    @if ($is_edit && $auction->assignment)
                        <span class="text-muted">{{ $auction->assignment?->assignment_number }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <form id="report-cycle-form" method="POST" enctype="multipart/form-data" autocomplete="off"
                        data-form-validate
                        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                        @if (!$is_edit)
                            data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                            data-draft-file-url="{{ tenant_route_name('wizard-drafts.file.destroy') }}"
                            data-draft-resource-key="tenant.marketing-campaign-create"
                        @endif
                        action="{{ $is_edit ? tenant_route_name('marketing-campaigns.update', $auction->id) : tenant_route_name('marketing-campaigns.store') }}"
                        data-campaign-metrics='@json($channelDefinitions)'
                        data-campaign-fields='@json($channelFields)'
                        data-campaign-properties='@json($propertyOptions)'>
                        @csrf
                        @if ($is_edit)
                            @method('PUT')
                        @endif

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

                        {{-- Campaign Channel Boxes (Same style as auction-closings) --}}
                        <div id="campaign-boxes-wrapper">
                            @foreach ($channelDefinitions as $channel => $metrics)
                                @php
                                    $channelLabel = $channelLabels[$channel] ?? $channel;
                                    $campaign = $existingCampaigns->get($channel);
                                    $hasLink = in_array($channel, $linkChannels, true);
                                    $hasEvidence = in_array($channel, $evidenceChannels, true);
                                    $hasDate = $channel === 'whatsapp';
                                    $isSelectedChannel = in_array(
                                        $channel,
                                        (array) old('channels', $selectedChannels),
                                        true,
                                    );
                                @endphp

                                <section
                                    class="border rounded p-4 mb-4 campaign-channel-box {{ $isSelectedChannel ? '' : 'd-none' }}"
                                    id="channel-box-{{ $channel }}" data-channel="{{ $channel }}"
                                    {!! $isSelectedChannel ? '' : 'style="display: none;"' !!}>
                                    <h6 class="fw-bold mb-4">{{ $channelLabel }}</h6>

                                    <div class="row g-4">
                                        {{-- URL Field (if applicable) --}}
                                        @if ($hasLink)
                                            @php
                                                $urlCol = in_array($channel, ['youtube', 'x', 'snapchat', 'tiktok', 'whatsapp', 'marketing_evidence'], true)
                                                    ? 'col-12'
                                                    : 'col-md-6';
                                            @endphp
                                            <x-form.controls.input :name="'campaigns[' . $channel . '][url]'" type="url" label="رابط الحملة"
                                                :value="old('campaigns.' . $channel . '.url', $campaign?->url)" :col="$urlCol" />
                                        @endif

                                        {{-- Date Field (if applicable) --}}
                                        @if ($hasDate)
                                            @php
                                                $dateCol = $channel === 'whatsapp' ? 'col-md-4' : 'col-md-6';
                                            @endphp
                                            <x-form.controls.input :name="'campaigns[' . $channel . '][campaign_date]'" label="تاريخ الحملة" type="date"
                                                :value="old(
                                                    'campaigns.' . $channel . '.campaign_date',
                                                    $campaign?->campaign_date?->format('Y-m-d'),
                                                )" :col="$dateCol" />
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
                                                    $channel === 'google' && $metric === 'views' => 'col-12',
                                                    $channel === 'youtube' && in_array($metric, ['views', 'subscribers', 'watch_hours'], true) => 'col-md-4',
                                                    $channel === 'x' && in_array($metric, ['views', 'impressions', 'posts'], true) => 'col-md-4',
                                                    $channel === 'tiktok' && in_array($metric, ['views', 'impressions', 'reach', 'profile_views', 'profile_change', 'reach_change', 'views_change'], true) => 'col-md-4',
                                                    $channel === 'whatsapp' && in_array($metric, ['messages', 'delivered', 'failed', 'pending', 'total', 'views'], true) => 'col-md-4',
                                                    default => 'col-md-6',
                                                };
                                            @endphp

                                            @if ($channel === 'brokers' && in_array($metric, ['display_name', 'broker_second_name'], true))
                                                <x-form.controls.input :name="'campaigns[' . $channel . '][metrics][' . $metric . ']'" :label="$textFields[$metric] ?? ($metric === 'display_name' ? 'اسم الوسيط' : 'اسم الوسيط الثاني')"
                                                    type="text" :value="old(
                                                        'campaigns.' . $channel . '.metrics.' . $metric,
                                                        $campaign?->metrics[$metric] ?? null,
                                                    )" :data-campaign-metric="$metric" :col="$metricCol" />
                                            @elseif (array_key_exists($metric, $textFields))
                                                <x-form.controls.textarea :name="'campaigns[' . $channel . '][metrics][' . $metric . ']'" :label="$textFields[$metric]"
                                                    :value="old(
                                                        'campaigns.' . $channel . '.metrics.' . $metric,
                                                        $campaign?->metrics[$metric] ?? null,
                                                    )" :data-campaign-metric="$metric" rows="3" :col="$metricCol" />
                                            @else
                                                @php
                                                    $metricTitle =
                                                        $metric === 'views'
                                                            ? 'عدد المشاهدات'
                                                            : (__('auction_reports.metrics.' . $metric, [], 'ar') ?:
                                                            $metric);
                                                @endphp
                                                <x-form.controls.input :name="'campaigns[' . $channel . '][metrics][' . $metric . ']'" :label="$metricTitle" type="number"
                                                    :value="old(
                                                        'campaigns.' . $channel . '.metrics.' . $metric,
                                                        $campaign?->metrics[$metric] ?? null,
                                                    )" :data-campaign-metric="$metric" :step="in_array($metric, $integerMetrics, true) ? '1' : 'any'"
                                                    :min="in_array($metric, $signedMetrics, true) ? null : 0" :col="$metricCol" />
                                            @endif
                                        @endforeach

                                        {{-- Evidence Upload Field (if applicable) --}}
                                        @if ($hasEvidence)
                                            <x-form.controls.file :name="'campaigns[' . $channel . '][evidence][]'" label="أدلة الحملة" multiple
                                                accept=".pdf,.png,.jpg,.jpeg,.webp" help="صور أو PDF، حتى 10MB للملف"
                                                col="col-md-6" />
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
                                </section>
                            @endforeach
                        </div>

                        {{-- Actions --}}
                        <x-form.wizard.actions>
                            <x-form.wizard.previous-button :href="tenant_route_name('marketing-campaigns.index')" text="العودة" />
                            <div class="d-flex gap-2">
                                @if (!$is_edit)
                                    <x-form.wizard.draft-button step="1" />
                                @endif
                                <x-form.wizard.save-button text="حفظ الحملات التسويقية" />
                            </div>
                        </x-form.wizard.actions>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
