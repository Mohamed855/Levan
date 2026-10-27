<!DOCTYPE html>
<html dir="rtl" lang="ar">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>{{ $auction->name }} - كتيب البروشور</title>
    <style>
        @page {
            margin-top: 16mm;
            margin-bottom: 18mm;
            margin-left: 14mm;
            margin-right: 14mm;
        }

        body {
            font-family: 'Cairo', 'DejaVu Sans', 'Arial', sans-serif;
            direction: rtl;
            text-align: right;
            color: #2b2b2b;
            font-size: 11pt;
            line-height: 1.5;
            background-color: transparent;
        }

        .header-box {
            border-bottom: 3px solid #1D4F44;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .auction-title {
            color: #1D4F44;
            font-size: 22pt;
            font-weight: bold;
            margin: 0 0 6px 0;
            line-height: 1.2;
        }

        .badge-pill {
            display: inline-block;
            background-color: #F7F3EC;
            color: #1D4F44;
            border: 1px solid #1D4F44;
            padding: 3px 10px;
            border-radius: 16px;
            font-size: 9.5pt;
            font-weight: bold;
            margin-left: 4px;
        }

        .cover-photo-wrapper {
            text-align: center;
            margin-bottom: 16px;
        }

        .cover-photo {
            width: 100%;
            max-height: 230px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #cbd5e1;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .data-table td {
            padding: 8px 10px;
            vertical-align: middle;
            border: 1px solid #e2e8f0;
            font-size: 10pt;
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
            padding: 10px 14px;
            margin-bottom: 16px;
            border-radius: 0 6px 6px 0;
            font-size: 10pt;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
            padding-top: 10px;
            border-top: 2px dashed #cbd5e1;
        }

        .footer-table td {
            vertical-align: middle;
        }

        .qr-box {
            width: 90px;
            text-align: center;
        }

        .qr-image {
            width: 80px;
            height: 80px;
        }

        .qr-label {
            font-size: 8pt;
            color: #64748b;
            margin-top: 3px;
        }

        .brand-signature {
            font-size: 10.5pt;
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

    {{-- ==================== الصفحة الأولى: غلاف المزاد والبيانات العامة ==================== --}}
    <div class="first-page">
        {{-- Header --}}
        <div class="header-box">
            <table style="width: 100%;">
                <tr>
                    <td style="vertical-align: middle;">
                        <h1 class="auction-title">{{ $auction->name }}</h1>
                        <div>
                            <span class="badge-pill">مدينة {{ $auction->city ?? 'المملكة' }}</span>
                            @if ($auction->type)
                                <span
                                    class="badge-pill">{{ $auction->type === 'electronic' ? 'مزاد إلكتروني' : 'مزاد حضوري' }}</span>
                            @endif
                            @if ($auction->category)
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
        @if (!empty($coverImageBase64))
            <div class="cover-photo-wrapper">
                <img src="{{ $coverImageBase64 }}" class="cover-photo" alt="{{ $auction->name }}">
            </div>
        @endif

        {{-- Key Specifications Table --}}
        <table class="data-table">
            <tr>
                <td class="label">تاريخ البداية</td>
                <td class="val">{{ $auction->start_date ? $auction->start_date->format('Y-m-d') : '-' }}
                    ({{ $auction->start_time ?? '-' }})</td>
                <td class="label">تاريخ النهاية</td>
                <td class="val">{{ $auction->end_date ? $auction->end_date->format('Y-m-d') : '-' }}
                    ({{ $auction->end_time ?? '-' }})</td>
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
                <td class="val" style="font-weight: bold; color: #1D4F44;">{{ $auction->properties->count() }} عقار
                </td>
            </tr>
        </table>

        {{-- Description --}}
        @if ($auction->description)
            <div class="description-box">
                <strong style="color: #1D4F44;">نبذة عن المزاد:</strong><br>
                {{ $auction->description }}
            </div>
        @endif

        {{-- Footer with QR and Platform Link --}}
        <table class="footer-table">
            <tr>
                @if (!empty($qrCodeBase64))
                    <td class="qr-box">
                        <img src="{{ $qrCodeBase64 }}" class="qr-image" alt="QR Code">
                        <div class="qr-label">امسح للمزايدة</div>
                    </td>
                @endif
                <td style="padding-right: 15px;">
                    <div style="font-weight: bold; color: #1D4F44; font-size: 10.5pt;">للمشاركة والمزايدة عبر الرابط
                        الإلكتروني:</div>
                    <div style="color: #0284c7; font-size: 9.5pt; direction: ltr; text-align: right;">
                        {{ $auction->platform_url ?? 'https://lot.sa' }}</div>
                    <div style="font-size: 8pt; color: #94a3b8; margin-top: 3px;">تم تصدير هذا الكتيب آلياً بنظام لوت
                        LOT المعتمد.</div>
                </td>
                <td style="text-align: left; width: 140px; font-size: 8.5pt; color: #64748b;">
                    تاريخ الإصدار:<br>
                    <strong>{{ now()->format('Y-m-d H:i') }}</strong>
                </td>
            </tr>
        </table>
    </div>

    {{-- ==================== الصفحات التالية: تفاصيل وصور العقارات المشمولة ==================== --}}
    @if (isset($propertiesData) && $propertiesData->isNotEmpty())
        @foreach ($propertiesData as $index => $prop)
            <pagebreak />
            <div class="property-page">
                <div class="header-box" style="margin-bottom: 12px;">
                    <table style="width: 100%;">
                        <tr>
                            <td style="vertical-align: middle;">
                                <span class="badge-pill" style="margin-right: 0;">عقار رقم ({{ $index + 1 }} من
                                    {{ $propertiesData->count() }})</span>
                                <h2 class="auction-title" style="font-size: 17pt; margin: 4px 0;">{{ $prop['name'] }}
                                </h2>
                                <div style="color: #64748b; font-size: 9.5pt;">
                                    المدينة: <strong>{{ $prop['city'] }}</strong> — الحي:
                                    <strong>{{ $prop['district'] }}</strong> | رقم العقار:
                                    <strong>{{ $prop['property_number'] }}</strong>
                                </div>
                            </td>
                            <td style="text-align: left; vertical-align: top; width: 130px;">
                                <span class="badge-pill"
                                    style="background-color: #1D4F44; color: #ffffff;">{{ $prop['type_label'] }}</span>
                            </td>
                        </tr>
                    </table>
                </div>

                {{-- Property Images Grid --}}
                @if (!empty($prop['main_image']) || !empty($prop['aerial_image']))
                    <table style="width: 100%; margin-bottom: 14px; border-collapse: collapse;">
                        <tr>
                            @if (!empty($prop['main_image']) && !empty($prop['aerial_image']))
                                <td style="width: 50%; padding-left: 6px; text-align: center;">
                                    <div
                                        style="font-size: 8.5pt; color: #64748b; margin-bottom: 3px; font-weight: bold;">
                                        صورة العقار الرئيسية</div>
                                    <img src="{{ $prop['main_image'] }}"
                                        style="width: 100%; max-height: 180px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;"
                                        alt="">
                                </td>
                                <td style="width: 50%; padding-right: 6px; text-align: center;">
                                    <div
                                        style="font-size: 8.5pt; color: #64748b; margin-bottom: 3px; font-weight: bold;">
                                        المصور الجوي / الكروكي</div>
                                    <img src="{{ $prop['aerial_image'] }}"
                                        style="width: 100%; max-height: 180px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;"
                                        alt="">
                                </td>
                            @elseif(!empty($prop['main_image']))
                                <td style="width: 100%; text-align: center;">
                                    <img src="{{ $prop['main_image'] }}"
                                        style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;"
                                        alt="">
                                </td>
                            @elseif(!empty($prop['aerial_image']))
                                <td style="width: 100%; text-align: center;">
                                    <img src="{{ $prop['aerial_image'] }}"
                                        style="width: 100%; max-height: 220px; object-fit: cover; border-radius: 6px; border: 1px solid #cbd5e1;"
                                        alt="">
                                </td>
                            @endif
                        </tr>
                    </table>
                @endif

                {{-- Property Specifications Table --}}
                <table class="data-table">
                    <tr>
                        <td class="label">المساحة الإجمالية</td>
                        <td class="val" style="font-weight: bold; color: #1D4F44;">{{ $prop['area'] }}</td>
                        <td class="label">القيمة التقديرية</td>
                        <td class="val" style="font-weight: bold; color: #1D4F44;">{{ $prop['estimated_value'] }}
                        </td>
                    </tr>
                    <tr>
                        <td class="label">رقم الصك</td>
                        <td class="val">{{ $prop['deed_number'] }}</td>
                        <td class="label">تاريخ الصك</td>
                        <td class="val">{{ $prop['deed_date'] }}</td>
                    </tr>
                    <tr>
                        <td class="label">المدينة والحي</td>
                        <td class="val">{{ $prop['city'] }} - {{ $prop['district'] }}</td>
                        <td class="label">نوع العقار</td>
                        <td class="val">{{ $prop['type_label'] }}</td>
                    </tr>
                </table>

                {{-- Boundaries Table if available --}}
                @if (!empty($prop['boundaries']) && count($prop['boundaries']) > 0)
                    <div style="font-weight: bold; color: #1D4F44; font-size: 10.5pt; margin-bottom: 5px;">حدود وأطوال
                        العقار:</div>
                    <table class="data-table" style="margin-bottom: 12px;">
                        <thead>
                            <tr style="background-color: #F7F3EC;">
                                <th
                                    style="padding: 5px 8px; border: 1px solid #cbd5e1; font-size: 9.5pt; color: #1D4F44;">
                                    الاتجاه</th>
                                <th
                                    style="padding: 5px 8px; border: 1px solid #cbd5e1; font-size: 9.5pt; color: #1D4F44;">
                                    الحد</th>
                                <th
                                    style="padding: 5px 8px; border: 1px solid #cbd5e1; font-size: 9.5pt; color: #1D4F44;">
                                    الطول (م)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prop['boundaries'] as $boundary)
                                <tr>
                                    <td class="label" style="width: 25%; font-weight: normal;">
                                        {{ $boundary->direction_label ?? $boundary->direction }}</td>
                                    <td class="val" style="width: 50%;">
                                        {{ $boundary->boundary_description ?? '-' }}</td>
                                    <td class="val" style="width: 25%; text-align: center; font-weight: bold;">
                                        {{ $boundary->length ? number_format((float) $boundary->length, 2) . ' م' : '-' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif

                {{-- Property Description / Location --}}
                @if (!empty($prop['short_description']))
                    <div class="description-box" style="margin-bottom: 10px;">
                        <strong>وصف العقار:</strong> {{ $prop['short_description'] }}
                    </div>
                @endif
            </div>
        @endforeach
    @endif

</body>

</html>
