@include('tenant.auctions.reports.partials.haram-header')
<main class="haram-native-main haram-brokers-main" data-native-flow data-native-brokers>
    <section class="haram-index-section" data-native-section>
        <h2>التسويق عبر الوسطاء العقاريين</h2>
        <div class="haram-brokers-grid" data-native-grid>
            @foreach ($content['images']['brokers'] ?? [] as $slot => $image)
                <figure class="haram-broker-frame">
                    <figcaption>{{ ($image['display_name'] ?? '') ?: ($content['values']['broker_'.($slot + 1)] ?? '') }}</figcaption>
                    @include('tenant.auctions.reports.partials.haram-image', ['image' => $image, 'label' => 'التسويق عبر الوسطاء العقاريين', 'imageClass' => ''])
                </figure>
            @endforeach
        </div>
        @if (empty($content['images']['brokers']))
            @foreach (['broker_1', 'broker_2', 'broker_3'] as $role)
                @if (filled($content['values'][$role] ?? null))<p class="haram-broker-name" data-native-paragraph>{{ $content['values'][$role] }}</p>@endif
            @endforeach
        @endif
    </section>
</main>
@include('tenant.auctions.reports.partials.haram-footer')
