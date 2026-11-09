<table class="signatures {{ $signatureClass ?? '' }}">
    <thead><tr><th>توقيع المنشأة</th><th>ختم المنشأة</th>@if(!empty($chamber))<th>المصادقة على التوقيع من الغرفة التجارية</th>@endif</tr></thead>
    <tbody><tr><td></td><td></td>@if(!empty($chamber))<td></td>@endif</tr></tbody>
</table>
