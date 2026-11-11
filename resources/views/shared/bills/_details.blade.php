@php
    $isAdmin = $isAdmin ?? false;
    $metadata = $bill->metadata ?? [];
@endphp

<div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $backUrl }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة
        </a>
        @if ($isAdmin)
            <a href="{{ route('admin.bills.print', $bill->id) }}" class="btn btn-primary">
                <i class="ti ti-printer me-1"></i> طباعة الفاتورة
            </a>
        @elseif ($bill->status->value === 'unpaid')
            <a href="{{ tenant_route_name('bills.checkout', $bill->id) }}" class="btn btn-primary">
                <i class="ti ti-credit-card-pay me-1"></i> دفع إلكتروني
            </a>
        @endif
    </div>
</div>

<div class="row">
    {{-- بطاقة ملخص الفاتورة ومعلوماتها (العمود الأيمن) --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mx-auto mb-3">
                    <div class="avatar avatar-xl bg-label-primary rounded-circle p-3 d-flex align-items-center justify-content-center mx-auto shadow-sm"
                        style="width: 100px; height: 100px;">
                        <i class="ti ti-file-invoice fs-1 text-primary"></i>
                    </div>
                </div>

                <h4 class="mb-1 fw-bold text-primary">#{{ $bill->bill_number }}</h4>
                <p class="mb-2 text-muted fw-medium">{{ $bill->type->label() }}</p>
                <span class="badge bg-label-{{ $bill->status->color() }} mb-3 fs-6">
                    {{ $bill->status->label() }}
                </span>

                <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                    <div>
                        <h5 class="mb-0 text-primary">
                            {{ number_format($bill->amount, 2) }}
                            <span class="icon-saudi_riyal ms-1"></span>
                        </h5>
                        <small class="text-muted">الإجمالي</small>
                    </div>
                    <div>
                        <h5 class="mb-0">@date($bill->created_at)</h5>
                        <small class="text-muted">تاريخ الإصدار</small>
                    </div>
                </div>

                <div class="info-container text-start">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-file-invoice text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">رقم الفاتورة:</span>
                            <span>{{ $bill->bill_number }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-category text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">النوع:</span>
                            <span class="badge bg-label-info">{{ $bill->type->label() }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-status-change text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الحالة:</span>
                            <span class="badge bg-label-{{ $bill->status->color() }}">{{ $bill->status->label() }}</span>
                        </li>

                        @if ($bill->payment_method)
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-credit-card text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">طريقة الدفع:</span>
                                <span class="badge bg-label-dark">{{ $bill->payment_method->label() }}</span>
                            </li>
                        @endif

                        @if (!$isAdmin && $bill->paid_at)
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-check text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ الدفع:</span>
                                <span class="fw-semibold text-success">@date($bill->paid_at)</span>
                            </li>
                        @endif

                        @if ($isAdmin && $bill->verified_by)
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-user-check text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تحقق بواسطة:</span>
                                <span>{{ $bill->verifiedBy?->name ?? 'غير محدد' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-check text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ التحقق:</span>
                                <span>@datetime($bill->verified_at)</span>
                            </li>
                        @endif

                        @if ($isAdmin && isset($metadata['new_licenses_count']))
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-users text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المستخدمون الإضافيون:</span>
                                <span class="badge bg-label-info">{{ $metadata['new_licenses_count'] }} مستخدم</span>
                            </li>
                        @endif

                        @if ($isAdmin && isset($metadata['license_unit_price']))
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-user-dollar text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">سعر المستخدم:</span>
                                <span class="badge bg-label-info">
                                    {{ number_format($metadata['license_unit_price'], 2) }}
                                    <span class="icon-saudi_riyal ms-1"></span>
                                </span>
                            </li>
                        @endif

                        @if ($bill->bank && $bill->status->value === 'pending_verification')
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-building-bank text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">البنك:</span>
                                <span class="badge bg-label-secondary">{{ $bill->bank->bank_name }}</span>
                            </li>
                        @endif

                        @if (!$isAdmin && $bill->status->value === 'pending_verification' && $bill->transfer_date)
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-event text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ التحويل:</span>
                                <span>{{ $bill->transfer_date }}</span>
                            </li>
                        @endif

                        @if ($isAdmin && $bill->invoice_url)
                            <li class="mb-2 d-flex align-items-center justify-content-between">
                                <span class="d-flex align-items-center">
                                    <i class="ti ti-paperclip text-primary me-2 fs-5"></i>
                                    <span class="fw-bold me-2">إيصال التحويل:</span>
                                </span>
                                <a href="{{ asset('storage/' . $bill->invoice_url) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary">عرض</a>
                            </li>
                        @endif
                    </ul>
                </div>

                @if (!$isAdmin && $bill->status->value === 'unpaid')
                    <div class="d-grid gap-2 mt-3 pt-3 border-top">
                        <a href="{{ tenant_route_name('bills.checkout', $bill->id) }}" class="btn btn-primary">
                            <i class="ti ti-credit-card-pay me-1"></i> دفع إلكتروني
                        </a>
                        <a href="{{ tenant_route_name('bills.bank-transfer', $bill->id) }}" class="btn btn-secondary">
                            <i class="ti ti-building-bank me-1"></i> تحويل بنكي
                        </a>
                    </div>
                @endif
            </div>
        </div>

        {{-- أزرار التحقق (Admin - pending_verification فقط) --}}
        @if ($isAdmin && $bill->status->value === 'pending_verification')
            <div class="card mb-4">
                <div class="card-header pb-2 mb-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-shield-check text-primary me-2"></i>مراجعة الإيصال
                    </h6>
                </div>
                <div class="card-body">
                    <p class="text-muted small mb-3">
                        <i class="ti ti-info-circle me-1"></i>
                        يرجى مراجعة الإيصال قبل التأكيد أو الرفض.
                    </p>
                    <div class="d-flex gap-2">
                        <form id="verifyBillForm" action="{{ route('admin.bills.verify', $bill->id) }}" method="POST"
                            class="m-0 flex-fill">
                            @csrf
                            <button type="button" class="btn btn-success btn-sm w-100 js-verify-bill-btn">
                                <i class="ti ti-check me-1"></i> تأكيد
                            </button>
                        </form>
                        <button type="button" class="btn btn-danger btn-sm flex-fill" data-bs-toggle="modal"
                            data-bs-target="#rejectModal">
                            <i class="ti ti-x me-1"></i> رفض
                        </button>
                    </div>
                </div>
            </div>
        @endif

        {{-- ملاحظات التحويل (Tenant - pending_verification) --}}
        @if (!$isAdmin && $bill->status->value === 'pending_verification' && $bill->transfer_notes)
            <div class="card mb-4">
                <div class="card-header pb-2 mb-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-note text-primary me-2"></i>ملاحظات التحويل
                    </h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info mb-0 py-2">
                        <p class="mb-0 small">{{ $bill->transfer_notes }}</p>
                    </div>
                </div>
            </div>
        @endif

        {{-- بطاقة بيانات العميل --}}
        <div class="card mb-4">
            <div class="card-header pb-2 mb-3">
                <h6 class="card-title mb-0">
                    <i class="ti ti-user text-primary me-2"></i>بيانات العميل
                </h6>
            </div>
            <div class="card-body">
                @if ($isAdmin)
                    @php
                        $hasBillingClient =
                            !empty($metadata['billing_name']) ||
                            !empty($metadata['billing_email']) ||
                            !empty($metadata['billing_phone']) ||
                            !empty($metadata['billing_company_name']) ||
                            !empty($metadata['billing_commercial_number']);
                    @endphp
                    @if ($hasBillingClient)
                        <div class="info-container text-start">
                            <ul class="list-unstyled mb-0">
                                @if (!empty($metadata['billing_name']))
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="ti ti-user text-primary me-2 fs-5"></i>
                                        <span class="fw-bold me-2">الاسم:</span>
                                        <span>{{ $metadata['billing_name'] }}</span>
                                    </li>
                                @endif
                                @if (!empty($metadata['billing_email']))
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="ti ti-mail text-primary me-2 fs-5"></i>
                                        <span class="fw-bold me-2">البريد:</span>
                                        <span class="text-break">{{ $metadata['billing_email'] }}</span>
                                    </li>
                                @endif
                                @if (!empty($metadata['billing_phone']))
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="ti ti-phone text-primary me-2 fs-5"></i>
                                        <span class="fw-bold me-2">الجوال:</span>
                                        <span dir="ltr">{{ $metadata['billing_phone'] }}</span>
                                    </li>
                                @endif
                                @if (!empty($metadata['billing_company_name']))
                                    <li class="mb-3 d-flex align-items-center">
                                        <i class="ti ti-building text-primary me-2 fs-5"></i>
                                        <span class="fw-bold me-2">الشركة:</span>
                                        <span>{{ $metadata['billing_company_name'] }}</span>
                                    </li>
                                @endif
                                @if (!empty($metadata['billing_commercial_number']))
                                    <li class="mb-2 d-flex align-items-center">
                                        <i class="ti ti-id-badge text-primary me-2 fs-5"></i>
                                        <span class="fw-bold me-2">السجل التجاري:</span>
                                        <span>{{ $metadata['billing_commercial_number'] }}</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @else
                        <p class="text-muted my-2 text-center small">لا توجد بيانات</p>
                    @endif
                @else
                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-building text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">اسم الشركة:</span>
                                <span>{{ $bill->tenant?->company_name ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-id-badge text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">السجل التجاري:</span>
                                <span>{{ $bill->tenant?->commercial_number ?: '-' }}</span>
                            </li>
                            <li class="{{ $bill->period ? 'mb-3' : 'mb-2' }} d-flex align-items-center">
                                <i class="ti ti-category text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">نوع العملية:</span>
                                <span>{{ $bill->type->label() }}</span>
                            </li>
                            @if ($bill->period)
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="ti ti-calendar-time text-primary me-2 fs-5"></i>
                                    <span class="fw-bold me-2">الفترة:</span>
                                    <span>من @date($bill->period->period_start) إلى @date($bill->period->period_end)</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- تفاصيل الفاتورة وبنودها والملخص المالي (العمود الأيسر) --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-receipt text-primary me-2"></i>تفاصيل الفاتورة
                </h5>
                <span class="badge bg-label-primary">{{ $settings->office_name }}</span>
            </div>

            <div class="card-body">
                {{-- ترويسة المنشأة --}}
                <div class="d-flex justify-content-between flex-wrap gap-3 p-3 rounded border bg-secondary-subtle mb-3">
                    <div>
                        <div class="fw-bold text-primary">{{ $settings->office_name }}</div>
                        <small class="text-muted d-block">{{ $settings->office_address }}</small>
                    </div>
                    <div class="text-end">
                        <div class="fw-bold">فاتورة #{{ $bill->bill_number }}</div>
                        <small class="text-muted">تاريخ الإصدار: @date($bill->created_at)</small>
                    </div>
                </div>

                @if ($bill->coupon_code)
                    <div class="alert alert-success d-flex justify-content-between align-items-center py-2 mb-3">
                        <span class="fw-semibold">
                            <i class="ti ti-discount-2 me-1"></i> كوبون الخصم مطبّق
                        </span>
                        <span class="badge bg-label-success">{{ $bill->coupon_code }}</span>
                    </div>
                @endif

                @if ($bill->reject_reason)
                    <div class="alert alert-danger mb-3" role="alert">
                        <div class="d-flex align-items-start">
                            <i class="ti ti-alert-circle me-2 mt-1 fs-5"></i>
                            <div>
                                <h6 class="alert-heading mb-1">آخر سبب الرفض</h6>
                                <p class="mb-0 small">{{ $bill->reject_reason }}</p>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- جدول البنود --}}
                <div class="table-responsive mb-3">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>البند</th>
                                <th style="width: 240px;">المجموع (شامل الضريبة)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <div class="fw-semibold">{{ $bill->type->label() }}</div>
                                    @if ($isAdmin && !empty($metadata['new_licenses_count']))
                                        <small class="text-muted d-block">
                                            {{ $metadata['new_licenses_count'] }} مستخدم
                                            × {{ number_format($metadata['license_unit_price'] ?? 0, 2) }} ر.س
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    {{ number_format($bill->amount, 2) }}
                                    <span class="icon-saudi_riyal ms-1"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- ملخص المبالغ --}}
                <div class="border rounded p-3 bg-secondary-subtle">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">المجموع قبل الضريبة</span>
                        <span class="fw-medium">
                            {{ number_format($priceWithoutTax, 2) }}
                            <span class="icon-saudi_riyal ms-1"></span>
                        </span>
                    </div>
                    <div class="d-flex justify-content-between border-top pt-2 mb-2">
                        <span class="text-muted">ضريبة القيمة المضافة ({{ (int) ($vatRate * 100) }}%)</span>
                        <span class="fw-medium">
                            {{ number_format($vatAmount, 2) }}
                            <span class="icon-saudi_riyal ms-1"></span>
                        </span>
                    </div>
                    @if ($discount > 0)
                        <div class="d-flex justify-content-between border-top pt-2 mb-2">
                            <span class="text-muted">الخصم</span>
                            <span class="fw-medium text-success">
                                - {{ number_format($discount, 2) }}
                                <span class="icon-saudi_riyal ms-1"></span>
                            </span>
                        </div>
                    @endif
                    @if ($bill->settlement_amount && $bill->settlement_amount != 0)
                        <div class="d-flex justify-content-between border-top pt-2 mb-2">
                            <span class="text-muted">التسوية</span>
                            <span class="fw-medium text-warning">
                                {{ number_format($bill->settlement_amount, 2) }}
                                <span class="icon-saudi_riyal ms-1"></span>
                            </span>
                        </div>
                    @endif
                    <div class="d-flex justify-content-between border-top pt-2">
                        <span class="fw-bold text-primary fs-5">الإجمالي</span>
                        <span class="fw-bold text-primary fs-5">
                            {{ number_format($bill->amount, 2) }}
                            <span class="icon-saudi_riyal ms-1"></span>
                        </span>
                    </div>
                </div>

                <p class="text-muted text-center small mt-3 mb-0">
                    شكرًا لاستخدامكم <strong>{{ $settings->office_name }}</strong>. نتطلع لخدمتكم دائمًا.
                </p>
            </div>
        </div>
    </div>
</div>

@if ($isAdmin && $bill->status->value === 'pending_verification')
    <x-bootstrap.modal id="rejectModal" title="تأكيد الرفض" icon="ti-ban">
        <form action="{{ route('admin.bills.reject', $bill->id) }}" method="POST">
            @csrf
            <div class="mb-3">
                <label for="reject_reason" class="form-label">سبب الرفض <span class="text-danger">*</span></label>
                <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3"
                    placeholder="يرجى كتابة سبب رفض الفاتورة..." required></textarea>
            </div>
            <x-slot:footer>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="ti ti-x me-1"></i>إلغاء
                </button>
                <button type="submit" class="btn btn-danger">
                    <i class="ti ti-ban me-1"></i>تأكيد الرفض
                </button>
            </x-slot:footer>
        </form>
    </x-bootstrap.modal>
@endif

