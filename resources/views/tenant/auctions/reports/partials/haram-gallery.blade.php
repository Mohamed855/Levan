@include('tenant.auctions.reports.partials.haram-header')
<main class="haram-native-main haram-gallery-main" data-native-flow>
    <section class="haram-index-section" data-native-section>
        <h2>{{ $content['gallery_title'] }}</h2>
        @if (isset($content['campaign_metrics']))
            <dl class="haram-extra-metrics">
                @foreach ($content['campaign_metrics'] as $metric => [$label, $value])<div><dt>{{ $label }}:</dt> <dd data-report-field="campaign_metric_{{ $metric }}">{{ $value }}</dd></div>@endforeach
            </dl>
            @if ($content['campaign_url'])<div class="haram-native-link-card"><a href="{{ $content['campaign_url'] }}" data-link-role="campaign">{{ __('auction_reports.campaign_link', [], 'ar') }}</a></div>@endif
        @endif
        <div class="haram-gallery-grid" data-native-grid>
            @foreach ($content['images']['gallery'] ?? [] as $slot => $image)
                <figure class="haram-gallery-frame">
                    @include('tenant.auctions.reports.partials.haram-image', ['image' => $image, 'label' => $content['gallery_title'], 'imageClass' => ''])
                    <figcaption>@if (! empty($image['channel'])){{ __('auction_reports.channels.'.\App\Services\Tenants\Auctions\Reports\AuctionReportChannels::key($image['channel']), [], 'ar') }} - @endif{{ ($image['display_name'] ?? '') ?: __('auction_reports.attachment', [], 'ar').' '.($slot + 1) }}@if (isset($image['document_page'])) - {{ (int) $image['document_page'] }}@endif</figcaption>
                </figure>
            @endforeach
        </div>
    </section>
</main>
@include('tenant.auctions.reports.partials.haram-footer')
