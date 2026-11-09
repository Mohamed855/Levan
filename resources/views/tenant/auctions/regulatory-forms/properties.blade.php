<main class="property-statement">
    <table class="statement-table property-table" data-table="properties">
        <colgroup>@foreach([20.23,97.78,83.75,76.54,31.04,42.25,58.35,35.39,53.67,46.56,75.72,75.73,75.3] as $width)<col style="width:{{ $width }}pt">@endforeach</colgroup>
        <thead><tr>
            <th>م</th><th>اسم مالك العقار</th><th>رقم وثيقة الملكية</th><th>رقم عقد المزاد</th><th>المساحة</th>
            <th>نوع العقار<small>(فيلا، أرض، شقة ...)</small></th><th>نوع استخدام العقار<small>(سكني، تجاري ...)</small></th><th>عمر العقار</th><th>المدينة /<br>الحي</th>
            <th>الإفصاح عن وجود<small>(رهن، قيد، نزاع، التزام)</small></th><th>الحد الأدنى لقيمة<br>بدء المزاد العقاري</th><th>قيمة مبلغ الدخول<br>في المزايدة</th><th>القيمة التقديرية<br>للعقار<small>(قيمة التقييم العقاري)</small></th>
        </tr></thead>
        <tbody>@foreach($page['rows'] as $index => $row)<tr data-record="{{ $row ? 'true' : 'false' }}">
            <td class="data-value">{{ $row ? $page['offset'] + $index + 1 : '' }}</td>
            @foreach(['owner_name','deed_number','contract_number','area','type','usage','age','city_district','disclosure','opening_value','entry_amount','estimated_value'] as $key)
                <td><span class="data-value" @if(in_array($key, ['deed_number','contract_number','area','age','opening_value','entry_amount','estimated_value'])) dir="ltr" @endif>{{ $row[$key] ?? '' }}</span></td>
            @endforeach
        </tr>@endforeach</tbody>
    </table>
    @include('tenant.auctions.regulatory-forms.signatures')
</main>
