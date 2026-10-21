@extends('layouts.dashboard')

@section('title', 'تفاصيل التذكرة #' . $ticket->ticket_number)
@section('dashboard-scope', 'admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-ticket text-primary me-2"></i>
                تذكرة #{{ $ticket->ticket_number }}
            </h4>
            <p class="text-muted mb-0">{{ $ticket->subject }}</p>
        </div>
        <div>
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible mb-4" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        {{-- Right / Main conversation column --}}
        <div class="col-lg-8">
            {{-- Ticket description card --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-file-text me-2 text-primary"></i>
                        نص التذكرة وموضوعها
                    </h5>
                    @php
                        $status = $ticket->status instanceof \App\Enums\Admin\Tickets\TicketStatus ? $ticket->status : \App\Enums\Admin\Tickets\TicketStatus::tryFrom((string)$ticket->status);
                        $color = $status ? $status->color() : 'secondary';
                        $label = $status ? $status->label() : (string)$ticket->status;
                    @endphp
                    <span class="badge bg-{{ $color }}">{{ $label }}</span>
                </div>
                <div class="card-body pt-4">
                    <h6 class="fw-bold mb-2">{{ $ticket->subject }}</h6>
                    <div class="p-3 bg-light rounded text-body" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $ticket->description }}</div>
                </div>
            </div>

            {{-- Replies thread --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-messages me-2 text-info"></i>
                        سجل الردود ({{ $replies->count() }})
                    </h5>
                </div>
                <div class="card-body pt-4">
                    @forelse($replies as $reply)
                        <div class="card border mb-3 {{ $reply->admin_user_id ? 'border-primary bg-label-primary' : 'border-secondary' }} shadow-none">
                            <div class="card-body p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge {{ $reply->admin_user_id ? 'bg-primary' : 'bg-secondary' }}">
                                            {{ $reply->admin_user_id ? 'فريق الدعم (' . ($reply->adminUser?->name ?? 'الإدارة') . ')' : 'المستأجر' }}
                                        </span>
                                    </div>
                                    <small class="text-muted font-monospace">{{ $reply->created_at?->format('Y-m-d H:i') }}</small>
                                </div>
                                <div class="text-body" style="white-space: pre-wrap; line-height: 1.6;">{{ $reply->message }}</div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4 text-muted">
                            <i class="ti ti-message-off fs-2 d-block mb-2"></i>
                            لا توجد ردود بعد على هذه التذكرة.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Reply Form --}}
            @can('tickets.reply')
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-send me-2 text-success"></i>
                            إضافة رد جديد
                        </h5>
                    </div>
                    <div class="card-body pt-4">
                        <form method="POST" action="{{ route('admin.tickets.reply', $ticket) }}">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label" for="reply-message">نص الرد <span class="text-danger">*</span></label>
                                <textarea name="message" id="reply-message" rows="4" class="form-control" placeholder="اكتب ردك هنا..." required>{{ old('message') }}</textarea>
                            </div>
                            <div class="text-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-send me-1"></i> إرسال الرد
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endcan
        </div>

        {{-- Left / Sidebar controls --}}
        <div class="col-lg-4">
            {{-- Status changer --}}
            @can('tickets.reply')
                <div class="card mb-4 shadow-sm">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-adjustments me-2 text-primary"></i>
                            تغيير حالة التذكرة
                        </h5>
                    </div>
                    <div class="card-body pt-4">
                        <form method="POST" action="{{ route('admin.tickets.status', $ticket) }}">
                            @csrf
                            @method('PATCH')
                            <div class="mb-3">
                                <label class="form-label">الحالة الجديدة</label>
                                <select name="status" class="form-select">
                                    @foreach (\App\Enums\Admin\Tickets\TicketStatus::options() as $opt)
                                        @php
                                            $currentStatus = $ticket->status instanceof \App\Enums\Admin\Tickets\TicketStatus ? $ticket->status->value : (string)$ticket->status;
                                        @endphp
                                        <option value="{{ $opt['value'] }}" @selected($currentStatus === $opt['value'])>
                                            {{ $opt['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="ti ti-check me-1"></i> حفظ الحالة
                            </button>
                        </form>
                    </div>
                </div>
            @endcan

            {{-- Tenant Info Card --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-building me-2 text-info"></i>
                        بيانات المستأجر
                    </h5>
                </div>
                <div class="card-body pt-4">
                    @if ($ticket->tenant)
                        <div class="d-flex align-items-center mb-3">
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initial rounded bg-label-primary"><i class="ti ti-building"></i></span>
                            </div>
                            <div>
                                <h6 class="mb-0">{{ $ticket->tenant->company_name }}</h6>
                                <small class="text-muted">المعرف: {{ $ticket->tenant->id }}</small>
                            </div>
                        </div>
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><i class="ti ti-mail me-2 text-muted"></i>{{ $ticket->tenant->email ?? '-' }}</li>
                            <li class="mb-2"><i class="ti ti-phone me-2 text-muted"></i>{{ $ticket->tenant->phone ?? '-' }}</li>
                        </ul>
                    @else
                        <div class="text-muted">طلب عام غير مرتبط بمستأجر محدد.</div>
                    @endif
                </div>
            </div>

            {{-- Metadata Card --}}
            <div class="card shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-info-circle me-2 text-secondary"></i>
                        معلومات التذكرة
                    </h5>
                </div>
                <div class="card-body pt-4">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">القسم:</span>
                            <span class="fw-semibold">{{ $ticket->department ?: 'الدعم الفني' }}</span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">تاريخ الفتح:</span>
                            <span class="font-monospace">{{ $ticket->created_at?->format('Y-m-d H:i') }}</span>
                        </li>
                        <li class="d-flex justify-content-between mb-2">
                            <span class="text-muted">آخر تحديث:</span>
                            <span class="font-monospace">{{ $ticket->updated_at?->format('Y-m-d H:i') }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection
