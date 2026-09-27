@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل التذكرة #' . $ticket->ticket_number)

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
            <h4 class="mb-1">
                <i class="ti ti-ticket text-primary me-2"></i>
                تذكرة #{{ $ticket->ticket_number }}
            </h4>
            <p class="text-muted mb-0">{{ $ticket->title }}</p>
        </div>
        <div>
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة لقائمة التذاكر
            </a>
        </div>
    </div>

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
        <!-- المحادثة والوصف (العمود الأيمن/الأكبر) -->
        <div class="col-lg-8">
            <!-- تفاصيل المشكلة الأساسية -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-file-text me-2 text-primary"></i>
                        تفاصيل التذكرة
                    </h5>
                    <div>
                        {!! $status !!}
                    </div>
                </div>
                <div class="card-body pt-4">
                    <h6 class="fw-bold mb-2">{{ $ticket->title }}</h6>
                    <div class="p-3 bg-lighter rounded mb-3 text-secondary" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $ticket->description }}</div>

                    @if (!empty($ticket->attachments) && count($ticket->attachments) > 0)
                        <div class="mt-3">
                            <h6 class="small text-muted mb-2"><i class="ti ti-paperclip me-1"></i> المرفقات:</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($ticket->attachments as $file)
                                    <a href="{{ Storage::url($file['url'] ?? $file) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-download me-1"></i> {{ $file['name'] ?? 'مرفق' }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- سجل الردود والمحادثة -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-messages me-2 text-info"></i>
                        المحادثة والردود ({{ count($replies) }})
                    </h5>
                </div>
                <div class="card-body pt-4">
                    <div class="chat-history-wrapper">
                        @forelse($replies as $reply)
                            @php
                                $isAdmin = in_array($reply->sender_type, ['support_user', 'admin']);
                            @endphp
                            <div class="d-flex {{ $isAdmin ? 'justify-content-start' : 'justify-content-end' }} mb-4">
                                <div class="card border {{ $isAdmin ? 'bg-primary-subtle border-primary' : 'bg-light border-secondary' }} shadow-none" style="max-width: 80%;">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 gap-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge {{ $isAdmin ? 'bg-primary' : 'bg-secondary' }}">
                                                    {{ $isAdmin ? 'فريق الدعم (' . ($reply->user_name ?? 'الإدارة') . ')' : 'المستأجر (' . ($ticket->customer_name ?? 'العميل') . ')' }}
                                                </span>
                                            </div>
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                {{ \Carbon\Carbon::parse($reply->created_at)->diffForHumans() }}
                                            </small>
                                        </div>
                                        <div class="text-dark" style="white-space: pre-wrap; line-height: 1.5;">{{ $reply->message }}</div>

                                        @if (!empty($reply->attachments) && count($reply->attachments) > 0)
                                            <div class="mt-2 pt-2 border-top">
                                                @foreach ($reply->attachments as $att)
                                                    <a href="{{ Storage::url($att['url'] ?? $att) }}" target="_blank" class="badge bg-label-info text-decoration-none me-1">
                                                        <i class="ti ti-paperclip me-1"></i> {{ $att['name'] ?? 'مرفق' }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif

                                        @if ($reply->rating)
                                            <div class="mt-2 text-warning" style="font-size: 0.85rem;">
                                                <span>تقييم العميل للرد:</span>
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <span>{{ $i <= $reply->rating ? '★' : '☆' }}</span>
                                                @endfor
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center py-4 text-muted">
                                <i class="ti ti-message-off fs-1 mb-2 d-block"></i>
                                لا توجد ردود بعد على هذه التذكرة.
                            </div>
                        @endforelse
                    </div>

                    <!-- نموذج إضافة رد -->
                    <hr class="my-4">
                    <form action="{{ route('admin.tickets.reply', $ticket->ticket_number) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="replyMessage">إضافة رد على التذكرة:</label>
                            <textarea class="form-control" id="replyMessage" name="message" rows="4" placeholder="اكتب رد الدعم الفني هنا..." required>{{ old('message') }}</textarea>
                        </div>
                        <div class="row align-items-center">
                            <div class="col-md-8 mb-2 mb-md-0">
                                <label class="form-label small text-muted mb-1" for="attachments">مرفقات (اختياري):</label>
                                <input type="file" class="form-control form-control-sm" id="attachments" name="attachments[]" multiple accept="image/*,application/pdf,.doc,.docx">
                            </div>
                            <div class="col-md-4 text-md-end">
                                <button type="submit" class="btn btn-primary mt-3 mt-md-0">
                                    <i class="ti ti-send me-1"></i> إرسال الرد
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- معلومات التذكرة والعميل (العمود الأيسر) -->
        <div class="col-lg-4">
            <!-- تغيير حالة التذكرة -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0"><i class="ti ti-adjustments me-2 text-warning"></i> التحكم بالحالة</h5>
                </div>
                <div class="card-body pt-3">
                    <form action="{{ route('admin.tickets.updateStatus', $ticket->ticket_number) }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label small">الحالة الحالية:</label>
                            <select name="status" class="form-select">
                                <option value="open" {{ $ticket->status === 'open' ? 'selected' : '' }}>جديدة (Open)</option>
                                <option value="in_progress" {{ $ticket->status === 'in_progress' ? 'selected' : '' }}>قيد المعالجة (In Progress)</option>
                                <option value="resolved_pending_survey" {{ $ticket->status === 'resolved_pending_survey' ? 'selected' : '' }}>تم الحل وفي انتظار التقييم</option>
                                <option value="resolved" {{ $ticket->status === 'resolved' ? 'selected' : '' }}>تم المعالجة والحل (Resolved)</option>
                                <option value="reopened" {{ $ticket->status === 'reopened' ? 'selected' : '' }}>معادة (Reopened)</option>
                                <option value="closed" {{ $ticket->status === 'closed' ? 'selected' : '' }}>مغلقة (Closed)</option>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-warning w-100">
                            <i class="ti ti-check me-1"></i> تحديث الحالة
                        </button>
                    </form>
                </div>
            </div>

            <!-- معلومات المستأجر والعميل -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0"><i class="ti ti-user me-2 text-info"></i> بيانات المستأجر</h5>
                </div>
                <div class="card-body pt-3">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">المعرف (Tenant ID):</span>
                            <span class="badge bg-label-secondary font-monospace">{{ $ticket->tenant_id }}</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">المكتب / الشركة:</span>
                            <span class="fw-semibold">{{ $ticket->tenant?->company_name ?? 'مستأجر' }}</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">اسم صاحب التذكرة:</span>
                            <span class="fw-semibold">{{ $ticket->customer_name ?? '-' }}</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">البريد الإلكتروني:</span>
                            <span>{{ $ticket->customer_email ?? '-' }}</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">رقم الهاتف:</span>
                            <span>{{ $ticket->customer_phone ?? '-' }}</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">القسم:</span>
                            <span class="badge bg-label-primary">{{ $ticket->section?->name ?? 'الدعم الفني' }}</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">تاريخ الإنشاء:</span>
                            <span class="small">{{ $ticket->created_at?->format('Y-m-d H:i') }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- تقييم رضا العميل (إن وجد) -->
            @if (!empty($ticket->survey_result))
                <div class="card shadow-sm border-warning">
                    <div class="card-header bg-warning-subtle py-3">
                        <h6 class="card-title mb-0 text-warning">
                            <i class="ti ti-star-filled me-1"></i> نتيجة تقييم العميل
                        </h6>
                    </div>
                    <div class="card-body pt-3">
                        <div class="mb-2">
                            <strong>هل تم حل المشكلة؟:</strong>
                            <span class="badge {{ ($ticket->survey_result['solved'] ?? false) ? 'bg-success' : 'bg-danger' }}">
                                {{ ($ticket->survey_result['solved'] ?? false) ? 'نعم' : 'لا' }}
                            </span>
                        </div>
                        @if (isset($ticket->survey_result['agent_rating']))
                            <div class="mb-1 small">
                                <span class="text-muted">تقييم موظف الدعم:</span>
                                <span class="text-warning">★ {{ $ticket->survey_result['agent_rating'] }}/5</span>
                            </div>
                        @endif
                        @if (isset($ticket->survey_result['system_rating']))
                            <div class="mb-1 small">
                                <span class="text-muted">تقييم النظام:</span>
                                <span class="text-warning">★ {{ $ticket->survey_result['system_rating'] }}/5</span>
                            </div>
                        @endif
                        @if (isset($ticket->survey_result['solution_rating']))
                            <div class="mb-1 small">
                                <span class="text-muted">جودة الحل:</span>
                                <span class="text-warning">★ {{ $ticket->survey_result['solution_rating'] }}/5</span>
                            </div>
                        @endif
                        @if (!empty($ticket->survey_result['comment']))
                            <div class="mt-2 p-2 bg-light rounded small">
                                <em>"{{ $ticket->survey_result['comment'] }}"</em>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
