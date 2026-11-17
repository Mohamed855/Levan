@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-brokers-main" data-asir-flow>
    @php($brokerUrl = null)
    @foreach ($content['campaigns'] as $campaign)
        @if (($campaign['platform'] ?? '') === 'brokers')
            @php($brokerUrl = (new \App\Services\Tenants\Auctions\Reports\AuctionReportLinks)->safeUrl($campaign['url'] ?? null))
            @break
        @endif
    @endforeach
    @foreach (['broker_1', 'broker_2'] as $role)
        @if (($content['values'][$role] ?? '') !== '' && $content['values'][$role] !== '-')
            <section class="asir-broker-name" data-asir-section>
                @if ($brokerUrl && $role === 'broker_1')<a href="{{ $brokerUrl }}" data-link-role="brokers">@endif
                <p data-asir-paragraph data-report-field="{{ $role }}">{{ $content['values'][$role] }}</p>
                @if ($brokerUrl && $role === 'broker_1')</a>@endif
            </section>
        @endif
    @endforeach
    @if (($content['values']['contract_text'] ?? '') !== '')
        <section class="asir-contract" data-asir-section><p data-asir-paragraph data-report-field="contract_text">{{ $content['values']['contract_text'] }}</p></section>
    @endif
    @if ($content['image_items'] !== [])
        <section data-asir-section><div class="asir-broker-grid" data-asir-grid>
            @foreach ($content['image_items'] as $image)
                <div class="asir-broker-document" data-asir-item>
                    @if ($content['company_logo'])<img class="asir-broker-letterhead" src="{{ $content['company_logo'] }}" alt="" aria-hidden="true">@endif
                    @if ($content['broker_watermark'])<span class="asir-broker-watermark" style="mask-image:url('{{ $content['broker_watermark'] }}')" aria-hidden="true"></span>@endif
                    @if ($content['broker_pattern'])<span class="asir-broker-pattern" style="mask-image:url('{{ $content['broker_pattern'] }}')" aria-hidden="true"></span>@endif
                    @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'frameClass' => 'asir-broker-image'])
                </div>
            @endforeach
        </div></section>
    @endif
</main>
