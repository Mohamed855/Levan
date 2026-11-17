@include('tenant.auctions.reports.partials.asir-header')
<main class="asir-index-main" data-asir-flow>
    <section class="asir-index-metadata" data-asir-section data-asir-repeat>
        <dl>
            @foreach (['project' => 'اسم المشروع', 'name' => 'اسم المزاد', 'manager' => 'مدير المشروع'] as $key => $label)
                <div><dt>{{ $label }}</dt><dd data-report-field="{{ $key }}">{{ $content['values'][$key] }}</dd></div>
            @endforeach
        </dl>
    </section>
    <section class="asir-dates" data-asir-section data-asir-repeat>
        @foreach (['start' => 'بداية المزاد', 'end' => 'نهاية المزاد'] as $key => $label)
            <div class="asir-date">
                <h2>{{ $label }}</h2>
                <div class="asir-date-card">
                    <span class="asir-date-weekday">{{ $content['values'][$key.'_weekday'] }}</span>
                    <strong data-report-field="{{ $key }}_day">{{ $content['values'][$key.'_day'] }}</strong>
                    <div class="asir-date-calendar">
                        <span>{{ $content['values'][$key.'_month'] }}</span>
                        <span>{{ $content['values'][$key.'_year'] }}</span>
                    </div>
                    <div class="asir-date-time">
                        <span>المــوافق</span>
                        <span>{{ $content['values'][$key.'_hijri'] }}</span>
                        <span data-report-field="{{ $key }}_time">{{ $content['values'][$key.'_time'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </section>
    @if (!empty($content['tables']['properties']))
        <section class="asir-index-table-frame" data-asir-section>
            <table class="asir-table asir-property-table" data-asir-table="properties">
                <colgroup><col style="width:5%"><col style="width:20%"><col style="width:22%"><col style="width:18%"><col style="width:17%"><col style="width:18%"></colgroup>
                <thead><tr>@foreach (['م', 'نوع العقار', 'رقم الصك', 'رقم الأصل', 'عدد اللوحات', 'مقاس اللوحات'] as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
                <tbody>
                    @foreach ($content['tables']['properties'] as $row)
                        <tr data-asir-row="{{ $row[5] }}">@foreach (array_reverse($row) as $value)<td><bdi>{{ $value }}</bdi></td>@endforeach</tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
</main>
