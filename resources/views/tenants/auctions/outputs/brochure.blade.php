<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $auction->name }} - كتيب البروشور</title>
    <style>
        @page {
            header: page-header;
            footer: page-footer;
            margin-top: 18mm;
            margin-bottom: 20mm;
            margin-left: 15mm;
            margin-right: 15mm;
        }
        body {
            font-family: 'Cairo', 'DejaVu Sans', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            color: #2b2b2b;
            font-size: 12pt;
            line-height: 1.5;
            background-color: transparent;
        }
        .header-box {
            border-bottom: 3px solid #1D4F44;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .auction-title {
            color: #1D4F44;
            font-size: 24pt;
            font-weight: bold;
            margin: 0 0 8px 0;
            line-height: 1.2;
        }
        .badge-pill {
            display: inline-block;
            background-color: #F7F3EC;
            color: #1D4F44;
            border: 1px solid #1D4F44;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 10pt;
            font-weight: bold;
            margin-left: 6px;
        }
        .cover-photo-wrapper {
            text-align: center;
            margin-bottom: 20px;
        }
        .cover-photo {
            width: 100%;
            max-height: 220px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #e0e0e0;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .data-table td {
            padding: 10px 12px;
            vertical-align: top;
            border: 1px solid #e2e8f0;
            font-size: 10.5pt;
        }
        .data-table .label {
            background-color: #F7F3EC;
            color: #1D4F44;
            font-weight: bold;
            width: 22%;
        }
        .data-table .val {
            background-color: #ffffff;
            color: #1a202c;
            width: 28%;
        }
        .description-box {
            background-color: #f8fafc;
            border-right: 4px solid #1D4F44;
            padding: 12px 16px;
            margin-bottom: 20px;
            border-radius: 0 6px 6px 0;
            font-size: 10.5pt;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px dashed #cbd5e1;
        }
        .footer-table td {
            vertical-align: middle;
        }
        .qr-box {
            width: 100px;
            text-align: center;
        }
        .qr-image {
            width: 85px;
            height: 85px;
        }
        .qr-label {
            font-size: 8.5pt;
            color: #64748b;
            margin-top: 4px;
        }
        .brand-signature {
            font-size: 10pt;
            color: #1D4F44;
            font-weight: bold;
        }
        .brand-sub {
            font-size: 8.5pt;
            color: #64748b;
        }
    </style>
</head>
<body>

    {{-- Header --}}
    <div class="header-box">
        <table style="width: 100%;">
            <tr>
                <td style="vertical-align: middle;">
                    <h1 class="auction-title">{{ $auction->name }}</h1>
                    <div>
                        <span class="badge-pill">مدينة {{ $auction->city ?? 'المملكة' }}</span>
                        @if($auction->type)
                            <span class="badge-pill">{{ $auction->type === 'electronic' ? 'مزاد إلكتروني' : 'مزاد حضوري' }}</span>
                        @endif
                        @if($auction->category)
                            <span class="badge-pill">{{ $auction->category }}</span>
                        @endif
                    </div>
                </td>
                <td style="text-align: left; vertical-align: top; width: 140px;">
                    <div class="brand-signature">{{ $tenantName }}</div>
                    <div class="brand-sub">كتيب البروشور الرسمي</div>
                </td>
            </tr>
        </table>
    </div>

    {{-- Cover Photo --}}
    @if(!empty($coverImageBase64))
        <div class="cover-photo-wrapper">
            <img src="{{ $coverImageBase64 }}" class="cover-photo" alt="{{ $auction->name }}">
        </div>
    @endif

    {{-- Key Specifications Table --}}
    <table class="data-table">
        <tr>
            <td class="label">تاريخ البداية</td>
            <td class="val">{{ $auction->start_date ? $auction->start_date->format('Y-m-d') : '-' }} ({{ $auction->start_time ?? '-' }})</td>
            <td class="label">تاريخ النهاية</td>
            <td class="val">{{ $auction->end_date ? $auction->end_date->format('Y-m-d') : '-' }} ({{ $auction->end_time ?? '-' }})</td>
        </tr>
        <tr>
            <td class="label">المنصة المعتمدة</td>
            <td class="val">{{ $auction->platform_name ?? 'المنصة الوطنية' }}</td>
            <td class="label">الجهة المشرفة</td>
            <td class="val">{{ $auction->supervising_entity ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">رقم الإسناد</td>
            <td class="val">{{ $auction->assignment?->assignment_number ?? '-' }}</td>
            <td class="label">عدد العقارات</td>
            <td class="val">{{ $auction->properties->count() }} عقار</td>
        </tr>
    </table>

    {{-- Description --}}
    @if($auction->description)
        <div class="description-box">
            <strong style="color: #1D4F44;">نبذة عن المزاد:</strong><br>
            {{ $auction->description }}
        </div>
    @endif

    {{-- Footer with QR and Platform Link --}}
    <table class="footer-table">
        <tr>
            @if(!empty($qrCodeBase64))
                <td class="qr-box">
                    <img src="{{ $qrCodeBase64 }}" class="qr-image" alt="QR Code">
                    <div class="qr-label">امسح للمزايدة</div>
                </td>
            @endif
            <td style="padding-right: 15px;">
                <div style="font-weight: bold; color: #1D4F44; font-size: 11pt;">للمشاركة والمزايدة عبر الرابط الإلكتروني:</div>
                <div style="color: #0284c7; font-size: 10pt; direction: ltr; text-align: right;">{{ $auction->platform_url ?? 'https://lot.sa' }}</div>
                <div style="font-size: 8.5pt; color: #94a3b8; margin-top: 4px;">تم تصدير هذا الكتيب آلياً بنظام لوت LOT المعتمد.</div>
            </td>
            <td style="text-align: left; width: 140px; font-size: 9pt; color: #64748b;">
                تاريخ الإصدار:<br>
                <strong>{{ now()->format('Y-m-d H:i') }}</strong>
            </td>
        </tr>
    </table>

</body>
</html>
