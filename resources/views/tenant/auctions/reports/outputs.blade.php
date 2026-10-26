<!doctype html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="utf-8">
    <title>مخرجات المزاد - {{ $data['auction']['name'] }}</title>@include('tenant.auctions.reports.partials.styles')
</head>

<body class="outputs-document">
    <section class="page outputs-cover">
        @if ($data['auction']['cover_image'])
            <img class="outputs-cover__background" src="{{ $data['auction']['cover_image'] }}" alt="">
        @endif
        <div class="outputs-cover__grid"></div>
        <div class="outputs-cover__top"><span class="levan-logo-light" aria-label="ليفان"></span><span
                class="outputs-cover__eyebrow">ليفان العقارية · مخرجات مزاد عقاري</span></div>
        <div class="outputs-cover__content">
            <p class="outputs-cover__kicker">مخرجات المزاد</p>
            <h1>{{ $data['auction']['name'] }}</h1>
            <p>{{ $data['auction']['description'] ?: 'ملخص مخرجات ونتائج المزاد العقاري' }}</p>
            <div class="outputs-cover__accent"></div>
        </div>
        <div class="outputs-cover__meta">
            <div><span>الإسناد</span><strong>{{ $data['assignment']['name'] }}</strong></div>
            <div><span>مدير المشروع</span><strong>{{ $data['auction']['project_manager_name'] }}</strong></div>
            <div><span>العقارات</span><strong>{{ $data['totals']['properties_count'] }}</strong></div>
            <div><span>تاريخ المزاد</span><strong>{{ $data['auction']['start_date'] ?: '-' }}</strong></div>
        </div>
        <div class="outputs-cover__footer"><span>LEVAN REAL ESTATE</span><span>{{ $data['auction']['city'] }}</span>
        </div>
    </section>

    @foreach (collect($data['properties'])->chunk(8)->whenEmpty(fn ($chunks) => $chunks->push(collect())) as $propertyChunk)
    <section class="page outputs-results-page">
        <header class="document-header"><span class="levan-logo-light"></span><strong>مخرجات المزاد</strong></header>
        <h2 class="section-title">نتائج العقارات</h2>
        @if (count($data['properties']))
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>العقار / الصك</th>
                        <th>المدينة</th>
                        <th>الحالة</th>
                        <th>سعر الافتتاح</th>
                        <th>سعر البيع</th>
                        <th>المشاركون</th>
                        <th>المزايدات</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($propertyChunk as $index => $property)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $property['number'] }}<br><span class="muted">{{ $property['name'] }} •
                                    {{ $property['deed_number'] }}</span></td>
                            <td>{{ $property['city'] }}</td>
                            <td>{{ ['sold' => 'تم البيع', 'unsold' => 'لم يتم البيع', 'pending' => 'لم تسجل النتيجة'][$property['result_status']] ?? 'لم تسجل النتيجة' }}
                            </td>
                            <td class="amount">
                                {{ $property['opening_price'] !== null ? number_format($property['opening_price'], 2) : '-' }}
                            </td>
                            <td class="amount">
                                {{ $property['sale_price'] !== null ? number_format($property['sale_price'], 2) : '-' }}
                            </td>
                            <td>{{ $property['participants_count'] ?? '-' }}</td>
                            <td>{{ $property['bids_count'] ?? '-' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else<div class="no-data">لا توجد عقارات مرتبطة بالمزاد.</div>
        @endif
        <div class="stats" style="margin-top:10mm">
            <div class="stat" style="grid-column:span 2">
                <strong>{{ number_format($data['totals']['recorded_sales_total'], 2) }}</strong><span>إجمالي المبيعات
                    المسجلة (ر.س)</span>
            </div>
            <div class="stat"><strong>{{ $data['totals']['participants_count'] }}</strong><span>المشاركون
                    المسجلون</span></div>
            <div class="stat"><strong>{{ $data['totals']['bids_count'] }}</strong><span>المزايدات المسجلة</span>
            </div>
        </div>
        <div class="footer"><span>{{ $data['auction']['name'] }}</span><span>النتائج</span></div>
    </section>
    @endforeach
</body>

</html>
