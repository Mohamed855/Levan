<!doctype html>
<html lang="ar" dir="rtl">
<head><meta charset="utf-8"><title>مخرجات المزاد - {{ $data['auction']['name'] }}</title>@include('tenant.auctions.reports.partials.styles')</head>
<body>
<section class="page" style="padding:0">
    <div class="hero">
        <div class="brand">ليفان • مخرجات المزاد</div>
        <h1>{{ $data['auction']['name'] }}</h1>
        <p>{{ $data['auction']['description'] ?: 'ملخص مخرجات ونتائج المزاد العقاري' }}</p>
    </div>
    <div style="padding:0 16mm">
        @if($data['auction']['cover_image'])<img class="cover-image" src="{{ $data['auction']['cover_image'] }}" alt="">@else<div class="image-placeholder">لا توجد صورة غلاف مسجلة</div>@endif
        <div class="stats">
            <div class="stat"><strong>{{ $data['totals']['properties_count'] }}</strong><span>العقارات</span></div>
            <div class="stat"><strong>{{ $data['totals']['sold_count'] }}</strong><span>تم بيعها</span></div>
            <div class="stat"><strong>{{ $data['totals']['unsold_count'] }}</strong><span>لم تُبع</span></div>
            <div class="stat"><strong>{{ $data['totals']['pending_count'] }}</strong><span>بانتظار النتيجة</span></div>
        </div>
        <div class="grid">
            <div class="card"><h3>بيانات المزاد</h3><div class="card-body">المدينة: {{ $data['auction']['city'] }}<br>الفترة: {{ $data['auction']['start_date'] ?: '-' }} — {{ $data['auction']['end_date'] ?: '-' }}<br>المنصة: {{ $data['auction']['platform_name'] }}</div></div>
            <div class="card"><h3>الإدارة والإسناد</h3><div class="card-body">مدير المشروع: {{ $data['auction']['project_manager_name'] }}<br>الإسناد: {{ $data['assignment']['name'] }}<br>الجهة: {{ $data['assignment']['issuing_entity'] }}</div></div>
        </div>
    </div>
    <div class="footer"><span>ليفان لإدارة الأصول والمزادات</span><span>مخرجات المزاد</span></div>
</section>

<section class="page">
    <h2 class="section-title">نتائج العقارات</h2>
    @if(count($data['properties']))
    <table><thead><tr><th>#</th><th>العقار</th><th>المدينة</th><th>الحالة</th><th>سعر الافتتاح</th><th>سعر البيع</th><th>المشاركون</th><th>المزايدات</th></tr></thead><tbody>
    @foreach($data['properties'] as $index => $property)
        <tr><td>{{ $index + 1 }}</td><td>{{ $property['number'] }}<br><span class="muted">{{ $property['name'] }}</span></td><td>{{ $property['city'] }}</td><td>{{ ['sold'=>'تم البيع','unsold'=>'لم يتم البيع','pending'=>'لم تسجل النتيجة'][$property['result_status']] ?? 'لم تسجل النتيجة' }}</td><td class="amount">{{ $property['opening_price'] !== null ? number_format($property['opening_price'], 2) : '-' }}</td><td class="amount">{{ $property['sale_price'] !== null ? number_format($property['sale_price'], 2) : '-' }}</td><td>{{ $property['participants_count'] ?? '-' }}</td><td>{{ $property['bids_count'] ?? '-' }}</td></tr>
    @endforeach
    </tbody></table>
    @else<div class="no-data">لا توجد عقارات مرتبطة بالمزاد.</div>@endif
    <div class="stats" style="margin-top:10mm">
        <div class="stat" style="grid-column:span 2"><strong>{{ number_format($data['totals']['recorded_sales_total'], 2) }}</strong><span>إجمالي المبيعات المسجلة (ر.س)</span></div>
        <div class="stat"><strong>{{ $data['totals']['participants_count'] }}</strong><span>المشاركون المسجلون</span></div>
        <div class="stat"><strong>{{ $data['totals']['bids_count'] }}</strong><span>المزايدات المسجلة</span></div>
    </div>
    <div class="footer"><span>{{ $data['auction']['name'] }}</span><span>النتائج</span></div>
</section>
</body></html>
