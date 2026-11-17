@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-social-main" data-asir-flow>
    @if (($content['values']['social_context'] ?? '') !== '')
        <section data-asir-section><p class="asir-context" data-asir-paragraph data-report-field="social_context">{{ $content['values']['social_context'] }}</p></section>
    @endif
    @if (array_intersect_key($content['channels'], array_flip(['snapchat', 'tiktok', 'x'])) !== [])
        <section data-asir-section><div class="asir-social-grid" data-asir-grid>
            @foreach (['snapchat', 'tiktok', 'x'] as $channel)
                @if (isset($content['channels'][$channel]))
                    @php($details = $content['channels'][$channel])
                    <article class="asir-social-card" data-asir-item data-asir-channel="{{ $channel }}">
                        @foreach ($details['images'] as $image)
                            @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'frameClass' => 'asir-social-image'])
                        @endforeach
                        <div class="asir-social-label">
                            @if ($details['url'])<a href="{{ $details['url'] }}" data-link-role="{{ $channel }}">@endif
                            <span class="asir-social-icon" aria-hidden="true"><img src="{{ $content['icons'][$channel] }}" alt=""></span>
                            @foreach ($details['metrics'] as $key => $metric)
                                <div><strong data-report-field="{{ $channel }}_{{ $key }}">{{ $metric['value'] }}</strong>@if (!in_array($key, ['views', 'impressions'], true))<small>{{ $metric['label'] }}</small>@endif</div>
                            @endforeach
                            <small>مشاهدات عبر {{ $details['label'] }}</small>
                            @if ($details['url'])</a>@endif
                        </div>
                    </article>
                @endif
            @endforeach
        </div></section>
    @endif
    @if (($content['values']['social_total'] ?? '') !== '')
        <section class="asir-social-total" data-asir-section><span>بمجمل مشاهدات</span><strong data-report-field="social_total">{{ $content['values']['social_total'] }}</strong></section>
    @endif
</main>
