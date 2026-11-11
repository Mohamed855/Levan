@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل طلب التواصل')

@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.contact-requests.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت بيانات العميل والطلب (العمود الأيمن) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <div class="avatar avatar-xl bg-label-primary rounded-circle p-3 d-flex align-items-center justify-content-center mx-auto shadow-sm"
                            style="width: 100px; height: 100px;">
                            <i class="ti ti-mail-opened fs-1 text-primary"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-primary">{{ $contactRequest->customer_name }}</h4>
                    <p class="mb-2 text-muted fw-medium text-break">{{ $contactRequest->customer_email }}</p>
                    <span
                        class="badge bg-label-{{ $contactRequest->status->value === 'pending' ? 'warning' : 'success' }} mb-3 fs-6">
                        {{ $contactRequest->status->label() }}
                    </span>

                    <div class="info-container text-start border-top pt-3 mt-1">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-user text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الاسم:</span>
                                <span>{{ $contactRequest->customer_name }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-mail text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">البريد:</span>
                                <span class="text-break">{{ $contactRequest->customer_email }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">التاريخ:</span>
                                <span>@datetime($contactRequest->created_at)</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-status-change text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الحالة:</span>
                                <span
                                    class="badge bg-label-{{ $contactRequest->status->value === 'pending' ? 'warning' : 'success' }}">
                                    {{ $contactRequest->status->label() }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- الرسالة والرد (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            <div class="card mb-4">
                <div class="card-header pb-2 mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-message-text text-primary me-2"></i>رسالة العميل
                    </h5>
                </div>
                <div class="card-body">
                    <div class="bg-secondary rounded p-3" style="white-space: pre-wrap; line-height: 1.6;">{{ $contactRequest->message }}</div>
                </div>
            </div>

            <div class="card">
                <div class="card-header pb-2 mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-send text-primary me-2"></i>الرد على الطلب
                    </h5>
                </div>
                <div class="card-body">
                    @if ($contactRequest->reply)
                        <div class="alert alert-success mb-0">
                            <h6 class="alert-heading mb-1">تم إرسال الرد</h6>
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
