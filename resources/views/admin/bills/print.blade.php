<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فاتورة #{{ $bill->bill_number }}</title>


    <style>
        :root {
            --brand: #5cb58d;
            --brand-dark: #4a9a76;
            --brand-bg: #f0faf5;
            --text-primary: #1a202c;
            --text-secondary: #4a5568;
            --text-muted: #94a3b8;
            --border: #e8ecf0;
        }

        .text-start {
            text-align: center
        }

        * {
            box-sizing: border-box;
        }

        body {
            background: #eef1f4;
            font-family: 'Cairo', 'Segoe UI', Tahoma, Arial, sans-serif;
            color: var(--text-primary);
            font-size: 14px;
            line-height: 1.6;
            -webkit-font-smoothing: antialiased;
        }

        .invoice-wrapper {
            background: #fff;
            max-width: 900px;
            margin: 28px auto 48px;
            padding: 52px 56px;
            border: 1px solid #dde3ea;
        }

        /* ── Header ── */
        .inv-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 36px;
            padding-bottom: 15px;
            border-bottom: 3px solid var(--border);
        }

        .inv-title {
            font-size: 32px;
            font-weight: 700;
            color: var(--text-primary);
            text-transform: uppercase;
            letter-spacing: 3px;
            line-height: 1.1;
        }

        .inv-status {
            display: inline-flex;
            align-items: center;
            padding: 4px 14px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            letter-spacing: 0.3px;
        }

        /* ── Company block ── */
        .company-block {
            min-width: 220px;
        }

        .company-block .company-name {
            font-size: 20px;
            font-weight: 700;
            color: var(--brand);
            letter-spacing: -0.3px;
            line-height: 1.2;
            margin-bottom: 10px;
        }

        .company-block .company-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 4px 10px;
            font-size: 12px;
        }

        .company-block .c-label {
            color: var(--text-muted);
            white-space: nowrap;
            font-weight: 500;
            text-align: right;
        }

        .company-block .c-value {
            color: var(--text-secondary);
            font-weight: 500;
        }

        /* ── Info section ── */
        .info-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            margin-bottom: 32px;
            border: 1px solid var(--border);
        }

        .info-cell {
            padding: 20px 24px;
        }

        .info-cell+.info-cell {
            border-right: 1px solid var(--border);
        }

        .info-cell-label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.6px;
            color: var(--brand);
            margin-bottom: 12px;
        }

        .info-cell address {
            font-size: 13px;
            line-height: 2;
            color: var(--text-secondary);
            margin: 0;
        }

        .info-cell address strong {
            display: block;
            font-size: 15px;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 3px;
            line-height: 1.4;
        }

        /* ── Invoice details grid ── */
        .inv-details {
            display: grid;
            grid-template-columns: auto 1fr;
            gap: 7px 20px;
        }

        .d-lbl {
            color: var(--text-muted);
            white-space: nowrap;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.8;
        }

        .d-val {
            color: var(--text-primary);
            font-weight: 600;
            font-size: 13px;
            text-align: left;
            line-height: 1.8;
        }

        .d-val.green {
            color: #16a34a;
        }

        /* ── Coupon ── */
        .coupon-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--brand-bg);
            border-right: 3px solid var(--brand);
            padding: 10px 16px;
            font-size: 13px;
            font-weight: 600;
            color: #166534;
            margin-bottom: 0;
        }

        .coupon-bar .badge {
            background: var(--brand);
            color: white;
            padding: 3px 12px;
            font-size: 12px;
            font-weight: 600;
            border-radius: 4px;
            letter-spacing: 0.5px;
        }

        /* ── Table ── */
        .inv-table {
            width: 100%;
            border-collapse: collapse;
        }

        .inv-table thead tr {
            background: #f8fafc;
            border-top: 2px solid var(--text-primary);
            border-bottom: 2px solid var(--text-primary);
        }

        .inv-table thead th {
            padding: 12px 16px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.4px;
            color: var(--text-primary);
        }

        .inv-table tbody td {
            padding: 14px 16px;
            font-size: 14px;
            color: var(--text-secondary);
            border-bottom: 1px solid #f1f5f9;
            line-height: 1.5;
        }

        .inv-table tbody tr:nth-child(even) td {
            background: #fafbfc;
        }

        .inv-table .sub-row td {
            padding: 9px 16px;
            font-size: 12.5px;
            color: #64748b;
            background: #fafafa !important;
            font-weight: 500;
        }

        .inv-table .discount-row td {
            padding: 9px 16px;
            font-size: 12.5px;
            color: #16a34a;
            background: var(--brand-bg) !important;
            font-weight: 600;
        }

        .inv-table .total-row td {
            padding: 15px 16px;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-primary);
            background: #f8fafc !important;
            border-top: 2px solid var(--text-primary);
            border-bottom: 2px solid var(--text-primary);
            letter-spacing: 0.5px;
        }

        .inv-table .total-row td:first-child {
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ── Buttons ── */
        .btn-brand {
            background: var(--brand);
            border: none;
            color: #fff;
            font-family: 'Cairo', sans-serif;
            font-weight: 700;
            font-size: 14px;
            padding: 10px 28px;
            cursor: pointer;
            transition: background .2s;
            letter-spacing: 0.3px;
        }

        .btn-brand:hover {
            background: var(--brand-dark);
        }

        .btn-outline {
            background: transparent;
            border: 1.5px solid #cbd5e0;
            color: var(--text-secondary);
            font-family: 'Cairo', sans-serif;
            font-weight: 600;
            font-size: 14px;
            padding: 10px 28px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: border-color .2s, color .2s;
            letter-spacing: 0.3px;
        }

        .btn-outline:hover {
            border-color: #94a3b8;
            color: var(--text-primary);
        }

        .action-row {
            justify-content: end;
            display: flex;
            gap: 10px;
            margin-top: 28px;
        }

        /* ── Footer ── */
        .inv-footer {
            text-align: center;
            font-size: 12px;
            font-weight: 500;
            color: #a0aec0;
            margin-top: 36px;
            padding-top: 20px;
            border-top: 1px solid var(--border);
            letter-spacing: 0.3px;
        }

        .inv-footer strong {
            color: var(--brand);
            font-weight: 700;
        }

        /* ── Print ── */
        @media print {
            body {
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .invoice-wrapper {
                margin: 0;
                border: none;
                padding: 1cm 1.4cm;
            }

            @page {
                size: A4;
                margin: 0;
            }

            body {
                print-color-adjust: exact;
                -webkit-print-color-adjust: exact;
            }
        }
    </style>
</head>

<body>
    <div class="invoice-wrapper">

        {{-- ── Header ── --}}
        <div class="inv-header">

            {{-- : بيانات الشركة --}}
            <div class="company-block">
                <div class="company-name">{{ $settings->office_name }}</div>
                <div class="company-details">
                    @if ($settings->office_address ?? false)
                        <span class="c-label">العنوان</span>
                        <span class="c-value">{{ $settings->office_address }}</span>
                    @endif
                    @if ($settings->phone_number ?? false)
                        <span class="c-label">الجوال</span>
                        <span class="c-value">{{ $settings->phone_number }}</span>
                    @endif
                    @if ($settings->commercial_register ?? false)
                        <span class="c-label">س.ت</span>
                        <span class="c-value">{{ $settings->commercial_register }}</span>
                    @endif
                    @if ($settings->email ?? false)
                        <span class="c-label">البريد الالكتروني</span>
                        <span class="c-value">{{ $settings->email }}</span>
                    @endif
                </div>
            </div>

            <div>
                <div class="inv-title">فاتورة</div>
                <div class="d-val" style="font-size:11.5px ;margin:5px">{{ $bill->bill_number }}</div>

                @php
                    $sc = [
                        'paid' => 'background:#dcfce7; color:#166534',
                        'pending' => 'background:#fef9c3; color:#854d0e',
                        'pending_verification' => 'background:#dbeafe; color:#1e40af',
                        'cancelled' => 'background:#fee2e2; color:#991b1b',
                        'rejected' => 'background:#fee2e2; color:#991b1b',
                    ];
                    $sv = $sc[$bill->status->value] ?? 'background:#f1f5f9; color:#475569';
                @endphp
                <span class="inv-status" style="{{ $sv }}">{{ $bill->status->label() }}</span>
            </div>

        </div>

        {{-- ── Info section ── --}}
        <div class="info-section">
            <div class="info-cell">
                <div class="info-cell-label">بيانات العميل</div>
                <address>
                    <strong>{{ $bill->tenant?->company_name ?? ($bill->metadata['billing_company_name'] ?? 'غير محدد') }}</strong>

                    @if ($cn = $bill->tenant?->commercial_number ?? ($bill->metadata['billing_commercial_number'] ?? null))
                        س.ت: {{ $cn }}<br>
                    @endif
                    @if (!empty($bill->metadata['billing_name']))
                        {{ $bill->metadata['billing_name'] }}<br>
                    @endif
                    @if (!empty($bill->metadata['billing_phone']))
                        الجوال: {{ $bill->metadata['billing_phone'] }}<br>
                    @endif
                    @if (!empty($bill->metadata['billing_email']))
                        {{ $bill->metadata['billing_email'] }}
                    @endif
                </address>
            </div>
            <div class="info-cell">
                <div class="info-cell-label">تفاصيل الفاتورة</div>
                <div class="inv-details">
                    <span class="d-lbl">رقم الفاتورة</span>
                    <span class="d-val" style="font-size:11.5px">{{ $bill->bill_number }}</span>

                    <span class="d-lbl">تاريخ الإصدار</span>
                    <span class="d-val">{{ $bill->created_at->format('Y/m/d') }}</span>

                    <span class="d-lbl">نوع الفاتورة</span>
                    <span class="d-val">{{ $bill->type->label() }}</span>

                    @if ($bill->payment_method)
                        <span class="d-lbl">طريقة الدفع</span>
                        <span class="d-val">{{ $bill->payment_method->label() }}</span>
                    @endif

                    @if ($bill->paid_at)
                        <span class="d-lbl">تاريخ الدفع</span>
                        <span class="d-val green">{{ $bill->paid_at }}</span>
                    @endif

                    @if ($bill->bank)
                        <span class="d-lbl">البنك</span>
                        <span class="d-val">{{ $bill->bank->bank_name }}</span>
                    @endif
                </div>
            </div>
        </div>

        {{-- ── Coupon ── --}}
        @if ($bill->coupon_code)
            <div class="coupon-bar">
                <span>كوبون الخصم مطبّق</span>
                <span class="badge">{{ $bill->coupon_code }}</span>
            </div>
        @endif

        {{-- ── Table ── --}}
        <table class="inv-table">
            <thead>
                <tr>
                    <th>البند</th>
                    <th class="text-start" style="width:210px">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $bill->type->label() }}</td>
                    <td class="text-start fw-semibold">{{ number_format($bill->amount, 2) }} ر.س</td>
                </tr>
                <tr class="sub-row">
                    <td>المجموع قبل الضريبة</td>
                    <td class="text-start">{{ number_format($priceWithoutTax, 2) }} ر.س</td>
                </tr>
                <tr class="sub-row">
                    <td>ضريبة القيمة المضافة ({{ (int) ($vatRate * 100) }}%)</td>
                    <td class="text-start">{{ number_format($vatAmount, 2) }} ر.س</td>
                </tr>
                @if ($discount > 0)
                    <tr class="discount-row">
                        <td>الخصم @if ($bill->coupon_code)
                                ({{ $bill->coupon_code }})
                            @endif
                        </td>
                        <td class="text-start">- {{ number_format($discount, 2) }} ر.س</td>
                    </tr>
                @endif
                @if ($bill->settlement_amount && $bill->settlement_amount != 0)
                    <tr class="sub-row">
                        <td>التسوية</td>
                        <td class="text-start">{{ number_format($bill->settlement_amount, 2) }} ر.س</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>الإجمالي</td>
                    <td class="text-start">{{ number_format($bill->amount, 2) }} ر.س</td>
                </tr>
            </tbody>
        </table>

        {{-- ── Action buttons ── --}}
        <div class="action-row no-print">
            <button onclick="window.print()" class="btn-brand">طباعة الفاتورة</button>
            <a href="{{ route('admin.bills.show', $bill->id) }}" class="btn-outline">رجوع</a>
        </div>

    </div>
</body>

</html>
