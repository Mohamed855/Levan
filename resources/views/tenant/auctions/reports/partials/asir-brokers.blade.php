@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-brokers-main" data-asir-flow>
    @php($brokerUrl = null)
    @foreach ($content['campaigns'] as $campaign)
        @if (($campaign['platform'] ?? '') === 'brokers')
            @php($brokerUrl = (new \App\Services\Tenants\Auctions\Reports\AuctionReportLinks)->safeUrl($campaign['url'] ?? null))
            @break
        @endif
    @endforeach
    @foreach ($content['broker_names'] as $index => $name)
        <section class="asir-broker-name" data-asir-section @if ($content['image_items'] !== []) data-asir-repeat @endif>
            @if ($brokerUrl && $index === 0)<a href="{{ $brokerUrl }}" data-link-role="brokers">@endif
            <p data-asir-paragraph data-report-field="broker_{{ $index + 1 }}">{{ $name }}</p>
            @if ($brokerUrl && $index === 0)</a>@endif
        </section>
    @endforeach
    @if (($content['values']['contract_text'] ?? '') !== '')
        <section class="asir-contract" data-asir-section><p data-asir-paragraph data-report-field="contract_text">{{ $content['values']['contract_text'] }}</p></section>
    @endif
    @if ($content['image_items'] !== [])
        <section data-asir-section data-asir-gap-min="6"><div class="asir-broker-grid" data-asir-grid>
            @foreach ($content['image_items'] as $image)
                <div class="asir-broker-document" data-asir-item>
                    @if ($content['company_logo'])<img class="asir-broker-letterhead" src="{{ $content['company_logo'] }}" alt="" aria-hidden="true">@endif
                    @if ($content['broker_watermark'])<img class="asir-broker-watermark" src="{{ $content['broker_watermark'] }}" alt="" aria-hidden="true">@endif
                    @if (!$content['broker_watermark'] && $content['company_logo'])<img class="asir-broker-watermark-image" src="{{ $content['company_logo'] }}" alt="" aria-hidden="true">@endif
                    @if ($content['broker_pattern'])<img class="asir-broker-pattern" src="{{ $content['broker_pattern'] }}" alt="" aria-hidden="true">@endif
                    @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'frameClass' => 'asir-broker-image'])
                </div>
            @endforeach
        </div></section>
    @endif
</main>
