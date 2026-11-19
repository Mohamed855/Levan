@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-video-main" data-asir-flow>
    @if (($content['values']['video_context'] ?? '') !== '')
        <section data-asir-section><p class="asir-context" data-asir-paragraph data-report-field="video_context">{{ $content['values']['video_context'] }}</p></section>
    @endif
    @if (isset($content['channels']['youtube']['metrics']['views']))
        <section class="asir-video-views" data-asir-section><span>عدد المشاهدات :</span> <bdi data-report-field="youtube_views">{{ $content['channels']['youtube']['metrics']['views']['value'] }}</bdi></section>
    @endif
    @if ($content['image_items'] !== [] || $content['legacy'])
        <section class="asir-video-hero" data-asir-section data-asir-gap-min="10">
            @include('tenant.auctions.reports.partials.asir-thumbnail', ['image' => $content['image_items'][0] ?? null])
            <img class="asir-video-badge" src="{{ $content['youtube_icon'] }}" alt="YouTube">
        </section>
    @endif
    @if (isset($content['channels']['youtube']) || count($content['image_items']) > 1)
        <section class="asir-video-statistics" data-asir-section>
            <dl class="asir-video-metrics">
                @foreach ($content['channels']['youtube']['metrics'] ?? [] as $key => $metric)
                    <div><dt>{{ $metric['label'] }}</dt><dd data-report-field="youtube_{{ $key }}"><bdi>{{ $metric['value'] }}</bdi></dd></div>
                @endforeach
            </dl>
            @foreach (array_slice($content['image_items'], 1) as $image)
                @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'frameClass' => 'asir-video-evidence'])
            @endforeach
        </section>
    @endif
    @if (!empty($content['destinations']['video']))
        <section class="asir-link-section" data-asir-section data-asir-gap-min="6">@include('tenant.auctions.reports.partials.asir-link', ['url' => $content['destinations']['video'], 'role' => 'video', 'label' => 'رابط فيديو المزاد'])</section>
    @endif
</main>
