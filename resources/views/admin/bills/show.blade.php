@extends('layouts.dashboard')
@section('title', 'تفاصيل الفاتورة — ' . $bill->bill_number)
@section('dashboard-scope', 'admin')

@section('content')

@php
    $statusColors = [
        'paid'      => 'success',
        'pending'   => 'warning',
        'overdue'   => 'danger',
        'cancelled' => 'secondary',
        'refunded'  => 'dark',
    ];
    $statusLabels = [
        'paid'      => 'مدفوعة',
        'pending'   => 'قيد المعالجة',
        'overdue'   => 'متأخرة',
        'cancelled' => 'ملغاة',
        'refunded'  => 'مسترجعة',
    ];
    $typeLabels = [
        'subscription' => 'اشتراك',
        'renewal'      => 'تجديد',
        'upgrade'      => 'ترقية',
    ];
    $paymentLabels = [
        'bank_transfer' => 'تحويل بنكي',
        'credit_card'   => 'بطاقة ائتمان',
        'mada'          => 'مدى',
        'cash'          => 'نقدي',
    ];
@endphp

<div class="row g-4">
    {{-- Left Column: Summary & Actions --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        {{-- Profile Card --}}
        <div class="card mb-4">
            <div class="card-body text-center pt-5 pb-4">
                <div class="avatar avatar-xl mb-3">
                    <span class="avatar-initial rounded-circle bg-label-primary" style="font-size:1.5rem;">
                        <i class="ti ti-receipt"></i>
                    </span>
                </div>
                <h5 class="mb-1 font-monospace">{{ $bill->bill_number }}</h5>
                <p class="text-muted mb-3">{{ $bill->tenant?->company_name ?? 'بدون عميل محدد' }}</p>

                <span class="badge bg-label-{{ $statusColors[$bill->status] ?? 'primary' }} fs-6 px-3 py-2">
                    {{ $statusLabels[$bill->status] ?? $bill->status }}
                </span>
            </div>
            <div class="card-body border-top">
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">نوع الفاتورة</span>
                        <span class="fw-semibold">{{ $typeLabels[$bill->type] ?? $bill->type }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">المبلغ الإجمالي</span>
                        <span class="fw-semibold text-primary">{{ number_format((float) $bill->amount, 2) }} ر.س</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">المبلغ المحصل</span>
                        <span class="fw-semibold {{ $bill->settled_amount ? 'text-success' : 'text-muted' }}">
                            {{ $bill->settled_amount ? number_format((float) $bill->settled_amount, 2) . ' ر.س' : '—' }}
                        </span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">طريقة الدفع</span>
                        <span class="fw-semibold">{{ $paymentLabels[$bill->payment_method] ?? ($bill->payment_method ?: '—') }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">تاريخ الإصدار</span>
                        <span class="fw-semibold">{{ $bill->created_at?->format('Y-m-d H:i') ?? '—' }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">تاريخ الاستحقاق</span>
                        <span class="fw-semibold">{{ $bill->due_at?->format('Y-m-d') ?? '—' }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2">
                        <span class="text-muted">تاريخ السداد</span>
                        <span class="fw-semibold {{ $bill->paid_at ? 'text-success' : 'text-muted' }}">
                            {{ $bill->paid_at?->format('Y-m-d H:i') ?? '—' }}
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        {{-- Update Status Card --}}
        @can('bills.update')
            @if(count($allowedTransitions) > 0)
                <div class="card mb-4">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="ti ti-exchange me-1"></i> تغيير حالة الفاتورة</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.bills.status', $bill) }}">
                            @csrf
                            @method('PATCH')

                            <div class="mb-3">
                                <label for="status" class="form-label">الحالة الجديدة <span class="text-danger">*</span></label>
                                <select name="status" id="status" class="form-select @error('status') is-invalid @enderror" required>
                                    <option value="">— اختر الحالة —</option>
                                    @foreach($allowedTransitions as $s)
                                        <option value="{{ $s }}" @selected(old('status') === $s)>{{ $statusLabels[$s] ?? $s }}</option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="settled_amount" class="form-label">المبلغ المحصل (ر.س)</label>
                                <input type="number" step="0.01" min="0" name="settled_amount" id="settled_amount"
                                       class="form-control @error('settled_amount') is-invalid @enderror"
                                       value="{{ old('settled_amount', $bill->settled_amount ?? $bill->amount) }}"
                                       placeholder="المبلغ الفعلي المستلم">
                                @error('settled_amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label">طريقة الدفع</label>
                                <select name="payment_method" id="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                                    <option value="">— احتفظ بالطريقة الحالية —</option>
                                    @foreach(['bank_transfer' => 'تحويل بنكي', 'credit_card' => 'بطاقة ائتمان', 'mada' => 'مدى', 'cash' => 'نقدي'] as $val => $label)
                                        <option value="{{ $val }}" @selected(old('payment_method', $bill->payment_method) === $val)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('payment_method')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-3">
                                <label for="notes" class="form-label">ملاحظات العملية</label>
                                <textarea name="notes" id="notes" rows="3"
                                          class="form-control @error('notes') is-invalid @enderror"
                                          placeholder="سبب التغيير أو مرجع الحوالة">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ti ti-check me-1"></i> حفظ وتحديث الحالة
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan

        <a href="{{ route('admin.bills.index') }}" class="btn btn-label-secondary w-100">
            <i class="ti ti-arrow-right me-1"></i> العودة لقائمة الفواتير
        </a>
    </div>

    {{-- Right Column: Detailed Breakdown & Tenant Info & Audit Trail --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        {{-- Tenant Summary Card --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">بيانات العميل</h5>
                @if($bill->tenant)
                    <a href="{{ route('admin.tenants.show', $bill->tenant) }}" class="btn btn-sm btn-label-primary">
                        <i class="ti ti-external-link me-1"></i> ملف العميل
                    </a>
                @endif
            </div>
            <div class="card-body">
                @if($bill->tenant)
                    <div class="row g-3">
                        <div class="col-md-6">
                            <span class="text-muted d-block small">اسم الشركة</span>
                            <span class="fw-semibold fs-6">{{ $bill->tenant->company_name }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">السجل التجاري</span>
                            <span class="fw-semibold">{{ $bill->tenant->commercial_number ?? '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">الرقم الضريبي</span>
                            <span class="fw-semibold">{{ $bill->tenant->tax_number ?? '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">البريد الإلكتروني الرئيسي</span>
                            <span class="fw-semibold">{{ $bill->tenant->primary_contact_email ?? '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">رقم جوال المسؤول</span>
                            <span class="fw-semibold">{{ $bill->tenant->primary_contact_phone ?? '—' }}</span>
                        </div>
                        <div class="col-md-6">
                            <span class="text-muted d-block small">العنوان</span>
                            <span class="fw-semibold">{{ $bill->tenant->address ?? '—' }}</span>
                        </div>
                    </div>
                @else
                    <div class="text-center py-3 text-muted">
                        <i class="ti ti-user-x fs-1 d-block mb-1"></i>
                        لا توجد بيانات عميل مرتبطة بهذه الفاتورة مباشرة.
                    </div>
                @endif
            </div>
        </div>

        {{-- Financial Breakdown Card --}}
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">تفاصيل المبالغ والبيانات الإضافية</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>البند</th>
                            <th>التفاصيل</th>
                            <th class="text-end">القيمة</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <span class="fw-semibold">رسوم {{ $typeLabels[$bill->type] ?? $bill->type }}</span>
                            </td>
                            <td>
                                <span class="text-muted">فاتورة رقم {{ $bill->bill_number }}</span>
                            </td>
                            <td class="text-end fw-semibold">
                                {{ number_format((float) $bill->amount, 2) }} ر.س
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <td colspan="2" class="fw-bold">الإجمالي النهائي</td>
                            <td class="text-end fw-bold text-primary fs-6">
                                {{ number_format((float) $bill->amount, 2) }} ر.س
                            </td>
                        </tr>
                        @if($bill->settled_amount !== null)
                            <tr>
                                <td colspan="2" class="fw-bold text-success">المبلغ المسدد فعلياً</td>
                                <td class="text-end fw-bold text-success fs-6">
                                    {{ number_format((float) $bill->settled_amount, 2) }} ر.س
                                </td>
                            </tr>
                        @endif
                    </tfoot>
                </table>
            </div>
            @if(!empty($bill->metadata) && is_array($bill->metadata))
                <div class="card-body border-top">
                    <h6 class="fw-semibold mb-2">بيانات إضافية (Metadata)</h6>
                    <pre class="bg-light p-3 rounded mb-0 text-start" dir="ltr" style="font-size: 0.85rem;">{{ json_encode($bill->metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            @endif
        </div>

        {{-- Status Change History / Audit Trail Card --}}
        @php
            $history = $bill->metadata['history'] ?? [];
        @endphp
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0"><i class="ti ti-history me-1"></i> سجل تغييرات الحالة</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>من</th>
                                <th>إلى</th>
                                <th>بواسطة</th>
                                <th>الملاحظات</th>
                                <th>التاريخ</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($history as $item)
                                <tr>
                                    <td>
                                        <span class="badge bg-label-secondary">
                                            {{ $statusLabels[$item['from_status']] ?? $item['from_status'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-{{ $statusColors[$item['to_status']] ?? 'primary' }}">
                                            {{ $statusLabels[$item['to_status']] ?? $item['to_status'] }}
                                        </span>
                                    </td>
                                    <td>{{ $item['admin_name'] ?? ('مشرف #' . ($item['admin_id'] ?? '—')) }}</td>
                                    <td>{{ $item['notes'] ?? '—' }}</td>
                                    <td class="text-muted small">
                                        {{ !empty($item['changed_at']) ? \Carbon\Carbon::parse($item['changed_at'])->format('Y-m-d H:i') : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-3 text-muted">لا يوجد سجل تغييرات سابقة لهذه الفاتورة.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
