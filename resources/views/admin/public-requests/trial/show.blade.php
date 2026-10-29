@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل طلب التجربة')



@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">تفاصيل طلب التجربة</h4>
            <p class="text-muted mb-0">مراجعة الطلب واتخاذ الإجراء المناسب</p>
        </div>
        <a href="{{ route('admin.trial-requests.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة للطلبات
        </a>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">بيانات المتقدم</h5>
                    <span
                        class="badge bg-{{ match ($trialRequest->status->value) {
                            'pending' => 'warning',
                            'approved' => 'success',
                            default => 'danger',
                        } }}">{{ $trialRequest->status->label() }}</span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted">الاسم</dt>
                        <dd class="col-sm-7">{{ $trialRequest->customer_name }}</dd>
                        <dt class="col-sm-5 text-muted">البريد</dt>
                        <dd class="col-sm-7 text-break">{{ $trialRequest->customer_email }}</dd>
                        <dt class="col-sm-5 text-muted">الهاتف</dt>
                        <dd class="col-sm-7" dir="ltr">{{ $trialRequest->customer_phone }}</dd>
                        <dt class="col-sm-5 text-muted">الشركة</dt>
                        <dd class="col-sm-7">{{ $trialRequest->company_name ?? 'طلب قديم غير مكتمل' }}</dd>
                        <dt class="col-sm-5 text-muted">السجل التجاري</dt>
                        <dd class="col-sm-7">{{ $trialRequest->commercial_number ?? '-' }}</dd>
                        <dt class="col-sm-5 text-muted">الرقم الضريبي</dt>
                        <dd class="col-sm-7">{{ $trialRequest->tax_number ?? '-' }}</dd>
                        <dt class="col-sm-5 text-muted">المدينة</dt>
                        <dd class="col-sm-7">{{ $trialRequest->city?->name ?? '-' }}</dd>
                        <dt class="col-sm-5 text-muted">العنوان</dt>
                        <dd class="col-sm-7">{{ $trialRequest->address ?? '-' }}</dd>
                        <dt class="col-sm-5 text-muted">تأكيد البريد</dt>
                        <dd class="col-sm-7">{{ $trialRequest->email_verified_at?->format('Y-m-d H:i') ?? 'غير مؤكد' }}
                        </dd>
                        @if ($trialRequest->provisioning_error)
                            <dd class="col-12 text-danger">{{ $trialRequest->provisioning_error }}</dd>
                        @endif
                        @if ($trialRequest->status->value === 'approved' && $trialRequest->tenant)
                            <dd class="col-12"><a class="btn btn-success" target="_blank" rel="noopener"
                                    href="{{ route('path.login', ['tenant' => $trialRequest->tenant_id]) }}"><i class="ti ti-external-link me-1"></i>فتح رابط دخول العميل</a></dd>
                            <dt class="col-sm-5 text-muted">بداية التجربة</dt>
                            <dd class="col-sm-7">{{ $trialRequest->subscription?->starts_at?->format('Y-m-d H:i') }}</dd>
                            <dt class="col-sm-5 text-muted">نهاية التجربة</dt>
                            <dd class="col-sm-7">{{ $trialRequest->subscription?->ends_at?->format('Y-m-d H:i') }}</dd>
                            <dd class="col-12">بدون باقة — مستخدم واحد (المدير الأساسي)</dd>
                        @endif
                        <dt class="col-sm-5 text-muted">التاريخ</dt>
                        <dd class="col-sm-7">{{ $trialRequest->created_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                        @if ($trialRequest->rejection_reason)
                            <dt class="col-sm-5 text-muted">سبب الرفض</dt>
                            <dd class="col-sm-7">{{ $trialRequest->rejection_reason }}</dd>
                        @endif
                    </dl>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">إجراءات الطلب</h5>
                </div>
                <div class="card-body">
                    @if ($trialRequest->status->value === 'pending')
                        <p class="text-muted">الموافقة تنشئ حساب الشركة والمدير وتفعّل تجربة 7 أيام بدون باقة أو دفع.</p>
                        @if (!$trialRequest->password_hash || !$trialRequest->email_verified_at)
                            <div class="alert alert-warning">الطلب قديم أو ناقص البيانات. يجب أن يقدم العميل طلبًا كاملًا مع
                                تأكيد البريد أولًا.</div>
                        @endif
                        <div class="d-flex align-items-center gap-2">
                            <form id="approveTrialForm" method="POST"
                                action="{{ route('admin.trial-requests.approve', $trialRequest) }}"
                                class="m-0 d-inline-flex">
                                @csrf
                                <button type="button" class="btn btn-sm btn-success js-approve-trial-btn"
                                    @disabled(
                                        !$trialRequest->password_hash ||
                                            !$trialRequest->email_verified_at ||
                                            ($trialRequest->provisioning_status === 'processing' &&
                                                $trialRequest->provisioning_started_at?->gt(now()->subMinutes(15))))>
                                    <i class="ti ti-check me-1"></i> موافقة وإنشاء شركة
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal"
                                data-bs-target="#rejectTrialModal">
                                <i class="ti ti-x me-1"></i> رفض الطلب
                            </button>
                        </div>
                    @else
                        <div class="alert alert-success mb-0">
                            تم اتخاذ إجراء على هذا الطلب ولا توجد إجراءات متاحة حاليًا.
                        </div>
                    @endif
                </div>
            </div>
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
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>إلغاء</button>
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
