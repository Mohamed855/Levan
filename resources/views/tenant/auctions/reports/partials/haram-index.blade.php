@include('tenant.auctions.reports.partials.haram-header')
<main class="haram-index-main">
    <section class="haram-index-section haram-index-project">
        <h2>معلومات المشروع</h2>
        <div class="haram-index-project-frame">
            <table class="haram-index-project-table">
                <colgroup><col style="width:35.15%"><col style="width:29.3%"><col style="width:35.55%"></colgroup>
                <thead><tr><th>اسم المشروع</th><th>اسم المزاد</th><th>مدير المشروع</th></tr></thead>
                <tbody><tr>
                    @foreach (['project', 'name', 'manager'] as $role)
                        <td data-report-field="{{ $role }}" data-native-fit>{{ $content['values'][$role] }}</td>
                    @endforeach
                </tr></tbody>
            </table>
        </div>
    </section>
    <section class="haram-index-section haram-index-dates">
        <h2>تاريخ المزاد</h2>
        <div class="haram-index-date-grid">
            @foreach (['start_date' => 'بداية المزاد', 'end_date' => 'نهاية المزاد'] as $role => $label)
                <div class="haram-index-date-card">
                    <div class="haram-index-date-label">{{ $label }}</div>
                    <div class="haram-index-date-value" data-report-field="{{ $role }}">{{ $content['values'][$role] }}</div>
                </div>
            @endforeach
        </div>
    </section>
    <section class="haram-index-section haram-index-properties">
        <h2>قائمة العقارات</h2>
        <div class="haram-index-property-frame">
            <table class="haram-index-property-table" data-native-property-table>
                <colgroup>
                    @foreach ([35, 113, 143, 109, 114] as $width)<col style="width:{{ $width / 514 * 100 }}%">@endforeach
                </colgroup>
                <thead><tr><th>م</th><th>نوع العقار</th><th>رقم الصك</th><th>رقم الأصل</th><th>مقاس اللوحات</th></tr></thead>
                <tbody>
                    @foreach ($content['tables']['properties'] as $rowIndex => $row)
                        <tr data-haram-table-row="properties">
                            @foreach (array_reverse($row, true) as $column => $value)
                                <td data-report-cell="properties:{{ $rowIndex }}:{{ $column }}">
                                    @if ($column === 3)
                                        <span class="haram-index-type" data-haram-property-badge>{{ $value }}</span>
                                    @else
                                        <bdi dir="ltr">{{ $value }}</bdi>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</main>
@include('tenant.auctions.reports.partials.haram-footer')
