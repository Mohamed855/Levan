@extends('layouts.dashboard')

@section('title', 'تفاصيل الفاتورة #' . $bill->bill_number)
@section('dashboard-scope', 'tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('tenant.bills.index') }}">الفواتير</a></li>
                <li class="breadcrumb-item active">فاتورة #{{ $bill->bill_number }}</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">فاتورة رقم <span class="text-primary font-monospace">#{{ $bill->bill_number }}</span></h4>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('tenant.bills.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
        </a>
        <button type="button" class="btn btn-label-primary" onclick="window.print()">
            <i class="ti ti-printer me-1"></i> طباعة الفاتورة
        </button>
    </div>
</div>

<div class="row">
    {{-- العمود الأيمن: تفاصيل الفاتورة الرسمية والبنود --}}
    <div class="col-xl-8 col-lg-7 col-md-7 mb-4">
        <div class="card invoice-preview-card">
            <div class="card-body">
                <div class="d-flex justify-content-between flex-xl-row flex-md-column flex-sm-row flex-column m-sm-3 m-0">
                    <div class="mb-xl-0 mb-4">
                        <div class="d-flex svg-illustration mb-4 gap-2 align-items-center">
                            <span class="app-brand-text fw-bold fs-4 text-primary">لوت LOT</span>
                        </div>
                        <p class="mb-2">{{ $settings?->company_name ?? 'منصة لوت للمزادات' }}</p>
                        <p class="mb-2">{{ $settings?->company_address ?? 'المملكة العربية السعودية' }}</p>
                        <p class="mb-0">{{ $settings?->company_phone ?? '' }}</p>
                    </div>
                    <div>
                        <h4 class="fw-bold text-md-end mb-2">فاتورة ضريبية</h4>
                        <div class="mb-2 text-md-end">
                            <span class="text-muted">رقم الفاتورة:</span>
                            <span class="fw-semibold font-monospace">#{{ $bill->bill_number }}</span>
                        </div>
                        <div class="mb-2 text-md-end">
                            <span class="text-muted">تاريخ الإصدار:</span>
                            <span class="fw-semibold">{{ $bill->created_at?->format('Y-m-d') }}</span>
                        </div>
                        @if($bill->due_at)
                            <div class="text-md-end">
                                <span class="text-muted">تاريخ الاستحقاق:</span>
                                <span class="fw-semibold {{ $bill->due_at->isPast() && $bill->status->value !== 'paid' ? 'text-danger' : '' }}">
                                    {{ $bill->due_at->format('Y-m-d') }}
                                </span>
                            </div>
                        @endif
                    </div>
                </div>

                <hr class="my-4" />

                <div class="row p-sm-3 p-0">
                    <div class="col-xl-6 col-md-12 col-sm-5 col-12 mb-xl-0 mb-md-4 mb-sm-0 mb-4">
                        <h6 class="pb-2 fw-bold">فاتورة موجهة إلى:</h6>
                        <p class="mb-1 fw-semibold">{{ $bill->tenant?->company_name ?? tenant('company_name') }}</p>
                        <p class="mb-1">{{ $bill->tenant?->company_address ?? '' }}</p>
                        <p class="mb-1">السجل التجاري: {{ $bill->tenant?->commercial_number ?? 'غير مسجل' }}</p>
                        <p class="mb-0">البريد الإلكتروني: {{ $bill->tenant?->email ?? tenant('email') }}</p>
                    </div>
                    <div class="col-xl-6 col-md-12 col-sm-7 col-12">
                        <h6 class="pb-2 fw-bold">بيانات السداد:</h6>
                        <table>
                            <tbody>
                                <tr>
                                    <td class="pe-3 text-muted">طريقة الدفع:</td>
                                    <td>
                                        @if($bill->payment_method)
                                            <span class="badge bg-label-info">{{ $bill->payment_method->label() }}</span>
                                        @else
                                            <span class="text-muted">لم تُحدد بعد</span>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td class="pe-3 text-muted">حالة الفاتورة:</td>
                                    <td>
                                        <span class="badge bg-{{ $bill->status->color() }}">{{ $bill->status->label() }}</span>
                                    </td>
                                </tr>
                                @if($bill->paid_at)
                                    <tr>
                                        <td class="pe-3 text-muted">تاريخ السداد:</td>
                                        <td class="fw-semibold text-success">{{ $bill->paid_at->format('Y-m-d H:i') }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="table-responsive border-top my-4">
                    <table class="table m-0">
                        <thead>
                            <tr>
                                <th>الوصف</th>
                                <th class="text-center">النوع</th>
                                <th class="text-center">السعر قبل الضريبة</th>
                                <th class="text-center">ضريبة القيمة المضافة (15%)</th>
                                <th class="text-end">الإجمالي (شامل الضريبة)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="text-nowrap fw-semibold">
                                    {{ $bill->type?->label() ?? 'خدمة اشتراك' }}
                                    <div class="text-muted small">اشتراك نظام منصة لوت لإدارة المزادات</div>
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-label-primary">{{ $bill->type?->label() ?? 'اشتراك' }}</span>
                                </td>
                                <td class="text-center">{{ number_format($priceWithoutTax, 2) }} ر.س</td>
                                <td class="text-center">{{ number_format($vatAmount, 2) }} ر.س</td>
                                <td class="text-end fw-bold text-primary">{{ number_format((float) $bill->amount, 2) }} ر.س</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="card-body mx-3 p-0">
                    <div class="row">
                        <div class="col-12 col-md-6 mb-md-0 mb-3">
                            <div class="alert alert-light border">
                                <h6 class="alert-heading fw-bold mb-1"><i class="ti ti-info-circle me-1"></i> ملاحظات الدفع:</h6>
                                <p class="mb-0 small text-muted">جميع الأسعار تشمل ضريبة القيمة المضافة (15%). في حال التحويل البنكي يرجى إرفاق صورة الإشعار مع الإشارة لرقم الفاتورة.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6 d-flex justify-content-end">
                            <div class="invoice-calculations">
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-150 text-muted">المجموع قبل الضريبة:</span>
                                    <span class="fw-semibold">{{ number_format($priceWithoutTax, 2) }} ر.س</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="w-px-150 text-muted">ضريبة القيمة المضافة:</span>
                                    <span class="fw-semibold">{{ number_format($vatAmount, 2) }} ر.س</span>
                                </div>
                                <hr />
                                <div class="d-flex justify-content-between">
                                    <span class="w-px-150 fw-bold fs-5">المبلغ المطلوب:</span>
                                    <span class="fw-bold fs-5 text-primary">{{ number_format((float) $bill->amount, 2) }} ر.س</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- العمود الأيسر: بطاقة الإجراءات والملف التعريفي للفاتورة --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3 fw-bold">حالة السداد</h5>
                <div class="text-center py-3">
                    <div class="avatar avatar-xl mx-auto mb-3 bg-label-{{ $bill->status->color() }} rounded-circle d-flex align-items-center justify-content-center">
                        <i class="ti ti-receipt fs-1 text-{{ $bill->status->color() }}"></i>
                    </div>
                    <h5 class="fw-bold mb-1">{{ number_format((float) $bill->amount, 2) }} ر.س</h5>
                    <span class="badge bg-{{ $bill->status->color() }} fs-6">{{ $bill->status->label() }}</span>
                </div>

                <div class="border-top pt-3 mt-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">رقم الفاتورة:</span>
                        <span class="fw-bold font-monospace">#{{ $bill->bill_number }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">تاريخ الإصدار:</span>
                        <span>{{ $bill->created_at?->format('Y-m-d') }}</span>
                    </div>
                    @if($bill->due_at)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">الاستحقاق:</span>
                        <span>{{ $bill->due_at->format('Y-m-d') }}</span>
                    </div>
                    @endif
                    @if($bill->settled_amount)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">المبلغ المسوّى:</span>
                        <span class="text-success fw-bold">{{ number_format((float) $bill->settled_amount, 2) }} ر.س</span>
                    </div>
                    @endif
                </div>

                @if($bill->status->value === 'unpaid')
                    <div class="d-grid gap-2 mt-4">
                        <a href="{{ route('tenant.bills.bank-transfer', $bill->id) }}" class="btn btn-primary">
                            <i class="ti ti-building-bank me-1"></i> السداد عبر التحويل البنكي
                        </a>
                    </div>
                @elseif($bill->status->value === 'pending_verification')
                    <div class="alert alert-info mt-4 mb-0 py-2">
                        <i class="ti ti-clock-check me-1"></i> إشعار السداد قيد المراجعة والتدقيق من قبل الإدارة.
                    </div>
                @endif
            </div>
        </div>

        @if(isset($bill->metadata['receipt_path']))
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0">إشعار التحويل البنكي المرفق</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between p-2 border rounded">
                        <div class="d-flex align-items-center gap-2">
                            <i class="ti ti-file-text fs-2 text-primary"></i>
                            <div>
                                <span class="fw-semibold d-block">إشعار السداد</span>
                                <small class="text-muted">{{ $bill->metadata['submitted_at'] ?? 'مرفق' }}</small>
                            </div>
                        </div>
                        <a href="{{ asset('storage/' . $bill->metadata['receipt_path']) }}" target="_blank" class="btn btn-sm btn-label-primary">
                            <i class="ti ti-eye"></i> معاينة
                        </a>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
