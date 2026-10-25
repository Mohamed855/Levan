<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><title>تقرير المزاد - {{ $data['auction']['name'] }}</title>@include('tenant.auctions.reports.partials.styles')</head>
<body>
<section class="page" style="padding:0">
    @if($data['auction']['report_image'])<img src="{{ $data['auction']['report_image'] }}" alt="" style="position:absolute;inset:0;width:100%;height:100%;object-fit:cover">@endif
    <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(4,37,27,.12),rgba(4,37,27,.92))"></div>
    <div style="position:absolute;right:16mm;left:16mm;bottom:24mm;color:#fff">
        <div class="brand">ليفان • تقرير المزاد</div><h1 style="font-size:34px;margin:5mm 0">{{ $data['auction']['name'] }}</h1>
        <p style="font-size:16px;line-height:1.8">{{ $data['auction']['description'] ?: 'التقرير التنفيذي لنتائج وأعمال المزاد' }}</p>
        <div style="border-top:2px solid #caa64a;padding-top:5mm;margin-top:8mm">مدير المشروع: {{ $data['auction']['project_manager_name'] }}</div>
    </div>
</section>

<section class="page">
    <h2 class="section-title">الملخص التنفيذي</h2>
    <div class="stats">
        <div class="stat"><strong>{{ $data['totals']['properties_count'] }}</strong><span>العقارات</span></div>
        <div class="stat"><strong>{{ $data['totals']['sold_count'] }}</strong><span>المباعة</span></div>
        <div class="stat"><strong>{{ $data['totals']['participants_count'] }}</strong><span>المشاركون</span></div>
        <div class="stat"><strong>{{ $data['totals']['bids_count'] }}</strong><span>المزايدات</span></div>
    </div>
    <div class="card"><h3>بيانات المشروع</h3><div class="card-body grid"><div>اسم المزاد: {{ $data['auction']['name'] }}<br>المدينة: {{ $data['auction']['city'] }}<br>التصنيف: {{ $data['auction']['category'] }}</div><div>الجهة المشرفة: {{ $data['auction']['supervising_entity'] }}<br>مدير المشروع: {{ $data['auction']['project_manager_name'] }}<br>المنصة: {{ $data['auction']['platform_name'] }}</div></div></div>
    <div class="card"><h3>الحصيلة المالية المسجلة</h3><div class="card-body"><strong class="gold" style="font-size:30px">{{ number_format($data['totals']['recorded_sales_total'], 2) }} ر.س</strong><p class="muted">يحتسب الإجمالي من نتائج العقارات المسجلة كمباعة فقط، ولا تُحوّل النتائج الناقصة إلى أصفار.</p></div></div>
    <h2 class="section-title" style="margin-top:10mm">التسويق</h2>
    @if(!empty($data['marketing']['campaigns']))
        <table><thead><tr><th>المنصة</th><th>الوصول</th><th>المشاهدات</th><th>التفاعل</th><th>النقرات</th></tr></thead><tbody>
        @foreach($data['marketing']['campaigns'] as $campaign)<tr><td>{{ $campaign['platform'] ?? '-' }}</td><td>{{ $campaign['reach'] ?? '-' }}</td><td>{{ $campaign['impressions'] ?? '-' }}</td><td>{{ $campaign['engagements'] ?? '-' }}</td><td>{{ $campaign['clicks'] ?? '-' }}</td></tr>@endforeach
        </tbody></table>
    @else<div class="no-data">لم تُسجل بيانات حملات تسويقية.</div>@endif
    <div class="footer"><span>{{ $data['auction']['name'] }}</span><span>الملخص التنفيذي</span></div>
</section>

@forelse($data['properties'] as $index => $property)
<section class="page">
    <h2 class="section-title">العقار {{ $index + 1 }} — {{ $property['name'] }}</h2>
    @if($property['image'])<img class="property-image" src="{{ $property['image'] }}" alt="">@else<div class="image-placeholder" style="height:70mm">لا توجد صورة للعقار</div>@endif
    <div class="grid" style="margin-top:7mm">
        <div class="card"><h3>بيانات العقار</h3><div class="card-body">الرقم: {{ $property['number'] }}<br>النوع: {{ $property['type'] }}<br>الموقع: {{ $property['city'] }} — {{ $property['district'] }}<br>المساحة: {{ $property['area'] !== null ? number_format($property['area'], 2).' م²' : '-' }}</div></div>
        <div class="card"><h3>نتيجة المزاد</h3><div class="card-body">الحالة: {{ ['sold'=>'تم البيع','unsold'=>'لم يتم البيع','pending'=>'لم تسجل النتيجة'][$property['result_status']] ?? 'لم تسجل النتيجة' }}<br>سعر الافتتاح: {{ $property['opening_price'] !== null ? number_format($property['opening_price'], 2).' ر.س' : '-' }}<br>سعر البيع: {{ $property['sale_price'] !== null ? number_format($property['sale_price'], 2).' ر.س' : '-' }}<br>المشاركون/المزايدات: {{ $property['participants_count'] ?? '-' }} / {{ $property['bids_count'] ?? '-' }}</div></div>
    </div>
    @if($property['result_status'] === 'unsold' && $property['unsold_reason'])<div class="card"><h3>سبب عدم البيع</h3><div class="card-body">{{ $property['unsold_reason'] }}</div></div>@endif
    <div class="footer"><span>{{ $data['auction']['name'] }}</span><span>{{ $property['number'] }}</span></div>
</section>
@empty
<section class="page"><h2 class="section-title">العقارات</h2><div class="no-data">لا توجد عقارات مرتبطة بالمزاد.</div></section>
@endforelse

@if(!empty($data['evidence']['marketing_evidence']))
<section class="page"><h2 class="section-title">الأدلة التسويقية</h2><div class="evidence-grid">@foreach($data['evidence']['marketing_evidence'] as $item)@if($item['uri'])<img src="{{ $item['uri'] }}" alt="{{ $item['name'] }}">@endif @endforeach</div><div class="footer"><span>{{ $data['auction']['name'] }}</span><span>الأدلة التسويقية</span></div></section>
@endif
</body></html>
