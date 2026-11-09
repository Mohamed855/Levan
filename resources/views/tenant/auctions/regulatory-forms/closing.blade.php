<main class="closing-statement">
    <table class="statement-table closing-table" data-table="closing">
        <colgroup>@foreach([16.95,103.03,97.51,98.26,97.96,98.53,101.17,97.19] as $width)<col style="width:{{ $width }}pt">@endforeach</colgroup>
        <thead><tr><th>#</th><th>رقم وثيقة الملكية العقارية</th><th>نوع العقار</th><th>نوع استخدام العقار</th><th>حالة الترسية</th><th>سبب عدم الترسية<small>(في حال عدم الترسية)</small></th><th>اسم من تمت الترسية عليه<small>(في حال تمت الترسية)</small></th><th>قيمة الترسية<small>(في حال تمت الترسية)</small></th></tr></thead>
        <tbody>@foreach($page['rows'] as $index => $row)<tr data-record="{{ $row ? 'true' : 'false' }}">
            <td class="data-value">{{ $row ? $page['offset'] + $index + 1 : '' }}</td>
            @foreach(['deed_number','type','usage','result_label','unawarded_reason','awardee','award_value'] as $key)<td><span class="data-value" @if(in_array($key,['deed_number','award_value'])) dir="ltr" @endif>{{ $row[$key] ?? '' }}</span></td>@endforeach
        </tr>@endforeach</tbody>
    </table>
    @include('tenant.auctions.regulatory-forms.signatures')
    <p class="closing-repeat">يمكن تكرار البيان عند زيادة العقارات محل المزاد العقاري</p>
</main>
