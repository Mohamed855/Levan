@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-narrative-main" data-asir-flow>
    @foreach ($content['paragraphs'] as $paragraph)
        <section class="asir-narrative-block" data-asir-section><p data-asir-paragraph @if ($paragraph['role']) data-report-field="{{ $paragraph['role'] }}" @endif>{{ $paragraph['text'] }}</p></section>
    @endforeach
</main>
