<table class="signatures {{ $signatureClass ?? '' }}">
    <thead><tr><th>توقيع المنشأة</th><th>ختم المنشأة</th>@if(!empty($chamber))<th>المصادقة على التوقيع من الغرفة التجارية</th>@endif</tr></thead>
    <tbody><tr><td>@if(!empty($data['signing_assets']['signature']))<img class="establishment-signing-image" src="{{ $data['signing_assets']['signature'] }}" alt="توقيع المنشأة">@endif</td><td>@if(!empty($data['signing_assets']['stamp']))<img class="establishment-signing-image" src="{{ $data['signing_assets']['stamp'] }}" alt="ختم المنشأة">@endif</td>@if(!empty($chamber))<td></td>@endif</tr></tbody>
</table>
