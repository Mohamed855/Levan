@extends('tenants.layouts.layoutMaster')

@section('title', ' الفاتورة')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/scss/pages/app-invoice.scss'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')

    <div class="row g-2 invoice-preview">
        <div class="col-xl-9 col-md-8">

            <div class="card border-0 shadow-sm">
                {{-- رأس الفاتورة --}}
                <div class="card-body border-bottom bg-light-subtle rounded-top p-4">
                    <div class="d-flex justify-content-between flex-wrap gap-3">
                        <div class="d-flex gap-2 align-items-center">
                            {{-- <div class="app-brand-logo">
                                <x-partials.bill-print :height="22" withbg="" />
                            </div> --}}
                            <div>
                                <div class="fw-bold fs-5">{{ $settings->office_name }}</div>
                                <div class="text-muted small">{{ $settings->office_address }}</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-6">فاتورة #{{ $bill->bill_number }}</div>
                            <div class="text-muted">تاريخ الإصدار: {{ $bill->created_at->format('Y/m/d') }}</div>
                        </div>
                    </div>
                </div>

                {{-- بيانات العميل --}}
                <div class="card-body">
                    <div class="row g-1">
                        <div class="col-md-7">
                            <h6 class="fw-bold mb-3">تفاصيل العميل</h6>
                            <div class="mb-1">
                                <span class="fw-semibold">اسم الشركة:
                                </span>{{ $bill->tenant->company_name }}
                            </div>
                            <div class="mb-1">
                                <span class="fw-semibold">رقم السجل التجاري:
                                </span>{{ $bill->tenant->commercial_number }}
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="border rounded p-3 bg-body-tertiary">
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">نوع العميلة</span>
                                    <span class="fw-medium">{{ $bill->type->label() }}</span>
                                </div>
                                @if ($bill->period)
                                    <div class="d-flex justify-content-between mt-2">
                                        <span class="text-muted">الفترة</span>
                                        <span class="fw-medium">
                                            من {{ $bill->period->period_start->format('Y/m/d') }}
                                            إلى {{ $bill->period->period_end->format('Y/m/d') }}
                                        </span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- جدول البنود --}}
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead style="background: #faf9fa;">
                            <tr>
                                <th>البند</th>
                                <th class="text-end">المجموع (شامل الضريبة)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-heading">{{ $bill->type->label() }}</td>
                                <td class="text-end">
                                    {{ number_format($bill->amount, 2) }} <span class="icon-saudi_riyal ms-1"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- ملخص الفاتورة --}}
                <div class="card-body">
                    @if ($bill->coupon_code)
                        <div class="alert alert-success d-flex justify-content-between align-items-center">
                            <div class="fw-semibold">
                                <i class="ti ti-discount-2 me-1"></i> كوبون الخصم
                            </div>
                            <span class="badge bg-success">{{ $bill->coupon_code }}</span>
                        </div>
                    @endif

                    <div class="row g-1">
                        <div class="col-lg-12">
                            <div class="border rounded p-3 bg-light-subtle h-100">

                                <div class="d-flex justify-content-between mb-2">
                                    <span>المجموع </span>
                                    <span class="fw-medium">
                                        {{ number_format($priceWithoutTax, 2) }}
                                        <span class="icon-saudi_riyal ms-1"></span>
                                    </span>
                                </div>

                                <div class="d-flex justify-content-between border-top pt-2 mb-2">
                                    <span>الضريبة ({{ (int) ($vatRate * 100) }}%)</span>
                                    <span class="fw-medium">
                                        {{ number_format($vatAmount, 2) }}
                                        <span class="icon-saudi_riyal ms-1"></span>
                                    </span>
                                </div>

                                @if ($discount > 0)
                                    <div class="d-flex justify-content-between border-top pt-2 mb-2">
                                        <span>الخصم</span>
                                        <span class="fw-medium text-success">
                                            -{{ number_format($discount, 2) }}
                                            <span class="icon-saudi_riyal ms-1"></span>
                                        </span>
                                    </div>
                                @endif

                                @if ($bill->settlement_amount != 0)
                                    <div class="d-flex justify-content-between border-top pt-2 mb-2">
                                        <span>التسوية </span>
                                        <span class="fw-medium">
                                            {{ number_format($bill->settlement_amount, 2) }}
                                            <span class="icon-saudi_riyal ms-1"></span>
                                        </span>
                                    </div>
                                @endif

                                <div class="d-flex justify-content-between border-top pt-2">
                                    <span class="fw-bold text-primary">الإجمالي</span>
                                    <span class="fw-bold text-primary">
                                        {{ number_format($bill->amount, 2) }}
                                        <span class="icon-saudi_riyal ms-1"></span>
                                    </span>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>

                {{-- ملاحظات ختامية --}}
                <div class="card-body border-top bg-light-subtle rounded-bottom">
                    <p class="text-muted mb-0">
                        شكرًا لاستخدامكم <strong>{{ $settings->office_name }}</strong>. نتطلع لخدمتكم دائمًا.
                    </p>
                </div>

            </div>
        </div>

        {{-- الشريط الجانبي --}}
        <div class="col-xl-3 col-md-4">
            <!-- معلومات الفاتورة -->
            <div class="card border-0 shadow-sm">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-file-description text-primary me-2"></i>
                        معلومات الفاتورة
                    </h6>
                </div>
                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <!-- رقم الفاتورة -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 border-0 py-2">
                            <span class="text-muted">
                                <i class="ti ti-file-invoice me-1"></i>
                                رقم الفاتورة
                            </span>
                            <span class="badge bg-label-primary rounded-pill">{{ $bill->bill_number }}</span>
                        </div>

                        <!-- التاريخ -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">
                                <i class="ti ti-calendar me-1"></i>
                                تاريخ الإصدار
                            </span>
                            <span class="fw-medium">{{ $bill->created_at->format('Y/m/d') }}</span>
                        </div>

                        <!-- النوع -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">
                                <i class="ti ti-category me-1"></i>
                                النوع
                            </span>
                            <span class="badge bg-label-info">{{ $bill->type->label() }}</span>
                        </div>

                        <!-- حالة الفاتورة -->
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted">
                                <i class="ti ti-status-change me-1"></i>
                                حالة الفاتورة
                            </span>
                            <span class="badge bg-label-{{ $bill->status->color() }}">
                                {{ $bill->status->label() }}
                            </span>
                        </div>

                        <!-- طريقة الدفع -->
                        @if ($bill->payment_method)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">
                                    <i class="ti ti-credit-card me-1"></i>
                                    طريقة الدفع
                                </span>
                                <span class="badge bg-label-dark">{{ $bill->payment_method->label() }}</span>
                            </div>
                        @endif

                        <!-- تاريخ الدفع -->
                        @if ($bill->paid_at)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">
                                    <i class="ti ti-calendar-check me-1"></i>
                                    تاريخ الدفع
                                </span>
                                <span class="fw-medium text-success">{{ $bill->paid_at }}</span>
                            </div>
                        @endif
                    </div>

                    <!-- سبب الرفض -->
                    @if ($bill->reject_reason)
                        <div class="alert alert-danger mt-3 mb-0" role="alert">
                            <div class="d-flex align-items-start">
                                <i class="ti ti-alert-circle me-2 mt-1 fs-5"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">اخر سبب الرفض</h6>
                                    <p class="mb-0 small">{{ $bill->reject_reason }}</p>
                                </div>
                            </div>
                        </div>
                    @endif

                    @if ($bill->status->value == 'unpaid')
                        <div class="d-grid gap-2 mt-4">
                            <a href="{{ tenant_route_name('bills.checkout', $bill->id) }}" class="btn btn-primary">
                                <i class="ti ti-credit-card-pay me-1"></i>
                                دفع إلكتروني
                            </a>

                            <a href="{{ tenant_route_name('bills.bank-transfer', $bill->id) }}"
                                class="btn btn-secondary">
                                <i class="ti ti-building-bank me-1"></i>
                                تحويل بنكي
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- تفاصيل الدفع (قيد المراجعة) -->
            @if ($bill->status->value == 'pending_verification')
                <div class="card border-0 shadow-sm mt-3">
                    <div class="card-header py-4">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-receipt text-primary me-2"></i>
                            تفاصيل الدفع
                        </h6>
                    </div>

                    <div class="border-1 border-light border-dashed mb-2"></div>

                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <!-- تاريخ التحويل -->
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">
                                    <i class="ti ti-calendar-event me-1"></i>
                                    تاريخ التحويل
                                </span>
                                <span class="fw-medium">{{ $bill->transfer_date }}</span>
                            </div>

                            <!-- البنك -->
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted">
                                    <i class="ti ti-building-bank me-1"></i>
                                    البنك
                                </span>
                                <span class="badge bg-secondary">{{ $bill->bank->bank_name }}</span>
                            </div>

                            <!-- ملاحظات التحويل -->
                            @if ($bill->transfer_notes)
                                <div class="list-group-item px-0 py-2 border-0">
                                    <small class="text-muted d-block mb-2">
                                        <i class="ti ti-note me-1"></i>
                                        ملاحظات
                                    </small>
                                    <div class="alert alert-info mb-0 py-2">
                                        <p class="mb-0 small">{{ $bill->transfer_notes }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>

                    </div>
                </div>

            @endif
        </div>
    </div>

@endsection
