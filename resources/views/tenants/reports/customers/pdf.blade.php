<div class="content">
    <div class="report-header">
        <h2>تقرير العملاء التفصيلي</h2>
        <div class="header-info">
            <span class="date-range">الفترة: {{ $dateRange }}</span>
            <span class="applied-filters">الفلاتر: {{ $appliedFilters }}</span>
        </div>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>م</th>
                <th>اسم العميل</th>
                <th>النوع</th>
                <th>رقم الهوية</th>
                <th>الهاتف</th>
                <th>الشركة</th>
                <th>المحفظة</th>
                <th>مسؤول العلاقة</th>
                <th>الحالة</th>
                <th>نتيجة الاتصال</th>
                <th>المديونية</th>
                <th>تاريخ الإضافة</th>
            </tr>
        </thead>
        <tbody>
            @forelse($customers as $index => $customer)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td class="text-right">{{ $customer->name }}</td>
                    <td>{{ $customer->customer_type === 'individual' ? 'فردي' : 'شركة' }}</td>
                    <td>{{ $customer->identity_number ?: '—' }}</td>
                    <td>{{ $customer->phone ?: '—' }}</td>
                    <td class="text-right">{{ $customer->company->name ?? '—' }}</td>
                    <td class="text-right">{{ $customer->portfolio->name ?? '—' }}</td>
                    <td class="text-right">{{ $customer->relationshipManager->name ?? '—' }}</td>
                    <td>{{ $customer->customerStatus->name ?? '—' }}</td>
                    <td>{{ $customer->lastContactResult->name ?? 'غير محدد' }}</td>
                    <td class="{{ $customer->debt_amount > 0 ? 'debt-positive' : 'debt-zero' }}">
                        {{ number_format($customer->debt_amount, 2) }}
                    </td>
                    <td>{{ $customer->created_at->format('Y-m-d') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="12" class="no-records">لا توجد بيانات للعرض</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="report-footer">
        تم الإنشاء في {{ now()->format('Y/m/d H:i:s') }}
    </div>
</div>


<style>
    @page {
        margin: 15px;
    }

    body {
        font-family: almarai;
        font-size: 0.75rem;
        direction: rtl;
        margin: 0;
        color: #333;
    }

    .report-header {
        margin-bottom: 1.25rem;
        border-bottom: 2px solid #1D4F44;
        padding-bottom: 15px;
        text-align: center;
    }

    .report-header h2 {
        margin: 0 0 10px 0;
        font-size: 22px;
        color: #1D4F44;
    }

    .data-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #ddd;
        font-size: 9px;
    }

    .data-table th,
    .data-table td {
        border: 1px solid #ddd;
        padding: 0.25rem 3px;
        text-align: center;
    }

    .data-table th {
        background-color: #1D4F44;
        color: #fff;
    }

    .debt-positive {
        color: #e74c3c;
        font-weight: 600;
    }

    .debt-zero {
        color: #27ae60;
        font-weight: 600;
    }

    .report-footer {
        margin-top: 1.25rem;
        font-size: 11px;
        color: #777;
        text-align: center;
        border-top: 1px solid #ddd;
        padding-top: 10px;
    }
</style>
