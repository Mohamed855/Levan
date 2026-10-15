<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تقرير أداء الموظفين</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'almarai', sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #333;
            direction: rtl;
        }

        .header {
            text-align: center;
            margin-bottom: 1.25rem;
            padding: 15px;
            background: linear-gradient(135deg, #1f4e78 0%, #4cc1bb 100%);
            color: white;
            border-radius: 0.5rem;
        }

        .header h1 {
            font-size: 18px;
            margin-bottom: 0.5rem;
            font-weight: bold;
        }

        .header p {
            font-size: 10px;
            margin: 3px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        table thead {
            background: #1f4e78;
            color: white;
        }

        table thead th {
            padding: 0.5rem 6px;
            text-align: center;
            font-size: 9px;
            font-weight: bold;
            border: 1px solid #0d2847;
        }

        table tbody td {
            padding: 6px;
            text-align: center;
            border: 1px solid #ddd;
            font-size: 0.5rem;
        }

        table tbody tr:nth-child(even) {
            background: #f8f9fa;
        }

        table tbody tr:hover {
            background: #e9ecef;
        }

        .achievement-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 10px;
            font-weight: bold;
            font-size: 0.5rem;
        }

        .achievement-success {
            background: #d4edda;
            color: #155724;
        }

        .achievement-warning {
            background: #fff3cd;
            color: #856404;
        }

        .achievement-danger {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 25px;
            padding: 0.75rem;
            background: #f8f9fa;
            border-top: 2px solid #1f4e78;
            text-align: center;
            font-size: 0.5rem;
            color: #666;
        }

        .text-right {
            text-align: right !important;
        }

        .text-primary {
            color: #1f4e78;
            font-weight: bold;
        }

        .text-success {
            color: #28a745;
            font-weight: bold;
        }
    </style>
</head>

<body>
    @php
        $formatReportNumber = fn($value) => \App\Support\ReportNumberFormatter::format($value);
    @endphp
    {{-- الرأس --}}
    <div class="header">
        <h1>👥 تقرير أداء الموظفين</h1>
        <p>الفترة: {{ $dateRange }}</p>
        <p>تاريخ التقرير: {{ now()->format('Y-m-d H:i') }}</p>
    </div>

    {{-- الجدول --}}
    <table>
        <thead>
            <tr>
                <th style="width: 3%;">م</th>
                <th style="width: 12%;">الشركة</th>
                <th style="width: 12%;">الموظف</th>
                <th style="width: 7%;">العملاء</th>
                <th style="width: 10%;">المحفظة</th>
                <th style="width: 8%;">متوسط السداد</th>
                <th style="width: 7%;">المسددين</th>
                <th style="width: 7%;">المكالمات</th>
                <th style="width: 7%;">غير متواصل</th>
                <th style="width: 6%;">المهام المتأخرة</th>
                <th style="width: 6%;">الوعود</th>
                <th style="width: 9%;">مبلغ الوعد</th>
                <th style="width: 6%;">نسبة الوعد</th>
                <th style="width: 6%;">نسبة الهدف</th>
            </tr>
        </thead>
        <tbody>
            @forelse($employeesPerformance as $index => $employee)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-right">{{ $employee['company_name'] }}</td>
                    <td class="text-right text-primary">{{ $employee['employee_name'] }}</td>
                    <td>{{ $formatReportNumber($employee['customers_count']) }}</td>
                    <td>{{ $formatReportNumber($employee['total_portfolio']) }}</td>
                    <td class="text-success">{{ $formatReportNumber($employee['avg_payment']) }}</td>
                    <td>{{ $formatReportNumber($employee['paid_customers']) }}</td>
                    <td>{{ $formatReportNumber($employee['avg_calls']) }}</td>
                    <td>{{ $formatReportNumber($employee['not_contacted']) }}</td>
                    <td>{{ $formatReportNumber($employee['overdue_tasks'] ?? 0) }}</td>
                    <td>{{ $formatReportNumber($employee['promises_count']) }}</td>
                    <td>{{ $formatReportNumber($employee['promises_amount']) }}</td>
                    <td>
                        @php
                            $promiseRate = $employee['promise_achievement_rate'];
                            $promiseClass =
                                $promiseRate >= 80
                                    ? 'achievement-success'
                                    : ($promiseRate >= 50
                                        ? 'achievement-warning'
                                        : 'achievement-danger');
                        @endphp
                        <span class="achievement-badge {{ $promiseClass }}">
                            {{ $formatReportNumber($promiseRate) }}%
                        </span>
                    </td>
                    <td>
                        @php
                            $targetRate = $employee['target_achievement_rate'];
                            $targetClass =
                                $targetRate >= 100
                                    ? 'achievement-success'
                                    : ($targetRate >= 50
                                        ? 'achievement-warning'
                                        : 'achievement-danger');
                        @endphp
                        <span class="achievement-badge {{ $targetClass }}">
                            {{ $formatReportNumber($targetRate) }}%
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="14" style="text-align: center; padding: 1.25rem; color: #999;">
                        لا توجد بيانات للعرض
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- التذييل --}}
    <div class="footer">
        <p>تم إنشاء هذا التقرير بواسطة نظام إدارة التحصيل | {{ now()->format('Y-m-d H:i:s') }}</p>
        <p>إجمالي الموظفين: {{ $formatReportNumber(count($employeesPerformance)) }} | إجمالي العملاء:
            {{ $formatReportNumber(collect($employeesPerformance)->sum('customers_count')) }}</p>
    </div>
</body>

</html>
