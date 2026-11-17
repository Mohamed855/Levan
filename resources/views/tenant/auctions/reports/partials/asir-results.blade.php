@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-results-main" data-asir-flow>
    <section class="asir-results-summary" data-asir-section data-asir-repeat>
        <h3>{{ $content['index'] === 18 ? 'الأصول المباعة :' : 'الأصول الغير مباعة :' }}</h3>
        @if ($content['index'] === 18)
            <div><span data-report-field="sold_count">{{ $content['values']['sold_count'] }}</span><span>إجمالي مبيعات <bdi data-report-field="sales_total">{{ $content['values']['sales_total'] }}</bdi></span></div>
        @else
            <p>(<bdi data-report-field="unsold_count">{{ $content['values']['unsold_count'] }}</bdi>) عقار لم يتم بيعها</p>
        @endif
    </section>
    @if ($content['index'] === 18)
        @foreach (['sales', 'sales2', 'sales3'] as $slot => $key)
            @foreach ($content['tables'][$key] ?? [] as $row)
                <section class="asir-sold-property" data-asir-section data-asir-property="{{ $content['values']['sold_position_'.($slot + 1)] }}">
                    <h3>العقار ( {{ $content['values']['sold_position_'.($slot + 1)] }} )</h3>
                    <h4>تفاصيل</h4>
                    <div class="asir-sold-table-frame"><table class="asir-table asir-sold-table" data-asir-table="sales">
                        <thead><tr>@foreach (['الصك رقم', 'فتح السعر', 'تم البيع بمبلغ', 'عدد المشاركين', 'عدد المزايدات'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                        <tbody><tr>@foreach (array_reverse($row) as $value)<td><bdi>{{ $value }}</bdi></td>@endforeach</tr></tbody>
                    </table></div>
                </section>
            @endforeach
        @endforeach
    @elseif (!empty($content['tables']['unsold']))
        <section class="asir-unsold-frame" data-asir-section>
            <table class="asir-table asir-unsold-table" data-asir-table="unsold">
                <colgroup><col style="width:6.05%"><col style="width:20.04%"><col style="width:14.2%"><col style="width:18.16%"><col style="width:41.55%"></colgroup>
                <thead><tr>@foreach (['م', 'نوع العقار', 'رقم الأصل', 'رقم الصك', 'أسباب عدم البيع'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                <tbody>@foreach ($content['tables']['unsold'] as $row)<tr data-asir-row="{{ $row[4] }}">@foreach (array_reverse($row) as $value)<td><bdi>{{ $value }}</bdi></td>@endforeach</tr>@endforeach</tbody>
            </table>
        </section>
    @endif
</main>
