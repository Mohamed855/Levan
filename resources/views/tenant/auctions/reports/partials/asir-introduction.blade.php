@include('tenant.auctions.reports.partials.asir-header')
@if ($content['company_logo'])
    <div class="asir-introduction-logo"><img src="{{ $content['company_logo'] }}" alt="" data-report-branding="company_logo"></div>
@endif
<main class="asir-introduction-main" data-asir-flow>
    @if (($content['values']['introduction'] ?? '') !== '')
        <section data-asir-section><p data-asir-paragraph data-report-field="introduction">{{ $content['values']['introduction'] }}</p></section>
    @endif
</main>
