@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل طلب التجربة')

@php
    $statusColor = match ($trialRequest->status->value) {
        'pending' => 'warning',
        'approved' => 'success',
        'already_subscribed' => 'info',
        default => 'danger',
    };
@endphp

@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.trial-requests.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
            @if ($trialRequest->status->value === 'approved' && $trialRequest->tenant)
                <a class="btn btn-primary" target="_blank" rel="noopener"
                    href="{{ route('path.login', ['tenant' => $trialRequest->tenant_id]) }}">
                    <i class="ti ti-external-link me-1"></i> فتح رابط دخول العميل
                </a>
            @elseif ($trialRequest->status->value === 'already_subscribed' && $trialRequest->tenant)
                <a class="btn btn-primary" href="{{ route('admin.clients.show', $trialRequest->tenant_id) }}">
                    <i class="ti ti-building me-1"></i> عرض ملف الشركة المشتركة
                </a>
            @endif
        </div>
    </div>

    <div class="row">
        {{-- كارت بيانات المتقدم (العمود الأيمن) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <div class="avatar avatar-xl bg-label-primary rounded-circle p-3 d-flex align-items-center justify-content-center mx-auto shadow-sm"
                            style="width: 100px; height: 100px;">
                            <i class="ti ti-building-store fs-1 text-primary"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-primary">{{ $trialRequest->company_name ?? 'طلب قديم غير مكتمل' }}</h4>
                    <p class="mb-2 text-muted fw-medium">{{ $trialRequest->customer_name }}</p>
                    <span class="badge bg-label-{{ $statusColor }} mb-3 fs-6">
                        {{ $trialRequest->status->label() }}
                    </span>

                    <div class="info-container text-start border-top pt-3 mt-1">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-user text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الاسم:</span>
                                <span>{{ $trialRequest->customer_name }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-mail text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">البريد:</span>
                                <span class="text-break">{{ $trialRequest->customer_email }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-phone text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الهاتف:</span>
                                <span dir="ltr">{{ $trialRequest->customer_phone }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-building text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الشركة:</span>
                                <span>{{ $trialRequest->company_name ?? 'طلب قديم غير مكتمل' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-file-text text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">السجل التجاري:</span>
                                <span>{{ $trialRequest->commercial_number ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-receipt-tax text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الرقم الضريبي:</span>
                                <span>{{ $trialRequest->tax_number ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-map-pin text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المدينة:</span>
                                <span>{{ $trialRequest->city?->name ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-home text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">العنوان:</span>
                                <span>{{ $trialRequest->address ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-mail-check text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تأكيد البريد:</span>
                                <span>{{ system_datetime($trialRequest->email_verified_at, 'غير مؤكد') }}</span>
                            </li>
                            <li class="{{ $trialRequest->rejection_reason ? 'mb-3' : 'mb-2' }} d-flex align-items-center">
                                <i class="ti ti-calendar text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">التاريخ:</span>
                                <span>@datetime($trialRequest->created_at)</span>
                            </li>
                            @if ($trialRequest->rejection_reason)
                                <li class="mb-2 d-flex align-items-center">
                                    <i class="ti ti-ban text-danger me-2 fs-5"></i>
                                    <span class="fw-bold text-danger me-2">سبب الرفض:</span>
                                    <span>{{ $trialRequest->rejection_reason }}</span>
                                </li>
                            @endif
                        </ul>

                        @if ($trialRequest->provisioning_error && $trialRequest->status->value !== 'already_subscribed')
                            <div class="alert alert-danger mt-3 mb-0 small">
                                {{ $trialRequest->provisioning_error }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- إجراءات الطلب (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="card">
                <div class="card-header pb-2 mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-settings-check text-primary me-2"></i>إجراءات الطلب
                    </h5>
                </div>
                <div class="card-body">
                    @if ($trialRequest->status->value === 'pending')
                        <p class="text-muted">الموافقة تنشئ حساب الشركة والمدير وتفعّل تجربة 7 أيام بدون باقة أو دفع.</p>
                        @if (!$trialRequest->password_hash || !$trialRequest->email_verified_at)
                            <div class="alert alert-warning">الطلب قديم أو ناقص البيانات. يجب أن يقدم العميل طلبًا كاملًا مع
                                تأكيد البريد أولًا.</div>
                        @endif
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <form id="approveTrialForm" method="POST"
                                action="{{ route('admin.trial-requests.approve', $trialRequest) }}"
                                class="m-0 d-inline-flex">
                                @csrf
                                <button type="button" class="btn btn-success js-approve-trial-btn"
                                    @disabled(
                                        !$trialRequest->password_hash ||
                                            !$trialRequest->email_verified_at ||
                                            ($trialRequest->provisioning_status === 'processing' &&
                                                $trialRequest->provisioning_started_at?->gt(now()->subMinutes(15))))>
                                    <i class="ti ti-check me-1"></i> موافقة وإنشاء شركة
                                </button>
                            </form>
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                                data-bs-target="#rejectTrialModal">
                                <i class="ti ti-x me-1"></i> رفض الطلب
                            </button>
                        </div>
                    @elseif ($trialRequest->status->value === 'already_subscribed')
                        <div class="alert alert-info mb-0">
                            بيانات العميل مسجلة بالفعل كشركة في النظام (مشترك بالفعل)، ولا توجد إجراءات متاحة لهذا الطلب.
                        </div>
                    @else
                        <div class="alert alert-success mb-0">
                            تم اتخاذ إجراء على هذا الطلب ولا توجد إجراءات متاحة حاليًا.
                        </div>
                    @endif
                </div>
            </div>

            @if ($trialRequest->status->value === 'approved' && $trialRequest->tenant)
                <div class="card mb-4">
                    <div class="card-header pb-2 mb-3">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-clock-play text-primary me-2"></i>تفاصيل التجربة المفعّلة
                        </h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-3">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-plus text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">بداية التجربة:</span>
                                <span>@datetime($trialRequest->subscription?->starts_at)</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-due text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">نهاية التجربة:</span>
                                <span>@datetime($trialRequest->subscription?->ends_at)</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-user-shield text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">النطاق:</span>
                                <span>بدون باقة — مستخدم واحد (المدير الأساسي)</span>
                            </li>
                        </ul>
                        <a class="btn btn-success w-100" target="_blank" rel="noopener"
                            href="{{ route('path.login', ['tenant' => $trialRequest->tenant_id]) }}">
                            <i class="ti ti-external-link me-1"></i> فتح رابط دخول العميل
                        </a>
                    </div>
                </div>
            @elseif ($trialRequest->status->value === 'already_subscribed' && $trialRequest->tenant)
                <div class="card mb-4">
                    <div class="card-header pb-2 mb-3">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-building text-primary me-2"></i>حساب الشركة المرتبط
                        </h6>
                    </div>
                    <div class="card-body">
                        <a class="btn btn-outline-primary w-100"
                            href="{{ route('admin.clients.show', $trialRequest->tenant_id) }}">
                            <i class="ti ti-building me-1"></i> عرض ملف الشركة المشتركة
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    @if ($trialRequest->status->value === 'pending')
        <x-bootstrap.modal id="rejectTrialModal" title="رفض طلب التجربة" icon="ti-x">
            <form method="POST" action="{{ route('admin.trial-requests.reject', $trialRequest) }}" data-form-validate>
                @csrf
                <p class="text-muted mb-3">يرجى توضيح سبب الرفض ليتم إشعار العميل وتسجيله في الطلب.</p>
                <x-form.controls.textarea name="rejection_reason" label="سبب الرفض" rows="4" col="col-12"
                    required />
                <x-slot:footer>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i
                            class="ti ti-x me-1"></i>إلغاء</button>
                    <button type="submit" class="btn btn-danger"><i class="ti ti-ban me-1"></i>تأكيد الرفض</button>
                </x-slot:footer>
            </form>
        </x-bootstrap.modal>
    @endif
@endsection

@section('page-script')
    <script>
        $(document).on('click', '.js-approve-trial-btn', function(e) {
            e.preventDefault();
            window.confirmAction({
                title: 'الموافقة على طلب التجربة',
                text: 'هل أنت متأكد من رغبتك في الموافقة على هذا الطلب وإنشاء حساب الشركة والنطاق الفرعي؟',
                variant: 'success',
                icon: 'ti-building',
                confirmText: 'نعم، موافقة وإنشاء',
                confirmClass: 'btn btn-success px-4',
                onConfirm: function() {
                    document.getElementById('approveTrialForm')?.submit();
                }
            });
        });
    </script>
@endsection
