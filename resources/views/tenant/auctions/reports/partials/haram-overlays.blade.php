@if (! empty($content['cover_identity']))
    <div class="haram-cover-identity">@foreach ($content['cover_identity'] as $identity)<span>{{ $identity }}</span>@endforeach</div>
@endif
@if (isset($content['cards']))
    <div class="haram-cards">
        @foreach ($content['cards'] as $role => [$label, $value])
            <div class="haram-card" data-haram-card="{{ $role }}"><span class="haram-card-label">{{ $label }}</span><span class="haram-card-value">{{ $value }}</span></div>
        @endforeach
    </div>
@endif
@if (isset($content['contacts']))
    <div class="haram-contacts">
        @foreach (['phone', 'unified_phone'] as $role)
            @if (filled($content['contacts'][$role] ?? null) && $content['contacts'][$role] !== '-')
                <div class="haram-contact" data-haram-contact="{{ $role }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M5 3h4l2 5-3 2a14 14 0 0 0 6 6l2-3 5 2v4a2 2 0 0 1-2 2C10 21 3 14 3 5a2 2 0 0 1 2-2Z"/></svg>
                    @if ($content['contact_links'][$role] ?? null)
                        <a dir="ltr" href="{{ $content['contact_links'][$role] }}" data-link-role="{{ $role }}">{{ $content['contacts'][$role] }}</a>
                    @else
                        <span dir="ltr">{{ $content['contacts'][$role] }}</span>
                    @endif
                </div>
            @endif
        @endforeach
    </div>
    <div class="haram-social">
        @if ($content['contact_links']['social'] ?? null)
            <a href="{{ $content['contact_links']['social'] }}" data-link-role="social">{{ $content['contacts']['social'] }}</a>
        @else
            {{ $content['contacts']['social'] ?? '' }}
        @endif
    </div>
@endif
@if (isset($content['marketing_sections']))
    <div class="haram-body">
        @foreach ($content['marketing_sections'] as $channel => $section)
            <section class="haram-marketing-section" data-haram-channel="{{ $channel }}">
                <h2>{{ $section['label'] }}</h2>
                <div class="haram-marketing-content">
                    @if ($section['metrics'] !== [])
                        <div class="haram-marketing-metrics">
                            @foreach ($section['metrics'] as $metric => [$label, $value])
                                <div class="haram-metric">{{ $label }}<strong data-report-field="{{ $channel }}_{{ $metric }}">{{ $value }}</strong></div>
                            @endforeach
                        </div>
                    @endif
                    @foreach ($section['images'] as $image)
                        @include('tenant.auctions.reports.partials.haram-image', ['image' => $image, 'label' => $section['label'], 'imageClass' => 'haram-marketing-image'])
                    @endforeach
                </div>
                @if ($section['url'])
                    <a class="haram-marketing-link" href="{{ $section['url'] }}" data-link-role="{{ $channel }}">رابط {{ $section['label'] }}</a>
                @endif
            </section>
        @endforeach
    </div>
@endif
@if (isset($content['boards']))
    <div class="haram-body">
        @foreach (['board_before' => 'نماذج تركيب اللوحات', 'board_after' => 'فك اللوحات'] as $section => $label)
            @if (! empty($content['boards'][$section]) || ($section === 'board_after' && filled($content['removal_date'])))
                <section class="haram-board-section" data-haram-board-section="{{ $section }}">
                    <h2>{{ $section === 'board_after' && filled($content['removal_date']) ? $content['removal_date'] : $label }}</h2>
                    <div class="haram-board-images">
                        @foreach ($content['boards'][$section] ?? [] as $image)
                            @include('tenant.auctions.reports.partials.haram-image', ['image' => $image, 'label' => $label, 'imageClass' => ''])
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach
    </div>
@endif
