@extends('layouts.dashboard')
@section('title', 'طلب تواصل — ' . $contactRequest->customer_name)
@section('dashboard-scope', 'admin')

@section('content')

<div class="row g-4">
    {{-- Request details --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body">
                <h6 class="card-title mb-3">بيانات المرسل</h6>
                <ul class="list-unstyled mb-0">
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">الاسم</span>
                        <span class="fw-semibold">{{ $contactRequest->customer_name }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">البريد</span>
                        <span class="fw-semibold">{{ $contactRequest->customer_email }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">تاريخ الإرسال</span>
                        <span class="fw-semibold">{{ $contactRequest->created_at->format('Y-m-d H:i') }}</span>
                    </li>
                    <li class="d-flex justify-content-between py-2">
                        <span class="text-muted">الحالة</span>
                        <span>
                            @if($contactRequest->status === 'replied')
                                <span class="badge bg-label-success">تم الرد</span>
                            @else
                                <span class="badge bg-label-warning">بانتظار الرد</span>
                            @endif
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <a href="{{ route('admin.contact-requests.index') }}" class="btn btn-label-secondary w-100">
            <i class="ti ti-arrow-right me-1"></i>العودة للقائمة
        </a>
    </div>

    {{-- Message + Reply --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        {{-- Original message --}}
        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">الرسالة</h6>
            </div>
            <div class="card-body">
                <p class="mb-0" style="white-space: pre-wrap;">{{ $contactRequest->message }}</p>
            </div>
        </div>

        {{-- Existing reply --}}
        @if($contactRequest->reply)
            <div class="card mb-4 border-success">
                <div class="card-header bg-label-success">
                    <h6 class="mb-0 text-success">الرد المُرسل</h6>
                </div>
                <div class="card-body">
                    <p class="mb-2" style="white-space: pre-wrap;">{{ $contactRequest->reply }}</p>
                    <small class="text-muted">
                        بواسطة: {{ $contactRequest->replied_by ?? '—' }} —
                        {{ $contactRequest->replied_at?->format('Y-m-d H:i') ?? '' }}
                    </small>
                </div>
            </div>
        @endif

        {{-- Reply form --}}
        @can('public_requests.reply')
            @if($contactRequest->status !== 'replied')
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0">إرسال رد</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('admin.contact-requests.reply', $contactRequest) }}">
                            @csrf
                            <div class="mb-3">
                                <label for="reply" class="form-label">نص الرد</label>
                                <textarea
                                    id="reply"
                                    name="reply"
                                    rows="6"
                                    class="form-control @error('reply') is-invalid @enderror"
                                    placeholder="اكتب ردك هنا..."
                                >{{ old('reply') }}</textarea>
                                @error('reply')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send me-1"></i>إرسال الرد
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        @endcan
    </div>
</div>

@endsection
