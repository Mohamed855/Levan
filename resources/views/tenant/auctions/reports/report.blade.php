<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <title>تقرير المزاد - {{ $data['auction']['name'] }}</title>@include('tenant.auctions.reports.partials.styles')
</head>

<body class="report-document">
    <section class="page report-cover">
        <span class="levan-logo-navy report-cover__logo" aria-label="ليفان"></span>
        <div class="report-cover__lines" aria-hidden="true"></div>
        <div class="report-cover__title">
            <p>التقرير النهائي للمزاد</p>
            <h1>{{ $data['auction']['name'] }}</h1>
            <span>{{ $data['auction']['description'] ?: 'التقرير التنفيذي لنتائج وأعمال المزاد' }}</span>
        </div>
        <div class="report-cover__image">
            @if ($data['auction']['report_image'])
                <img src="{{ $data['auction']['report_image'] }}" alt="صورة تقرير {{ $data['auction']['name'] }}">
            @endif
        </div>
        <div class="report-cover__meta">
            <span>{{ $data['auction']['city'] }}</span><span>{{ $data['auction']['start_date'] ?: '-' }}</span><span>{{ $data['auction']['project_manager_name'] }}</span>
        </div>
    </section>

    <section class="page">
        <h2 class="section-title">الملخص التنفيذي</h2>
        <div class="stats">
            <div class="stat"><strong>{{ $data['totals']['properties_count'] }}</strong><span>العقارات</span></div>
            <div class="stat"><strong>{{ $data['totals']['sold_count'] }}</strong><span>المباعة</span></div>
            <div class="stat"><strong>{{ $data['totals']['participants_count'] }}</strong><span>المشاركون</span>
            </div>
            <div class="stat"><strong>{{ $data['totals']['bids_count'] }}</strong><span>المزايدات</span></div>
        </div>
        <div class="card">
            <h3>بيانات المشروع</h3>
            <div class="card-body grid">
                <div>اسم المزاد: {{ $data['auction']['name'] }}<br>المدينة: {{ $data['auction']['city'] }}<br>التصنيف:
                    {{ $data['auction']['category'] }}</div>
                <div>الجهة المشرفة: {{ $data['auction']['supervising_entity'] }}<br>مدير المشروع:
                    {{ $data['auction']['project_manager_name'] }}<br>المنصة: {{ $data['auction']['platform_name'] }}
                </div>
            </div>
        </div>
        <div class="card">
            <h3>الحصيلة المالية المسجلة</h3>
            <div class="card-body"><strong class="gold"
                    style="font-size:30px">{{ number_format($data['totals']['recorded_sales_total'], 2) }} ر.س</strong>
                <p class="muted">يحتسب الإجمالي من نتائج العقارات المسجلة كمباعة فقط، ولا تُحوّل النتائج الناقصة إلى
                    أصفار.</p>
            </div>
        </div>
        <h2 class="section-title" style="margin-top:10mm">التسويق</h2>
        @if (!empty($data['marketing']['campaigns']))
            <table>
                <thead>
                    <tr>
                        <th>المنصة</th>
                        <th>الوصول</th>
                        <th>المشاهدات</th>
                        <th>التفاعل</th>
                        <th>النقرات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($data['marketing']['campaigns'] as $campaign)
                        <tr>
                            <td>{{ $campaign['platform'] ?? '-' }}</td>
                            <td>{{ $campaign['reach'] ?? '-' }}</td>
                            <td>{{ $campaign['impressions'] ?? '-' }}</td>
                            <td>{{ $campaign['engagements'] ?? '-' }}</td>
                            <td>{{ $campaign['clicks'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else<div class="no-data">لم تُسجل بيانات حملات تسويقية.</div>
        @endif
        <div class="footer"><span>{{ $data['auction']['name'] }}</span><span>الملخص التنفيذي</span></div>
    </section>

    @forelse($data['properties'] as $index => $property)
        @php($propertyImages = collect($property['images'] ?? [])->filter())
        <section class="page">
            <h2 class="section-title">العقار {{ $index + 1 }} — {{ $property['name'] }}</h2>
            @if ($propertyImages->isNotEmpty())
                <div class="property-gallery">
                    @foreach ($propertyImages->take(2) as $image)
                        <img src="{{ $image }}" alt="صورة العقار {{ $property['number'] }}">
                    @endforeach
                </div>
            @else<div class="image-placeholder" style="height:70mm">لا توجد صورة للعقار</div>
            @endif
            <div class="grid" style="margin-top:7mm">
                <div class="card">
                    <h3>بيانات العقار</h3>
                    <div class="card-body">الرقم: {{ $property['number'] }}<br>رقم الصك:
                        {{ $property['deed_number'] }}<br>النوع: {{ $property['type'] }}<br>الموقع:
                        {{ $property['city'] }} — {{ $property['district'] }}<br>المساحة:
                        {{ $property['area'] !== null ? number_format($property['area'], 2) . ' م²' : '-' }}</div>
                </div>
                <div class="card">
                    <h3>نتيجة المزاد</h3>
                    <div class="card-body">الحالة:
                        {{ ['sold' => 'تم البيع', 'unsold' => 'لم يتم البيع', 'pending' => 'لم تسجل النتيجة'][$property['result_status']] ?? 'لم تسجل النتيجة' }}<br>سعر
                        الافتتاح:
                        {{ $property['opening_price'] !== null ? number_format($property['opening_price'], 2) . ' ر.س' : '-' }}<br>سعر
                        البيع:
                        {{ $property['sale_price'] !== null ? number_format($property['sale_price'], 2) . ' ر.س' : '-' }}<br>المشاركون/المزايدات:
                        {{ $property['participants_count'] ?? '-' }} / {{ $property['bids_count'] ?? '-' }}</div>
                </div>
            </div>
            @if ($property['result_status'] === 'unsold' && $property['unsold_reason'])
                <div class="card">
                    <h3>سبب عدم البيع</h3>
                    <div class="card-body">{{ $property['unsold_reason'] }}</div>
                </div>
            @endif
            <div class="footer"><span>{{ $data['auction']['name'] }}</span><span>{{ $property['number'] }}</span>
            </div>
        </section>
        @foreach ($propertyImages->slice(2)->chunk(4) as $imageChunk)
            <section class="page">
                <h2 class="section-title">صور العقار {{ $property['number'] }} — {{ $property['name'] }}</h2>
                <div class="evidence-grid">
                    @foreach ($imageChunk as $image)
                        <div><img src="{{ $image }}" alt="صورة العقار {{ $property['number'] }}"></div>
                    @endforeach
                </div>
                <div class="footer"><span>{{ $data['auction']['name'] }}</span><span>صور العقار
                        {{ $property['number'] }}</span></div>
            </section>
        @endforeach
    @empty
        <section class="page">
            <h2 class="section-title">العقارات</h2>
            <div class="no-data">لا توجد عقارات مرتبطة بالمزاد.</div>
        </section>
    @endforelse

    @foreach (['marketing_evidence' => 'الأدلة التسويقية', 'board_before' => 'صور اللوحات قبل الفك', 'board_after' => 'صور اللوحات بعد الفك'] as $section => $title)
        @php($sectionItems = collect($data['evidence'][$section] ?? [])->filter(fn($item) => filled($item['uri'] ?? null) && (str_starts_with((string) ($item['mime'] ?? ''), 'image/') || str_starts_with((string) ($item['uri'] ?? ''), 'data:image/'))))
        @foreach ($sectionItems->chunk(3) as $chunk)
            <section class="page">
                <h2 class="section-title">{{ $title }}</h2>
                <div class="evidence-grid">
                    @foreach ($chunk as $item)
                        <div><img src="{{ $item['uri'] }}" alt="{{ $item['name'] }}">
                            <div class="muted" style="margin-top:2mm">{{ $item['name'] }}</div>
                        </div>
                    @endforeach
                </div>
                <div class="footer"><span>{{ $data['auction']['name'] }}</span><span>{{ $title }}</span></div>
            </section>
        @endforeach
    @endforeach
</body>

</html>
