@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل طلب التجربة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
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
                        class="badge bg-label-{{ match ($trialRequest->status->value) {
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
                        <p class="text-muted">اختر الإجراء المطلوب لهذا الطلب.</p>
                        <div class="d-flex align-items-center gap-2">
                            <form method="POST" action="{{ route('admin.trial-requests.approve', $trialRequest) }}" class="m-0 d-inline-flex">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-success">
                                    <i class="ti ti-check me-1"></i> موافقة وإنشاء شركة
                                </button>
                            </form>
                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectTrialModal">
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
        <div class="modal fade" id="rejectTrialModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-simple">
                <div class="modal-content p-3 p-md-5">
                    <div class="modal-body p-0">
                        <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                        <div class="text-center mb-4">
                            <h4 class="mb-2">رفض طلب التجربة</h4>
                            <p class="text-muted">يرجى توضيح سبب الرفض ليتم إشعار العميل وتسجيله في الطلب.</p>
                        </div>
                        <form method="POST" action="{{ route('admin.trial-requests.reject', $trialRequest) }}"
                            data-form-validate>
                            @csrf
                            <div class="mb-4">
                                <x-form.controls.textarea name="rejection_reason" label="سبب الرفض" rows="4" required />
                            </div>
                            <div class="d-flex justify-content-center gap-2">
                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                                <button type="submit" class="btn btn-danger">تأكيد الرفض</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
