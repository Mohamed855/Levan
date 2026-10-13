<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <title>تقرير متابعات العملاء</title>
    <style>
        body {
            font-family: 'Almarai', sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 0;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .filters {
            margin-bottom: 15px;
            font-size: 11px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            direction: rtl;
        }
        th {
            background-color: #1F4E78;
            color: white;
            text-align: center;
            padding: 8px;
            font-weight: bold;
        }
        td {
            padding: 6px 8px;
            text-align: right;
            border: 1px solid #ddd;
        }
        .text-center {
            text-align: center;
        }
        .text-left {
            text-align: left;
        }
        .stats-row {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .stats-row td {
            text-align: center;
            padding: 10px;
            color: #1F4E78;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>تقرير متابعات العملاء</h2>
        <div class="filters">
            <strong>الفترة:</strong> {{ $dateRange }} | 
            <strong>الفلاتر:</strong> {{ $appliedFilters }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">م</th>
                <th width="20%">العميل</th>
                <th width="15%">طريقة الاتصال</th>
                <th width="15%">نتيجة الاتصال</th>
                <th width="15%">حالة العميل</th>
                <th width="15%">الاتصال القادم</th>
                <th width="15%">تاريخ الإنشاء</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($follows as $index => $follow)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td>{{ $follow->customer->name ?? 'غير محدد' }}</td>
                <td>{{ $follow->contactMethod->name ?? 'غير محدد' }}</td>
                <td>{{ $follow->contactResult->name ?? 'غير محدد' }}</td>
                <td>{{ $follow->customerStatus->name ?? 'غير محدد' }}</td>
                <td>
                    @if($follow->next_contact_datetime)
                        {{ \Carbon\Carbon::parse($follow->next_contact_datetime)->format('Y-m-d H:i') }}
                    @else
                        غير محدد
                    @endif
                </td>
                <td>{{ \Carbon\Carbon::parse($follow->created_at)->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="stats-row">
                <td colspan="2">
                    إجمالي المتابعات: {{ number_format($totalFollows) }}
                </td>
                <td colspan="2">
                    تاريخ التقرير: {{ \Carbon\Carbon::now()->format('Y-m-d') }}
                </td>
                <td colspan="2">
                    الوقت: {{ \Carbon\Carbon::now()->format('H:i') }}
                </td>
                <td>
                    النظام: LOT
                </td>
            </tr>
        </tfoot>
    </table>
    
    <div class="footer">
        تم إنشاء التقرير بواسطة نظام إدارة العملاء - {{ \Carbon\Carbon::now()->format('Y-m-d H:i') }}
    </div>
</body>
</html>