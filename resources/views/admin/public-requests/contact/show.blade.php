@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل طلب التواصل')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">تفاصيل طلب التواصل</h4>
            <p class="text-muted mb-0">مراجعة بيانات العميل والرد على الاستفسار</p>
        </div>
        <a href="{{ route('admin.contact-requests.index') }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة للطلبات
        </a>
    </div>

    <div class="row g-4">
        <div class="col-xl-4 col-lg-5">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">بيانات الطلب</h5>
                    <span class="badge bg-label-{{ $contactRequest->status->value === 'pending' ? 'warning' : 'success' }}">
                        {{ $contactRequest->status->label() }}
                    </span>
                </div>
                <div class="card-body">
                    <dl class="row mb-0">
                        <dt class="col-sm-5 text-muted">الاسم</dt>
                        <dd class="col-sm-7">{{ $contactRequest->customer_name }}</dd>
                        <dt class="col-sm-5 text-muted">البريد</dt>
                        <dd class="col-sm-7 text-break">{{ $contactRequest->customer_email }}</dd>
                        <dt class="col-sm-5 text-muted">التاريخ</dt>
                        <dd class="col-sm-7">{{ $contactRequest->created_at?->format('Y-m-d H:i') ?? '-' }}</dd>
                    </dl>
                    <hr>
                    <h6 class="mb-2">الرسالة</h6>
                    <div class="bg-lighter rounded p-3" style="white-space: pre-wrap;">{{ $contactRequest->message }}</div>
                </div>
            </div>
        </div>

        <div class="col-xl-8 col-lg-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">الرد على الطلب</h5>
                </div>
                <div class="card-body">
                    @if ($contactRequest->reply)
                        <div class="alert alert-success mb-0">
                            <h6 class="alert-heading">تم إرسال الرد</h6>
                            <p class="mb-0" style="white-space: pre-wrap;">{{ $contactRequest->reply }}</p>
                        </div>
                    @else
                        <form method="POST" action="{{ route('admin.contact-requests.reply', $contactRequest) }}"
                            data-form-validate>
                            @csrf
                            <x-form.controls.textarea name="reply" label="نص الرد" rows="8" required
                                placeholder="اكتب الرد المرسل إلى العميل..." col="col-12" />
                            <div class="d-flex justify-content-end">
                                <button class="btn btn-primary">
                                    <i class="ti ti-send me-1"></i> إرسال الرد
                                </button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection
