@include('tenant.auctions.reports.partials.haram-header')
<main class="haram-native-main haram-marketing-main" data-native-flow>
    @if (array_filter($content['channels'], fn ($channel) => $channel['summary'] !== null) !== [])
        <section class="haram-index-section haram-marketing-summary" data-native-section>
            <h2>الترويج عبر حسابات الشركة في السوشيال ميديا</h2>
            <div class="haram-summary-grid">
                @foreach (['snapchat' => 'إجمالي مشاهدات', 'tiktok' => 'مشاهدات', 'x' => 'مشاهدة التغريدات'] as $channel => $caption)
                    @if (($content['channels'][$channel]['summary'] ?? null) !== null)
                        <div class="haram-summary-card" data-native-summary="{{ $channel }}" @if ($channel !== 'snapchat' && ! $content['channels'][$channel]['has_details']) data-haram-channel="{{ $channel }}" @endif>
                            <div class="haram-summary-platform">@if ($channel === 'x')<span class="haram-platform-icon" aria-hidden="true">X</span>@else<img class="haram-platform-icon" src="data:image/svg+xml;base64,{{ base64_encode(file_get_contents(resource_path('auction-reports/haram/icons/'.$channel.'.svg'))) }}" alt="">@endif منصة {{ $content['channels'][$channel]['label'] }}</div>
                            <strong data-report-field="{{ $channel }}_{{ $content['channels'][$channel]['summary_key'] }}">{{ $content['channels'][$channel]['summary'] }}</strong>
                            <span>{{ $caption }}</span>
                        </div>
                    @endif
                @endforeach
            </div>
        </section>
    @endif
    @foreach ($content['channels'] as $channel => $section)
        @php
            $detailMetrics = $section['metrics'];
            if ($section['summary_key'] !== null) unset($detailMetrics[$section['summary_key']]);
        @endphp
        @if ($channel !== 'snapchat' && $section['has_details'])
            <section class="haram-index-section haram-marketing-section haram-marketing-section--{{ $channel }}" data-native-section data-haram-channel="{{ $channel }}">
                <h2>{{ $channel === 'x' ? 'نموذج التغريدة - X منصة' : 'منصة تيك توك - إحصائيات الوصول' }}</h2>
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
                @endif
                @foreach ($section['images'] as $image)
                    <figure class="haram-marketing-image-frame" data-native-marketing-image>
                        @include('tenant.auctions.reports.partials.haram-image', ['image' => $image, 'label' => $section['label'], 'imageClass' => 'haram-marketing-image'])
                    </figure>
                @endforeach
                @if ($detailMetrics !== [])
                    <dl class="haram-extra-metrics">
                        @foreach ($detailMetrics as $metric => [$label, $value])<div><dt>{{ $label }}</dt><dd data-report-field="{{ $channel }}_{{ $metric }}">{{ $value }}</dd></div>@endforeach
                    </dl>
                @endif
                @if ($section['url'])
                    <div class="haram-native-link-card"><strong>{{ $channel === 'x' ? 'رابط التغريدة' : 'رابط تيك توك' }} :</strong><a href="{{ $section['url'] }}" data-link-role="{{ $channel }}">{{ $channel === 'x' ? 'رابط حملة X' : 'رابط حملة تيك توك' }}</a></div>
                @endif
            </section>
        @endif
    @endforeach
    @php($snapchat = $content['channels']['snapchat'] ?? null)
    @if ($snapchat || ($content['destinations']['marketing_evidence'] ?? null) || ($content['destinations']['boards'] ?? null))
        <section class="haram-index-section haram-marketing-section haram-marketing-section--snapchat" data-native-section @if ($snapchat) data-haram-channel="snapchat" @endif>
            <h2>{{ $snapchat ? 'منصة سناب شات' : 'أدلة التسويق' }}</h2>
            <div class="haram-snapchat-grid">
                @if (! empty($snapchat['images']))
                    <div class="haram-snapchat-images">
                        @foreach ($snapchat['images'] as $image)
                            <figure class="haram-snapchat-image-frame" data-native-marketing-image>
                                @include('tenant.auctions.reports.partials.haram-image', ['image' => $image, 'label' => 'سناب شات', 'imageClass' => 'haram-marketing-image'])
                            </figure>
                        @endforeach
                    </div>
                @endif
                <div class="haram-snapchat-details">
                    @if ($snapchat)
                        @foreach ($snapchat['metrics'] as $metric => [$label, $value])
                            <div class="haram-snapchat-card"><span>{{ $metric === 'impressions' ? 'عدد مشاهدات السناب:' : $label }}</span><div><strong data-report-field="snapchat_{{ $metric }}">{{ $value }}</strong>@if ($metric === 'impressions')<span>ألف مشاهدة</span>@endif</div></div>
                        @endforeach
                        @if ($snapchat['url'])<div class="haram-snapchat-card"><a href="{{ $snapchat['url'] }}" data-link-role="snapchat">رابط حملة سناب شات</a></div>@endif
                    @endif
                    @foreach (['marketing_evidence' => ['البوستات العامة للمزاد :', 'رابط أدلة التسويق'], 'boards' => ['رابط تركيب اللوحات وفكها :', 'رابط اللوحات']] as $role => [$label, $linkLabel])
                        @if ($content['destinations'][$role] ?? null)<div class="haram-snapchat-card"><span>{{ $label }}</span><a href="{{ $content['destinations'][$role] }}" data-link-role="{{ $role }}">{{ $linkLabel }}</a></div>@endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</main>
@include('tenant.auctions.reports.partials.haram-footer')
