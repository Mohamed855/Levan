@php($methodology = in_array($content['index'], [4, 5, 6], true))
@include('tenant.auctions.reports.partials.asir-header', ['content' => $methodology ? array_replace($content, ['section_title' => '']) : $content])
<main class="asir-narrative-main" data-asir-flow>
    @foreach ($content['paragraphs'] as $paragraph)
        <section data-asir-section>
            @if ($methodology && $loop->first)
                <h2 class="asir-methodology-heading">{{ $content['section_title'] }}</h2>
            @endif
            <div class="asir-narrative-block"><p data-asir-paragraph @if ($paragraph['role']) data-report-field="{{ $paragraph['role'] }}" @endif>{{ $paragraph['text'] }}</p></div>
        </section>
    @endforeach
</main>
