<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <title>تقرير التحصيلات المالية</title>
    <style>
        * {
            font-family: 'almarai', sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            direction: rtl;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
        }

        .header {
            background: linear-gradient(135deg, #372632 0%, #4cc1bb 100%);
            color: white;
            padding: 20px;
            text-align: center;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
        }

        .info-box {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .info-label {
            font-weight: bold;
            color: #495057;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table thead {
            background: #372632;
            color: white;
        }

        table th,
        table td {
            padding: 10px;
            text-align: right;
            border: 1px solid #dee2e6;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            display: inline-block;
        }

        .status-approved {
            background: #d4edda;
            color: #155724;
        }

        .status-pending {
            background: #fff3cd;
            color: #856404;
        }

        .status-rejected {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-top: 2px solid #372632;
        }

        .summary-box {
            background: #e7f3ff;
            border: 2px solid #4cc1bb;
            border-radius: 8px;
            padding: 15px;
            margin-top: 20px;
        }

        .summary-item {
            display: inline-block;
            margin: 0 10px;
        }

        .summary-value {
            font-weight: bold;
            color: #372632;
            font-size: 14px;
        }
    </style>
</head>

<body>
    <div class="header">
        <h1>تقرير التحصيلات المالية</h1>
        <div>{{ now()->format('Y-m-d H:i:s') }}</div>
    </div>

    <div class="info-box">
        <div class="info-row">
            <span class="info-label">الفترة الزمنية:</span>
            <span>{{ $dateRange }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">الفلاتر المطبقة:</span>
            <span>{{ $appliedFilters }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">إجمالي التحصيلات:</span>
            <span>{{ number_format($totalCollections) }} تحصيل</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th width="5%">م</th>
                <th width="20%">العميل</th>
                <th width="15%">رقم الحساب</th> <!-- إضافة عمود رقم الحساب -->
                <th width="15%">المبلغ</th>
                <th width="15%">رقم السند</th>
                <th width="10%">الحالة</th>
                <th width="15%">تاريخ التحصيل</th>
                <th width="20%">أنشأ بواسطة</th>
            </tr>
        </thead>
        <tbody>
            @foreach($collections as $index => $collection)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $collection->customer->name ?? 'غير محدد' }}</td>
                <td>{{ $collection->customer->account_number ?? 'غير محدد' }}</td> <!-- إضافة خلية رقم الحساب -->
                <td>{{ number_format($collection->collected_amount, 2) }} ريال</td>
                <td>{{ $collection->receipt_number ?: 'غير محدد' }}</td>
                <td>
                    @php
                    $statusClass = match($collection->status) {
                    'approved' => 'status-approved',
                    'pending' => 'status-pending',
                    'rejected' => 'status-rejected',
                    default => ''
                    };
                    $statusLabel = match($collection->status) {
                    'approved' => 'معتمد',
                    'pending' => 'معلق',
                    'rejected' => 'مرفوض',
                    default => $collection->status
                    };
                    @endphp
                    <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                </td>
                <td>{{ $collection->collection_date ?
                    \Carbon\Carbon::parse($collection->collection_date)->format('Y-m-d') : 'غير محدد' }}</td>
                <td>{{ $collection->addedBy->name ?? 'غير محدد' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php
    $approvedCollections = $collections->where('status', 'approved');
    $pendingCollections = $collections->where('status', 'pending');
    $rejectedCollections = $collections->where('status', 'rejected');
    $totalApprovedAmount = $approvedCollections->sum('collected_amount');
    $totalPendingAmount = $pendingCollections->sum('collected_amount');
    @endphp

    <div class="summary-box">
        <div class="summary-item">
            <span class="info-label">التحصيلات المعتمدة:</span>
            <span class="summary-value">{{ $approvedCollections->count() }}</span>
        </div>
        <div class="summary-item">
            <span class="info-label">المبلغ المعتمد:</span>
            <span class="summary-value">{{ number_format($totalApprovedAmount, 2) }} ريال</span>
        </div>
        <div class="summary-item">
            <span class="info-label">التحصيلات المعلقة:</span>
            <span class="summary-value">{{ $pendingCollections->count() }}</span>
        </div>
        <div class="summary-item">
            <span class="info-label">المبلغ المعلق:</span>
            <span class="summary-value">{{ number_format($totalPendingAmount, 2) }} ريال</span>
        </div>
        <div class="summary-item">
            <span class="info-label">التحصيلات المرفوضة:</span>
            <span class="summary-value">{{ $rejectedCollections->count() }}</span>
        </div>
    </div>

    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة النظام المالي</p>
        <p>جميع الحقوق محفوظة © {{ date('Y') }}</p>
    </div>
</body>

</html>