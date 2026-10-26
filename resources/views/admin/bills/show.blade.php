@extends('admin.layouts.layoutMaster')

@section('title', ' الفاتورة')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/scss/pages/app-invoice.scss'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="row g-2 invoice-preview">

        {{-- ── المحتوى الرئيسي ── --}}
        <div class="col-xl-9 col-md-8">
            <div class="card border-0 shadow-sm">

                {{-- هيدر الفاتورة --}}
                <div class="card-body border-bottom bg-secondary-subtle rounded-top p-4">
                    <div class="d-flex justify-content-between flex-wrap gap-3">
                        <div class="d-flex gap-2 align-items-center">
                            <div>
                                <div class="fw-bold fs-5">{{ $settings->office_name }}</div>
                                <div class="text-muted small">{{ $settings->office_address }}</div>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold fs-6">فاتورة #{{ $bill->bill_number }}</div>
                            <div class="text-muted small">تاريخ الإصدار: {{ $bill->created_at->format('Y/m/d') }}</div>
                            <span class="badge bg-label-{{ $bill->status->color() }} mt-1">
                                {{ $bill->status->label() }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- جدول البنود --}}
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0">
                        <thead style="background: #faf9fa;">
                            <tr>
                                <th>البند</th>
                                <th class="text-end" style="width:220px">المجموع (شامل الضريبة)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    {{ $bill->type->label() }}
                                    {{-- تفاصيل إضافية لشراء التراخيص --}}
                                    @if (!empty($bill->metadata['new_licenses_count']))
                                        <br>
                                        <small class="text-muted">
                                            {{ $bill->metadata['new_licenses_count'] }} ترخيص
                                            × {{ number_format($bill->metadata['license_unit_price'] ?? 0, 2) }} ر.س
                                        </small>
                                    @endif
                                </td>
                                <td class="text-end fw-semibold">
                                    {{ number_format($bill->amount, 2) }}
                                    <span class="icon-saudi_riyal ms-1"></span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                {{-- ملخص المبالغ --}}
                <div class="card-body border-top">
                    @if ($bill->coupon_code)
                        <div class="alert alert-success d-flex justify-content-between align-items-center py-2 mb-3">
                            <span class="fw-semibold">
                                <i class="ti ti-discount-2 me-1"></i> كوبون الخصم مطبّق
                            </span>
                            <span class="badge bg-success">{{ $bill->coupon_code }}</span>
                        </div>
                    @endif

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
                            <span class="fw-bold text-primary fs-6">الإجمالي</span>
                            <span class="fw-bold text-primary fs-6">
                                {{ number_format($bill->amount, 2) }}
                                <span class="icon-saudi_riyal ms-1"></span>
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <a href="{{ route('admin.bills.print', $bill->id) }}" class="btn btn-primary btn-sm">
                            <i class="ti ti-printer me-1"></i> طباعة الفاتورة
                        </a>
                    </div>
                </div>

                {{-- سبب الرفض --}}
                @if ($bill->reject_reason)
                    <div class="card-body border-top">
                        <div class="alert alert-danger mb-0">
                            <div class="d-flex align-items-start">
                                <i class="ti ti-alert-circle me-2 mt-1 fs-5"></i>
                                <div>
                                    <h6 class="alert-heading mb-1">اخر سبب الرفض</h6>
                                    <p class="mb-0 small">{{ $bill->reject_reason }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- فوتر --}}
                <div class="card-body border-top bg-secondary-subtle rounded-bottom text-center">
                    <p class="text-muted mb-0 small">
                        شكرًا لاستخدامكم <strong>{{ $settings->office_name }}</strong>. نتطلع لخدمتكم دائمًا.
                    </p>
                </div>

            </div>
        </div>

        {{-- ── الشريط الجانبي ── --}}
        <div class="col-xl-3 col-md-4">

            {{-- ── بطاقة معلومات الفاتورة ── --}}
            <div class="card border-0 shadow-sm mb-3">
                <div class="card-header py-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-file-description text-primary me-2"></i>
                        معلومات الفاتورة
                    </h6>
                </div>
                <div class="card-body pt-0 pb-2">
                    <div class="list-group list-group-flush">

                        {{-- رقم الفاتورة --}}
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted small"><i class="ti ti-file-invoice me-1"></i>رقم الفاتورة</span>
                            <span class="badge bg-label-primary fw-bold">{{ $bill->bill_number }}</span>
                        </div>

                        {{-- تاريخ الإصدار --}}
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted small"><i class="ti ti-calendar me-1"></i>تاريخ الإصدار</span>
                            <span class="fw-medium small">{{ $bill->created_at->format('Y/m/d') }}</span>
                        </div>

                        {{-- نوع الفاتورة --}}
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted small"><i class="ti ti-category me-1"></i>النوع</span>
                            <span class="badge bg-label-info">{{ $bill->type->label() }}</span>
                        </div>

                        {{-- حالة الفاتورة --}}
                        <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                            <span class="text-muted small"><i class="ti ti-status-change me-1"></i>الحالة</span>
                            <span class="badge bg-label-{{ $bill->status->color() }}">{{ $bill->status->label() }}</span>
                        </div>

                        {{-- طريقة الدفع --}}
                        @if ($bill->payment_method)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted small"><i class="ti ti-credit-card me-1"></i>طريقة الدفع</span>
                                <span class="badge bg-label-dark">{{ $bill->payment_method->label() }}</span>
                            </div>
                        @endif


                        {{-- تم التحقق بواسطة --}}
                        @if ($bill->verified_by)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted small"><i class="ti ti-user-check me-1"></i>تحقق بواسطة</span>
                                <span class="fw-medium small">{{ $bill->verifiedBy?->name ?? 'غير محدد' }}</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted small"><i class="ti ti-calendar-check me-1"></i>تاريخ
                                    التحقق</span>
                                <span class="fw-medium small">{{ $bill->verified_at }}</span>
                            </div>
                        @endif

                        {{-- عدد التراخيص --}}
                        @isset($bill->metadata['new_licenses_count'])
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted small"><i class="ti ti-users me-1"></i>عدد التراخيص</span>
                                <span class="badge bg-label-info">{{ $bill->metadata['new_licenses_count'] }} ترخيص</span>
                            </div>
                        @endisset

                        {{-- سعر الترخيص --}}
                        @isset($bill->metadata['license_unit_price'])
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted small"><i class="ti ti-license me-1"></i>سعر الترخيص</span>
                                <span class="badge bg-label-info">
                                    {{ number_format($bill->metadata['license_unit_price'], 2) }}
                                    <span class="icon-saudi_riyal ms-1"></span>
                                </span>
                            </div>
                        @endisset

                        {{-- الإيصال --}}
                        @if ($bill->invoice_url)
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted small"><i class="ti ti-paperclip me-1"></i>إيصال التحويل</span>
                                <a href="{{ asset('storage/' . $bill->invoice_url) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary">عرض</a>
                            </div>
                        @endif

                        {{-- البنك --}}
                        @if ($bill->bank && $bill->status->value === 'pending_verification')
                            <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                <span class="text-muted small"><i class="ti ti-building-bank me-1"></i>البنك</span>
                                <span class="badge bg-secondary">{{ $bill->bank->bank_name }}</span>
                            </div>
                        @endif

                    </div>
                </div>
            </div>

            {{-- ── أزرار التحقق (pending_verification فقط) ── --}}
            @if ($bill->status->value === 'pending_verification')
                <div class="card border-0 shadow-sm mb-3">
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

                {{-- Modal الرفض --}}
                <x-bootstrap.modal id="rejectModal" title="تأكيد الرفض" icon="ti-ban">
                    <form action="{{ route('admin.bills.reject', $bill->id) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="reject_reason" class="form-label">سبب الرفض <span
                                    class="text-danger">*</span></label>
                            <textarea class="form-control" id="reject_reason" name="reject_reason" rows="3"
                                placeholder="يرجى كتابة سبب رفض الفاتورة..." required></textarea>
                        </div>
                        <x-slot:footer>
                            <button type="button" class="btn btn-label-secondary"
                                data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-danger">تأكيد الرفض</button>
                        </x-slot:footer>
                    </form>
                </x-bootstrap.modal>
            @endif

            {{-- ── بيانات العميل ── --}}
            <div class="card border-0 shadow-sm">
                <div class="card-header py-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-user text-primary me-2"></i>
                        بيانات العميل
                    </h6>
                </div>
                <div class="card-body pt-0 pb-2">
                    @php $metadata = $bill->metadata ?? []; @endphp
                    @if (!empty($metadata))
                        <div class="list-group list-group-flush">

                            @if (!empty($metadata['billing_name']))
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <span class="text-muted small"><i class="ti ti-user me-1"></i>الاسم</span>
                                    <span class="fw-medium small">{{ $metadata['billing_name'] }}</span>
                                </div>
                            @endif

                            @if (!empty($metadata['billing_email']))
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <span class="text-muted small"><i class="ti ti-mail me-1"></i>البريد</span>
                                    <span class="fw-medium small">{{ $metadata['billing_email'] }}</span>
                                </div>
                            @endif

                            @if (!empty($metadata['billing_phone']))
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <span class="text-muted small"><i class="ti ti-phone me-1"></i>الجوال</span>
                                    <span class="fw-medium small">{{ $metadata['billing_phone'] }}</span>
                                </div>
                            @endif

                            @if (!empty($metadata['billing_company_name']))
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <span class="text-muted small"><i class="ti ti-building me-1"></i>الشركة</span>
                                    <span class="fw-medium small">{{ $metadata['billing_company_name'] }}</span>
                                </div>
                            @endif

                            @if (!empty($metadata['billing_commercial_number']))
                                <div class="list-group-item d-flex justify-content-between align-items-center px-0 py-2">
                                    <span class="text-muted small"><i class="ti ti-id-badge me-1"></i>السجل التجاري</span>
                                    <span class="fw-medium small">{{ $metadata['billing_commercial_number'] }}</span>
                                </div>
                            @endif

                        </div>
                    @else
                        <p class="text-muted my-3 text-center small">لا توجد بيانات</p>
                    @endif
                </div>
            </div>

        </div>
    </div>
@endsection

@section('page-script')
    <script>
        $(document).on('click', '.js-verify-bill-btn', function(e) {
            e.preventDefault();
            window.confirmAction({
                title: 'تأكيد الإيصال البنكي',
                text: 'هل أنت متأكد من صحة الإيصال وتأكيد دفع الفاتورة؟',
                variant: 'success',
                icon: 'ti-check',
                confirmText: 'نعم، تأكيد الدفع',
                confirmClass: 'btn btn-success px-4',
                onConfirm: function() {
                    document.getElementById('verifyBillForm')?.submit();
                }
            });
        });
    </script>
@endsection
