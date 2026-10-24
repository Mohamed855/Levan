<!doctype html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>فاتورة {{ $bill->bill_number }}</title>
    <style>
        body { color: #1f2937; font-family: Tahoma, Arial, sans-serif; margin: 0; background: #f8fafc; }
        main { background: #fff; margin: 2rem auto; max-width: 860px; padding: 3rem; }
        .header, .details { display: flex; justify-content: space-between; gap: 2rem; }
        .muted { color: #64748b; } .status { border-radius: 999px; display: inline-block; padding: .3rem .75rem; background: #e2e8f0; }
        table { border-collapse: collapse; margin-top: 2rem; width: 100%; } th, td { border: 1px solid #cbd5e1; padding: .75rem; text-align: right; }
        th { background: #f1f5f9; } .amount { text-align: left; white-space: nowrap; } .total { font-size: 1.1rem; font-weight: 700; }
        .actions { margin-top: 2rem; } button { background: #2563eb; border: 0; border-radius: .35rem; color: #fff; cursor: pointer; padding: .6rem 1rem; }
        @media print { body { background: #fff; } main { margin: 0; max-width: none; padding: 0; } .actions { display: none; } }
    </style>
</head>
<body>
<main>
    <div class="header">
        <section>
            <h1>{{ $general['office_name'] ?? $general['logo_text'] ?? 'منصة لوت' }}</h1>
            @if(!empty($general['office_address']))<p class="muted">{{ $general['office_address'] }}</p>@endif
            @if(!empty($general['commercial_register']))<p class="muted">س.ت: {{ $general['commercial_register'] }}</p>@endif
            @if(!empty($general['tax_number']))<p class="muted">الرقم الضريبي: {{ $general['tax_number'] }}</p>@endif
        </section>
        <section>
            <h2>فاتورة</h2>
            <p>رقم الفاتورة: <strong>{{ $bill->bill_number }}</strong></p>
            <p>تاريخ الإصدار: {{ $bill->created_at?->format('Y-m-d') }}</p>
            <span class="status">{{ $bill->status->label() }}</span>
        </section>
    </div>

    <hr>

    <div class="details">
        <section>
            <h3>موجهة إلى</h3>
            <p><strong>{{ $bill->tenant?->company_name ?? data_get($bill->metadata, 'provisioning.billing_company_name', 'غير محدد') }}</strong></p>
            @if($bill->tenant?->commercial_number)<p class="muted">س.ت: {{ $bill->tenant->commercial_number }}</p>@endif
            @if($bill->tenant?->tax_number)<p class="muted">الرقم الضريبي: {{ $bill->tenant->tax_number }}</p>@endif
        </section>
        <section>
            <h3>بيانات السداد</h3>
            <p>الطريقة: {{ $bill->payment_method?->label() ?? 'غير محددة' }}</p>
            @if($bill->paid_at)<p>تاريخ السداد: {{ $bill->paid_at->format('Y-m-d H:i') }}</p>@endif
        </section>
    </div>

    <table>
        <thead><tr><th>البند</th><th class="amount">المبلغ</th></tr></thead>
        <tbody>
            <tr><td>{{ $bill->type->label() }}</td><td class="amount">{{ number_format((float) $bill->amount, 2) }} ر.س</td></tr>
            <tr><td>المبلغ قبل الضريبة</td><td class="amount">{{ number_format($subtotalAmount, 2) }} ر.س</td></tr>
            <tr><td>ضريبة القيمة المضافة (15%)</td><td class="amount">{{ number_format($taxAmount, 2) }} ر.س</td></tr>
            @if($discountAmount !== null && $discountAmount > 0)
                <tr><td>الخصم</td><td class="amount">- {{ number_format($discountAmount, 2) }} ر.س</td></tr>
            @endif
            @if($bill->settled_amount !== null)
                <tr><td>المبلغ المسدد</td><td class="amount">{{ number_format((float) $bill->settled_amount, 2) }} ر.س</td></tr>
            @endif
            <tr class="total"><td>الإجمالي</td><td class="amount">{{ number_format((float) $bill->amount, 2) }} ر.س</td></tr>
        </tbody>
    </table>
    <div class="actions"><button type="button" onclick="window.print()">طباعة</button></div>
</main>
</body>
</html>
