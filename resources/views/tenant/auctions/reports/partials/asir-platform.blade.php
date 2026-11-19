@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-platform-main" data-asir-flow>
    @if (($content['values']['platform_context'] ?? '') !== '')
        <section data-asir-section><p class="asir-context" data-asir-paragraph data-report-field="platform_context">{{ $content['values']['platform_context'] }}</p></section>
    @endif
    <section class="asir-platform-card" data-asir-section data-asir-gap-min="35">
        @include('tenant.auctions.reports.partials.asir-thumbnail', ['image' => $content['image_items'][0] ?? null])
        <div class="asir-platform-details">
            <div class="asir-platform-count"><span>عدد المنتجات</span><strong>{{ $content['values']['property_count'] }}</strong></div>
            <div><small data-report-field="duration">{{ $content['values']['duration'] }}</small><p data-report-field="date_range">{{ $content['values']['date_range'] }}</p><p>{{ $content['values']['end_time_short'] }}</p></div>
            <div class="asir-platform-bottom">
                <strong class="asir-platform-status {{ $content['status_badge_class'] }}">{{ $content['values']['status'] }}</strong>
                @if ($content['company_logo'])<img src="{{ $content['company_logo'] }}" alt="" data-report-branding="company_logo">@endif
            </div>
        </div>
    </section>
    @if (count($content['image_items']) > 1)
        <section data-asir-section><div class="asir-platform-evidence" data-asir-grid>
            @foreach (array_slice($content['image_items'], 1) as $image)
                @include('tenant.auctions.reports.partials.asir-image', ['image' => $image])
            @endforeach
        </div></section>
    @endif
    @if (!empty($content['destinations']['platform']))
        <section class="asir-link-section" data-asir-section data-asir-gap-min="6">@include('tenant.auctions.reports.partials.asir-link', ['url' => $content['destinations']['platform'], 'role' => 'platform', 'label' => $content['values']['platform_url']])</section>
    @endif
</main>
