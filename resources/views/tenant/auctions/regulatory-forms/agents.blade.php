<main class="agent-statement">
    <table class="statement-table agent-table" data-table="agents">
        <colgroup><col style="width:20.5pt">@for($i=0; $i<4; $i++)<col style="width:125.95pt">@endfor</colgroup>
        <thead><tr><th>م</th><th>اسم مالك العقار</th><th>رقم وثيقة الملكية</th><th>اسم الوكيل</th><th>رقم الوكالة الإلكترونية</th></tr></thead>
        <tbody>@foreach($page['rows'] as $index => $row)<tr data-record="{{ $row ? 'true' : 'false' }}">
            <td class="data-value">{{ $row ? $page['offset'] + $index + 1 : '' }}</td>
            @foreach(['owner_name','deed_number','name','agency_number'] as $key)<td><span class="data-value" @if(in_array($key,['deed_number','agency_number'])) dir="ltr" @endif>{{ $row[$key] ?? '' }}</span></td>@endforeach
        </tr>@endforeach</tbody>
    </table>
    @include('tenant.auctions.regulatory-forms.signatures')
</main>
