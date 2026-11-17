<div class="asir-platform-hero">
    <div class="asir-platform-picture">
        @if (isset($image))
            @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'frameClass' => 'asir-thumbnail-image', 'showCaption' => isset($image['document_page'])])
        @endif
    </div>
    <div class="asir-platform-brand">
        @if ($content['issuing_logo'])<img src="{{ $content['issuing_logo'] }}" alt="" data-report-branding="issuing_entity_logo">@endif
        <span>{{ $content['values']['auction_type'] }}</span>
        <div class="asir-platform-name"><span>مزاد</span><strong>{{ $content['values']['name'] }}</strong></div>
    </div>
</div>
