<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير أداء الشركات</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'almarai', sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #000;
            direction: rtl;
            background: #fff;
        }

        /* ✅ جدول الإحصائيات في الأعلى */
        .statistics-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            border: 2px solid #000;
        }

        .statistics-table td {
            padding: 0.5rem;
            text-align: center;
            border: 1px solid #000;
            vertical-align: middle;
        }

        .statistics-table .label {
            font-size: 9px;
            color: #666;
            display: block;
            margin-bottom: 3px;
            font-weight: normal;
        }

        .statistics-table .value {
            font-size: 0.75rem;
            font-weight: bold;
            color: #000;
            display: block;
        }

        /* ✅ جدول البيانات */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .data-table thead {
            background: #e8e8e8;
            color: #000;
        }

        .data-table thead th {
            padding: 0.5rem 0.25rem;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #000;
        }

        .data-table tbody td {
            padding: 6px 0.25rem;
            text-align: center;
            border: 1px solid #000;
            font-size: 0.5rem;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f5f5f5;
        }

        /* ✅ شارات الإنجاز */
        .achievement-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 0.5rem;
            font-weight: bold;
            font-size: 0.5rem;
            border: 1px solid #000;
        }

        .achievement-success {
            background: #d8d8d8;
            color: #000;
        }

        .achievement-warning {
            background: #f0f0f0;
            color: #000;
        }

        .achievement-danger {
            background: #fff;
            color: #000;
        }
    </style>
</head>

<body>
    {{-- ✅ جدول الإحصائيات --}}
    <table class="statistics-table">
        <tr>
            <td>
                <span class="label">إجمالي المديونية</span>
                <span class="value">{{ number_format($statistics['total_portfolio'] / 1000000, 1) }}M</span>
            </td>
            <td>
                <span class="label">إجمالي التحصيل</span>
                <span class="value">{{ number_format($statistics['total_collections'] / 1000, 0) }}K</span>
            </td>
            <td>
                <span class="label">نسبة التحصيل</span>
                <span class="value">{{ number_format($statistics['collection_percentage'], 1) }}%</span>
            </td>
        </tr>
        <tr>
            <td>
                <span class="label">عدد الشركات</span>
                <span class="value">{{ $statistics['companies_count'] }}</span>
            </td>
            <td>
                <span class="label">العملاء المسددين</span>
                <span class="value">{{ number_format($statistics['paid_customers_count']) }}</span>
            </td>
            <td>
                <span class="label">المبلغ المتبقي</span>
                <span class="value">{{ number_format($statistics['total_remaining'] / 1000000, 1) }}M</span>
            </td>
        </tr>
    </table>

    {{-- ✅ جدول البيانات --}}
    <table class="data-table">
        <thead>
            <tr>
                <th style="width: 5%;">م</th>
                <th style="width: 25%;">الشركة</th>
                <th style="width: 10%;">العملاء</th>
                <th style="width: 15%;">المديونية</th>
                <th style="width: 12%;">المستهدف</th>
                <th style="width: 13%;">السداد</th>
                <th style="width: 10%;">التسويات</th>
                <th style="width: 10%;">الإنجاز</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($data as $index => $company)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td style="text-align: right; padding-right: 0.5rem;">{{ $company['company_name'] }}</td>
                    <td>{{ number_format($company['customers_count']) }}</td>
                    <td>{{ number_format($company['total_portfolio'], 0) }}</td>
                    <td>{{ number_format($company['customer_target'], 0) }}</td>
                    <td>{{ number_format($company['total_payment'], 0) }}</td>
                    <td>{{ number_format($company['total_settlements'], 0) }}</td>
                    <td>
                        @php
                            $rate = $company['achievement_rate'];
                            $class =
                                $rate >= 100
                                    ? 'achievement-success'
                                    : ($rate >= 50
                                        ? 'achievement-warning'
                                        : 'achievement-danger');
                        @endphp
                        <span class="achievement-badge {{ $class }}">
                            {{ number_format($rate, 1) }}%
                        </span>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>

</html>
