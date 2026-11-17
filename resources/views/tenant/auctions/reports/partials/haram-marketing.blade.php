@include('tenant.auctions.reports.partials.haram-header')
<main class="haram-native-main haram-marketing-main" data-native-flow>
    @foreach ($content['channels'] as $channel => $section)
        @php($detailMetrics = $section['metrics'])
        <section class="haram-index-section haram-marketing-section haram-marketing-section--{{ $channel }}" data-native-section data-haram-channel="{{ $channel }}">
            <h2>منصة {{ $section['label'] }}</h2>
            <div data-native-marketing-items>
                @if ($channel === 'tiktok' && array_intersect_key($section['metrics'], array_flip(['profile_views', 'reach', 'views'])) !== [])
                    <div class="haram-tiktok-panel">
                        <h3>الوصول</h3>
                        <div class="haram-tiktok-grid">
                            @foreach (['profile_views' => 'مشاهدات الملف الشخصي', 'reach' => 'الجمهور الذي تم الوصول إليه', 'views' => 'مشاهدات الفيديو'] as $metric => $label)
                                @if (isset($section['metrics'][$metric]))
                                    <div class="haram-tiktok-tile haram-tiktok-tile--{{ $metric }}">
                                        <span>{{ $label }}</span><strong data-report-field="tiktok_{{ $metric }}">{{ $section['metrics'][$metric][1] }}</strong>
                                        @php($change = ['profile_views' => 'profile_change', 'reach' => 'reach_change', 'views' => 'views_change'][$metric])
                                        @if (isset($section['metrics'][$change]))<span data-report-field="tiktok_{{ $change }}">{{ $section['metrics'][$change][1] }}%</span>@endif
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @if (filled($content['values']['date_range']))<div class="haram-tiktok-period">مشاهدات الفيديو<br><span>{{ $content['values']['date_range'] }}</span></div>@endif
                    </div>
                    @php($detailMetrics = array_diff_key($detailMetrics, array_flip(['profile_views', 'reach', 'views', 'profile_change', 'reach_change', 'views_change'])))
                @elseif ($section['summary'] !== null)
                    <div class="haram-summary-grid">
                        <div class="haram-summary-card" data-native-summary="{{ $channel }}">
                            <div class="haram-summary-platform">@if ($channel === 'x')<span class="haram-platform-icon" aria-hidden="true">X</span>@else<img class="haram-platform-icon" src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(resource_path('auction-reports/haram/icons/'.$channel.'.svg'))) }}" alt="">@endif منصة {{ $section['label'] }}</div>
                            <strong data-report-field="{{ $channel }}_{{ $section['summary_key'] }}">{{ $section['summary'] }}</strong>
                            <span>{{ $section['metrics'][$section['summary_key']][0] }}</span>
                        </div>
                    </div>
                    @php($detailMetrics = array_diff_key($detailMetrics, [$section['summary_key'] => true]))
                @endif
                @if ($detailMetrics !== [])
                    <dl class="haram-extra-metrics">
                        @foreach ($detailMetrics as $metric => [$label, $value])<div><dt>{{ $label }}</dt><dd data-report-field="{{ $channel }}_{{ $metric }}">{{ $value }}</dd></div>@endforeach
                    </dl>
                @endif
                @foreach ($section['images'] as $image)
                    <figure class="{{ $channel === 'snapchat' ? 'haram-snapchat-image-frame' : 'haram-marketing-image-frame' }}" data-native-marketing-image>
                        @include('tenant.auctions.reports.partials.haram-image', ['image' => $image, 'label' => $section['label'], 'imageClass' => 'haram-marketing-image'])
                    </figure>
                @endforeach
                @if ($section['url'])<div class="haram-native-link-card"><strong>رابط الحملة :</strong><a href="{{ $section['url'] }}" data-link-role="{{ $channel }}">رابط حملة {{ $section['label'] }}</a></div>@endif
            </div>
        </section>
    @endforeach
    @if (($content['destinations']['marketing_evidence'] ?? null) || ($content['destinations']['boards'] ?? null))
        <section class="haram-index-section haram-marketing-section" data-native-section data-native-marketing-links>
            <h2>أدلة التسويق</h2>
            <div data-native-marketing-items>
                @foreach (['marketing_evidence' => ['البوستات العامة للمزاد :', 'رابط أدلة التسويق'], 'boards' => ['رابط تركيب اللوحات وفكها :', 'رابط اللوحات']] as $role => [$label, $linkLabel])
                    @if ($content['destinations'][$role] ?? null)<div class="haram-snapchat-card"><span>{{ $label }}</span><a href="{{ $content['destinations'][$role] }}" data-link-role="{{ $role }}">{{ $linkLabel }}</a></div>@endif
                @endforeach
            </div>
        </section>
    @endif
</main>
@include('tenant.auctions.reports.partials.haram-footer')
