<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $auction->name }} - منصات التواصل الاجتماعي</title>
    <style>
        @page {
            margin: 10mm;
        }
        body {
            font-family: 'Cairo', 'DejaVu Sans', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            color: #1a202c;
            font-size: 11pt;
            background-color: transparent;
        }
        .social-card {
            border: 3px solid #1D4F44;
            border-radius: 12px;
            padding: 18px;
            background-color: rgba(255, 255, 255, 0.96);
            text-align: center;
        }
        .social-tag {
            display: inline-block;
            background-color: #1D4F44;
            color: #ffffff;
            padding: 4px 14px;
            border-radius: 16px;
            font-size: 10pt;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .social-title {
            color: #1D4F44;
            font-size: 20pt;
            font-weight: bold;
            margin: 6px 0 12px 0;
            line-height: 1.25;
        }
        .cover-wrapper {
            margin: 10px auto;
            text-align: center;
        }
        .cover-img {
            width: 100%;
            max-height: 190px;
            border-radius: 8px;
            object-fit: cover;
            border: 1px solid #cbd5e1;
        }
        .details-grid {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }
        .details-grid td {
            padding: 6px 8px;
            font-size: 9.5pt;
            border: 1px solid #e2e8f0;
            background-color: #F7F3EC;
            color: #1D4F44;
            font-weight: bold;
        }
        .details-grid td.val {
            background-color: #ffffff;
            color: #1a202c;
            font-weight: normal;
        }
        .social-footer {
            margin-top: 14px;
            padding-top: 10px;
            border-top: 1px dashed #cbd5e1;
        }
        .qr-img {
            width: 65px;
            height: 65px;
        }
    </style>
</head>
<body>

    <div class="social-card">
        <span class="social-tag">مزاد معتمد — {{ $auction->city ?? 'المملكة' }}</span>
        <h1 class="social-title">{{ $auction->name }}</h1>

        @if(!empty($coverImageBase64))
            <div class="cover-wrapper">
                <img src="{{ $coverImageBase64 }}" class="cover-img" alt="{{ $auction->name }}">
            </div>
        @endif

        <table class="details-grid">
            <tr>
                <td>موعد المزاد</td>
                <td class="val">{{ $auction->start_date ? $auction->start_date->format('Y-m-d') : '-' }} ({{ $auction->start_time ?? '-' }})</td>
            </tr>
            <tr>
                <td>المنصة</td>
                <td class="val">{{ $auction->platform_name ?? 'المنصة الوطنية' }}</td>
            </tr>
            <tr>
                <td>عدد العقارات</td>
                <td class="val">{{ $auction->properties->count() }} عقار</td>
            </tr>
        </table>

        <div class="social-footer">
            <table style="width: 100%;">
                <tr>
                    <td style="text-align: right; vertical-align: middle;">
                        <div style="font-weight: bold; color: #1D4F44; font-size: 10pt;">امسح الكود للمزايدة الفورية:</div>
                        <div style="color: #0284c7; font-size: 8.5pt; direction: ltr; text-align: right;">{{ $auction->platform_url ?? 'https://lot.sa' }}</div>
                    </td>
                    @if(!empty($qrCodeBase64))
                        <td style="text-align: left; width: 75px; vertical-align: middle;">
                            <img src="{{ $qrCodeBase64 }}" class="qr-img" alt="QR">
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
