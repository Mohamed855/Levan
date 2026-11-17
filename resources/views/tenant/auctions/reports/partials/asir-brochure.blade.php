@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-marketing-main asir-brochure-main" data-asir-flow>
    @if (($content['values']['brochure_context'] ?? '') !== '')
        <section data-asir-section><p class="asir-context" data-asir-paragraph data-report-field="brochure_context">{{ $content['values']['brochure_context'] }}</p></section>
    @endif
    @if ($content['image_items'] !== [])
        <section data-asir-section><div class="asir-brochure-grid" data-asir-grid>
            @foreach ($content['image_items'] as $image)
                @include('tenant.auctions.reports.partials.asir-image', ['image' => $image, 'frameClass' => 'asir-brochure-image'])
            @endforeach
        </div></section>
    @endif
    @if (!empty($content['destinations']['brochure']))
        <section class="asir-link-section" data-asir-section>@include('tenant.auctions.reports.partials.asir-link', ['url' => $content['destinations']['brochure'], 'role' => 'brochure', 'label' => 'رابط الكتيب'])</section>
    @endif
</main>
