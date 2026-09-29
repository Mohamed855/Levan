@extends('admin.layouts.print')

@section('title', 'فاتورة #' . $bill->bill_number)

@section('content')

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
                <div class="d-val" style="font-size:11.5px; margin:5px">{{ $bill->bill_number }}</div>

                @php
                    $statusColors = [
                        'paid'                 => 'background:#dcfce7; color:#166534',
                        'pending'              => 'background:#fef9c3; color:#854d0e',
                        'pending_verification' => 'background:#dbeafe; color:#1e40af',
                        'cancelled'            => 'background:#fee2e2; color:#991b1b',
                        'rejected'             => 'background:#fee2e2; color:#991b1b',
                    ];
                    $statusStyle = $statusColors[$bill->status->value] ?? 'background:#f1f5f9; color:#475569';
                @endphp
                <span class="inv-status" style="{{ $statusStyle }}">{{ $bill->status->label() }}</span>
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
                    <th style="width:210px; text-align:center">المبلغ</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $bill->type->label() }}</td>
                    <td class="fw-semibold" style="text-align:center">{{ number_format($bill->amount, 2) }} ر.س</td>
                </tr>
                <tr class="sub-row">
                    <td>المجموع قبل الضريبة</td>
                    <td style="text-align:center">{{ number_format($priceWithoutTax, 2) }} ر.س</td>
                </tr>
                <tr class="sub-row">
                    <td>ضريبة القيمة المضافة ({{ (int) ($vatRate * 100) }}%)</td>
                    <td style="text-align:center">{{ number_format($vatAmount, 2) }} ر.س</td>
                </tr>
                @if ($discount > 0)
                    <tr class="discount-row">
                        <td>الخصم @if ($bill->coupon_code)({{ $bill->coupon_code }})@endif</td>
                        <td style="text-align:center">- {{ number_format($discount, 2) }} ر.س</td>
                    </tr>
                @endif
                @if ($bill->settlement_amount && $bill->settlement_amount != 0)
                    <tr class="sub-row">
                        <td>التسوية</td>
                        <td style="text-align:center">{{ number_format($bill->settlement_amount, 2) }} ر.س</td>
                    </tr>
                @endif
                <tr class="total-row">
                    <td>الإجمالي</td>
                    <td style="text-align:center">{{ number_format($bill->amount, 2) }} ر.س</td>
                </tr>
            </tbody>
        </table>

        {{-- ── Action buttons (hidden on print) ── --}}
        <div class="inv-actions no-print">
            <button type="button" onclick="window.print()" class="btn-inv-primary">طباعة الفاتورة</button>
            <a href="{{ route('admin.bills.show', $bill->id) }}" class="btn-inv-outline">رجوع</a>
        </div>

    </div>

@endsection
