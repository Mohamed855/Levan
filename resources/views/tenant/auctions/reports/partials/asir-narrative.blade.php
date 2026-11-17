@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-narrative-main" data-asir-flow>
    @foreach ($content['paragraphs'] as $paragraph)
        <section class="asir-narrative-block" data-asir-section><p data-asir-paragraph @if ($paragraph['role']) data-report-field="{{ $paragraph['role'] }}" @endif>{{ $paragraph['text'] }}</p></section>
    @endforeach
    @if ($content['index'] === 4)
        <section class="asir-audience" data-asir-section>
            <h3>الفئـــــات المستهدفة :</h3>
            <ul><li>المستثمرين</li><li>المطورين العقاريين</li><li>عملاء خاصين بالشركة</li><li>الأفراد المهتمين بالعقارات السكنية</li></ul>
        </section>
    @endif
</main>
