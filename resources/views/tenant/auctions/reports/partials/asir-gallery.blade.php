@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-gallery-main {{ $content['gallery_campaign'] ? 'asir-gallery-main--campaign' : '' }}" data-asir-flow>
    @if ($content['gallery_campaign'])
        <section class="asir-campaign-heading" data-asir-section data-asir-repeat><h2>{{ $content['values']['campaign_heading'] }}</h2></section>
    @endif
    @foreach ($content['values'] as $role => $value)
        @if (str_starts_with($role, 'campaign_metric_') && $value !== '')
            <section data-asir-section><p data-asir-paragraph>{{ $value }}</p></section>
        @endif
    @endforeach
    @if ($content['image_items'] !== [])
        <section data-asir-section><div class="asir-gallery-grid {{ $content['gallery_single_broker'] ? 'asir-gallery-grid--broker' : '' }}" data-asir-grid>
            @foreach ($content['image_items'] as $slot => $image)
                @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'showCaption' => true, 'captionOverride' => $content['values']['gallery_caption_'.$slot] ?? null])
            @endforeach
        </div></section>
    @endif
    @if (!empty($content['link_destinations']['campaign']))
        <section class="asir-link-section" data-asir-section><a class="asir-campaign-link" href="{{ $content['link_destinations']['campaign'] }}" data-link-role="campaign">رابط الحملة</a></section>
    @endif
</main>
