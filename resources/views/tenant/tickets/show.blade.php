@extends('layouts.dashboard')

@section('title', 'تذكرة #' . $ticket->ticket_number)
@section('dashboard-scope', 'tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('tenant.tickets.index') }}">الدعم الفني</a></li>
                <li class="breadcrumb-item active">تذكرة #{{ $ticket->ticket_number }}</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">تذكرة رقم <span class="text-primary font-monospace">#{{ $ticket->ticket_number }}</span></h4>
    </div>
    <div>
        <a href="{{ route('tenant.tickets.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة لقائمة التذاكر
        </a>
    </div>
</div>

<div class="row">
    {{-- العمود الأيمن: موضوع التذكرة وسجل المحادثة ونموذج الرد --}}
    <div class="col-xl-8 col-lg-7 col-md-7 mb-4">
        {{-- بطاقة التذكرة الأصلية --}}
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-label-primary py-3 d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0 fw-bold text-primary">
                    <i class="ti ti-message-2 me-2"></i> {{ $ticket->subject }}
                </h5>
                <span class="badge bg-{{ $ticket->status->color() }}">{{ $ticket->status->label() }}</span>
            </div>
            <div class="card-body pt-3">
                <div class="d-flex align-items-center gap-2 mb-3 text-muted small">
                    <span><i class="ti ti-calendar me-1"></i> تم الفتح في: {{ $ticket->created_at?->format('Y-m-d H:i') }}</span>
                    <span>•</span>
                    <span><i class="ti ti-category me-1"></i> القسم: {{ $ticket->department }}</span>
                </div>
                <div class="p-3 bg-body-tertiary rounded">
                    <p class="mb-0" style="white-space: pre-line;">{{ $ticket->description }}</p>
                </div>
            </div>
        </div>

        {{-- سجل الردود والمحادثة --}}
        <div class="card mb-4">
            <div class="card-header py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="ti ti-messages me-2 text-primary"></i> المحادثات والردود ({{ $ticket->replies->count() }})
                </h5>
            </div>
            <div class="card-body pt-4">
                @forelse($ticket->replies as $reply)
                    @php
                        $isAdmin = $reply->admin_user_id !== null;
                    @endphp
                    <div class="d-flex mb-4 {{ $isAdmin ? 'flex-row' : 'flex-row-reverse' }} gap-3">
                        <div class="avatar avatar-md flex-shrink-0">
                            @if($isAdmin)
                                <div class="avatar-initial rounded-circle bg-label-primary">
                                    <i class="ti ti-headset"></i>
                                </div>
                            @else
                                <div class="avatar-initial rounded-circle bg-label-success">
                                    <i class="ti ti-user"></i>
                                </div>
                            @endif
                        </div>
                        <div class="w-100" style="max-width: 85%;">
                            <div class="d-flex justify-content-between align-items-center mb-1 {{ $isAdmin ? '' : 'flex-row-reverse' }}">
                                <span class="fw-bold fs-6 {{ $isAdmin ? 'text-primary' : 'text-success' }}">
                                    {{ $isAdmin ? ($reply->adminUser?->name ?? 'فريق دعم لوت') : 'فريق العمل' }}
                                </span>
                                <small class="text-muted">{{ $reply->created_at?->diffForHumans() }}</small>
                            </div>
                            <div class="p-3 rounded {{ $isAdmin ? 'bg-primary-subtle border border-primary-subtle text-dark' : 'bg-body-tertiary border text-dark' }}">
                                <p class="mb-0" style="white-space: pre-line;">{{ $reply->message }}</p>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-clock-check fs-2 mb-2 d-block"></i>
                        لم يتم إضافة أي ردود حتى الآن. سيقوم فريق الدعم بالرد قريباً.
                    </div>
                @endforelse
            </div>
        </div>

        {{-- نموذج إضافة رد --}}
        <div class="card">
            <div class="card-header py-3 border-bottom">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="ti ti-corner-down-left me-2 text-primary"></i> إضافة رد جديد
                </h5>
            </div>
            <div class="card-body pt-4">
                <form action="{{ route('tenant.tickets.reply', $ticket->id) }}" method="POST">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="reply-message">رسالتك <span class="text-danger">*</span></label>
                        <textarea name="message" id="reply-message" rows="4" class="form-control @error('message') is-invalid @enderror" placeholder="اكتب ردك أو استفسارك الإضافي هنا..." required>{{ old('message') }}</textarea>
                        @error('message')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="ti ti-send me-1"></i> إرسال الرد
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- العمود الأيسر: بطاقة معلومات التذكرة وحالتها --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3 fw-bold">معلومات التذكرة</h5>
                <div class="text-center py-3">
                    <div class="avatar avatar-xl mx-auto mb-3 bg-label-{{ $ticket->status->color() }} rounded-circle d-flex align-items-center justify-content-center">
                        <i class="ti ti-headset fs-1 text-{{ $ticket->status->color() }}"></i>
                    </div>
                    <span class="badge bg-{{ $ticket->status->color() }} fs-6">{{ $ticket->status->label() }}</span>
                </div>

                <div class="border-top pt-3 mt-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">رقم التذكرة:</span>
                        <div class="d-flex align-items-center gap-1">
                            <span class="fw-bold font-monospace text-primary">#{{ $ticket->ticket_number }}</span>
                            <button type="button" class="btn btn-xs btn-label-secondary" onclick="navigator.clipboard.writeText('{{ $ticket->ticket_number }}'); toastr.success('تم نسخ رقم التذكرة');" title="نسخ">
                                <i class="ti ti-copy"></i>
                            </button>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">القسم:</span>
                        <span class="fw-semibold">{{ $ticket->department }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">تاريخ الإنشاء:</span>
                        <span>{{ $ticket->created_at?->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">آخر تحديث:</span>
                        <span>{{ $ticket->updated_at?->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header py-3 border-bottom">
                <h6 class="card-title mb-0 fw-bold">
                    <i class="ti ti-info-circle me-1 text-info"></i> إرشادات الدعم الفني
                </h6>
            </div>
            <div class="card-body pt-3 small text-muted">
                <ul class="ps-3 mb-0">
                    <li class="mb-2">فريق الدعم الفني متاح على مدار الساعة للرد على استفساراتكم.</li>
                    <li class="mb-2">يمكنك الرد في أي وقت لإضافة تفاصيل أو ملفات جديدة.</li>
                    <li>عند حل المشكلة، يمكنك إبلاغنا أو ترك التذكرة ليتم إغلاقها تلقائياً.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
