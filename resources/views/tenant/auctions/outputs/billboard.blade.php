<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $auction->name }} - اللوحة الإعلانية</title>
    <style>
        @page {
            margin-top: 15mm;
            margin-bottom: 15mm;
            margin-left: 15mm;
            margin-right: 15mm;
        }
        body {
            font-family: 'Cairo', 'DejaVu Sans', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            color: #1a202c;
            font-size: 13pt;
            background-color: transparent;
        }
        .billboard-container {
            border: 4px solid #1D4F44;
            border-radius: 12px;
            padding: 24px;
            background-color: rgba(255, 255, 255, 0.94);
        }
        .billboard-title {
            color: #1D4F44;
            font-size: 32pt;
            font-weight: bold;
            margin: 0 0 12px 0;
            line-height: 1.2;
            text-align: center;
        }
        .badges-row {
            text-align: center;
            margin-bottom: 24px;
        }
        .badge {
            display: inline-block;
            background-color: #1D4F44;
            color: #ffffff;
            padding: 6px 18px;
            border-radius: 24px;
            font-size: 14pt;
            font-weight: bold;
            margin: 0 6px;
        }
        .badge-gold {
            background-color: #b45309;
            color: #ffffff;
        }
        .content-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .content-table td {
            vertical-align: middle;
        }
        .cover-col {
            width: 48%;
            text-align: center;
        }
        .cover-img {
            max-width: 100%;
            max-height: 250px;
            border-radius: 10px;
            border: 2px solid #cbd5e1;
            object-fit: cover;
        }
        .details-col {
            width: 52%;
            padding-right: 20px;
        }
        .info-pill {
            background-color: #F7F3EC;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 10px 14px;
            margin-bottom: 10px;
        }
        .info-label {
            color: #64748b;
            font-size: 10.5pt;
        }
        .info-val {
            color: #1D4F44;
            font-size: 14pt;
            font-weight: bold;
        }
        .footer-banner {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 2px solid #1D4F44;
        }
        .qr-img {
            width: 90px;
            height: 90px;
        }
    </style>
</head>
<body>

    <div class="billboard-container">
        {{-- Billboard Header --}}
        <h1 class="billboard-title">{{ $auction->name }}</h1>

        <div class="badges-row">
            <span class="badge">مدينة {{ $auction->city ?? 'المملكة' }}</span>
            <span class="badge badge-gold">{{ $auction->start_date ? $auction->start_date->format('Y/m/d') : '-' }}</span>
            <span class="badge">{{ $auction->properties->count() }} عقار معتمد</span>
        </div>

        {{-- Content Table: Left Cover, Right Specs --}}
        <table class="content-table">
            <tr>
                @if(!empty($coverImageBase64))
                    <td class="cover-col">
                        <img src="{{ $coverImageBase64 }}" class="cover-img" alt="{{ $auction->name }}">
                    </td>
                @endif
                <td class="details-col" @if(empty($coverImageBase64)) colspan="2" @endif>
                    <div class="info-pill">
                        <div class="info-label">موعد بدء المزاد</div>
                        <div class="info-val">{{ $auction->start_date ? $auction->start_date->format('Y-m-d') : '-' }} — الساعة {{ $auction->start_time ?? '-' }}</div>
                    </div>

                    <div class="info-pill">
                        <div class="info-label">منصة المزاد المعتمدة</div>
                        <div class="info-val">{{ $auction->platform_name ?? 'المنصة الوطنية للمزادات' }}</div>
                    </div>

                    @if($auction->supervising_entity)
                        <div class="info-pill">
                            <div class="info-label">الجهة المشرفة على المزاد</div>
                            <div class="info-val" style="font-size: 12pt;">{{ $auction->supervising_entity }}</div>
                        </div>
                    @endif
                </td>
            </tr>
        </table>

        {{-- Footer Banner --}}
        <div class="footer-banner">
            <table style="width: 100%;">
                <tr>
                    <td style="vertical-align: middle;">
                        <div style="font-size: 14pt; font-weight: bold; color: #1D4F44;">للمعاينة والمزايدة الإلكترونية:</div>
                        <div style="font-size: 11pt; color: #0284c7; direction: ltr; text-align: right;">{{ $auction->platform_url ?? 'https://lot.sa' }}</div>
                    </td>
                    @if(!empty($qrCodeBase64))
                        <td style="text-align: left; width: 110px;">
                            <img src="{{ $qrCodeBase64 }}" class="qr-img" alt="QR">
                        </td>
                    @endif
                </tr>
            </table>
        </div>
    </div>

</body>
</html>
