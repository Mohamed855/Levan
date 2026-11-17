@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-gallery-main" data-asir-flow>
    @foreach ($content['values'] as $role => $value)
        @if (str_starts_with($role, 'campaign_metric_') && $value !== '')
            <section data-asir-section><p data-asir-paragraph>{{ $value }}</p></section>
        @endif
    @endforeach
    @if ($content['image_items'] !== [])
        <section data-asir-section><div class="asir-gallery-grid" data-asir-grid>
            @foreach ($content['image_items'] as $slot => $image)
                @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'showCaption' => true, 'captionOverride' => $content['values']['gallery_caption_'.$slot] ?? null])
            @endforeach
        </div></section>
    @endif
    @if (!empty($content['link_destinations']['campaign']))
        <section class="asir-link-section" data-asir-section>@include('tenant.auctions.reports.partials.asir-link', ['url' => $content['link_destinations']['campaign'], 'role' => 'campaign', 'label' => 'رابط الحملة'])</section>
    @endif
</main>
