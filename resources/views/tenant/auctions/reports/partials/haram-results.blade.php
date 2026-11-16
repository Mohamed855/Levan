@include('tenant.auctions.reports.partials.haram-header')
<main class="haram-native-main haram-results-main" data-native-flow>
    @if (filled($content['values']['narrative'] ?? null))
        <section class="haram-index-section haram-result-narrative" data-native-section>
            <h2>نتائج المزاد</h2>
            <div class="haram-narrative-card"><p data-native-paragraph data-report-field="narrative">{{ $content['values']['narrative'] }}</p></div>
        </section>
    @endif
    @foreach (['sold' => 'ملخص البيع', 'unsold' => 'أسباب عدم البيع'] as $table => $label)
        @if (! empty($content['tables'][$table]))
            <section class="haram-index-section haram-result-section haram-result-section--{{ $table }}" data-native-section data-result-section="{{ $table }}">
                <h2>{{ $label }}</h2>
                <div class="haram-result-frame haram-result-frame--{{ $table }}">
                    <table class="haram-result-table" data-native-result-table="{{ $table }}">
                        <colgroup>
                            @foreach ($table === 'sold' ? [31, 91, 107, 100, 93, 85] : [31, 90, 386] as $width)
                                <col style="width:{{ $width / 507 * 100 }}%">
                            @endforeach
                        </colgroup>
                        <thead><tr>
                            @foreach ($table === 'sold' ? ['م', 'العقار', 'عدد المزايدات', 'عدد المشاركين', 'سعر الإفتتاح', 'سعر البيع'] : ['م', 'العقار', 'سبب عدم البيع'] as $heading)
                                <th>{{ $heading }}</th>
                            @endforeach
                        </tr></thead>
                        <tbody>
                            @foreach ($content['tables'][$table] as $rowIndex => $row)
                                <tr data-haram-table-row="{{ $table }}">
                                    @foreach (array_reverse($row, true) as $column => $value)
                                        <td data-report-cell="{{ $table }}:{{ $rowIndex }}:{{ $column }}"><bdi>{{ $value }}</bdi></td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    @endforeach
</main>
@include('tenant.auctions.reports.partials.haram-footer')
