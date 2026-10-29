@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل التذكرة #' . $ticket->ticket_number)

@section('vendor-style')
    @vite(['resources/assets/css/tenant/tickets/show.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/tenant/tickets/show.js'])

    @if ($ticket->status === 'resolved_pending_survey')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modalEl = document.getElementById('ticketSurveyModal');
                if (modalEl) {
                    var surveyModal = new bootstrap.Modal(modalEl, {
                        backdrop: 'static',
                        keyboard: false
                    });
                    surveyModal.show();
                }
            });
        </script>
    @endif
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    {{-- مودال تقييم التذكرة --}}
    <x-bootstrap.modal id="ticketSurveyModal" title="تقييم التذكرة #{{ $ticket->ticket_number }}" icon="ti-star" size="lg">
        <form action="{{ tenant_route_name('tickets.survey.submit', $ticket->ticket_number) }}" method="POST"
            novalidate data-form-validate
            data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
            @csrf

            <div class="text-center py-2">
                <p class="mb-3 fw-bold fs-5">
                    شاركنا تجربتك! هل تم حل مشكلتك بنجاح؟
                </p>
                <div class="d-flex justify-content-center gap-3 mb-4 choice-buttons" data-form-field
                    data-field-name="solved">
                    <input type="radio" class="btn-check" name="solved" id="solvedYes" value="1"
                        autocomplete="off" required data-form-control {{ old('solved') == '1' ? 'checked' : '' }}>
                    <label class="btn btn-outline-success" for="solvedYes">
                        نعم، تم الحل
                    </label>

                    <input type="radio" class="btn-check" name="solved" id="solvedNo" value="0"
                        autocomplete="off" required data-form-control {{ old('solved') == '0' ? 'checked' : '' }}>
                    <label class="btn btn-outline-danger" for="solvedNo">
                        لا، لم تُحل
                    </label>
                </div>

                <div id="ratingSection" style="display: {{ old('solved') == '1' ? 'block' : 'none' }};">
                    <hr class="my-4">

                    @foreach ($surveyQuestions as $field => $label)
                        <div class="my-4">
                            <label class="form-label d-block mb-2 fw-semibold">{{ $label }}</label>

                            <input type="hidden" name="{{ $field }}" value="{{ old($field) }}"
                                data-validation-skip />

                            <div class="d-flex justify-content-center">
                                <div class="survey-stars" data-target="{{ $field }}">
                                    @for ($i = 1; $i <= 5; $i++)
                                        <span class="survey-star" data-value="{{ $i }}">★</span>
                                    @endfor
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <hr class="my-4">
                <div class="mt-3">
                    <textarea name="comment" class="form-control" data-form-control rows="3"
                        placeholder="شاركنا رأيك أو أي ملاحظات لديك...">{{ old('comment') }}</textarea>
                </div>
            </div>

            <x-slot:footer>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><i class="ti ti-x me-1"></i>إلغاء</button>
                <button type="submit" class="btn btn-primary" id="submitSurveyBtn">
                    <i class="ti ti-send me-1"></i> إرسال التقييم
                </button>
            </x-slot:footer>
        </form>
    </x-bootstrap.modal>

    {{-- رأس الصفحة --}}
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-ticket text-primary me-2"></i>
                تذكرة #{{ $ticket->ticket_number }}
            </h4>
            <p class="text-muted mb-0">{{ $ticket->title }}</p>
        </div>
        <div>
            <a href="{{ tenant_route_name('tickets.index') }}" class="btn btn-secondary">
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
        {{-- العمود الرئيسي --}}
        <div class="col-lg-8">
            {{-- تفاصيل التذكرة --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-file-text me-2 text-primary"></i>
                        تفاصيل التذكرة
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        {!! $status !!}
                        @if ($ticket->status === 'resolved_pending_survey')
                            <button type="button" class="btn btn-sm btn-outline-warning" data-bs-toggle="modal"
                                data-bs-target="#ticketSurveyModal">
                                <i class="ti ti-star me-1"></i> قيّم التذكرة الآن
                            </button>
                        @endif
                    </div>
                </div>
                <div class="card-body pt-4">
                    <h6 class="fw-bold mb-2">{{ $ticket->title }}</h6>
                    <div class="p-3 bg-secondary rounded mb-3"
                        style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $ticket->description }}
                    </div>

                    @if (!empty($ticket->attachments) && count($ticket->attachments) > 0)
                        <div class="mt-3">
                            <h6 class="small text-muted mb-2"><i class="ti ti-paperclip me-1"></i> المرفقات:</h6>
                            <div class="d-flex flex-wrap gap-2">
                                @foreach ($ticket->attachments as $file)
                                    @php
                                        $fileUrl = is_array($file) ? $file['url'] ?? '' : $file;
                                        $fileName = is_array($file) ? $file['name'] ?? 'مرفق' : 'مرفق';
                                        $fullUrl = Str::startsWith($fileUrl, 'http')
                                            ? $fileUrl
                                            : Storage::url($fileUrl);
                                    @endphp
                                    <a href="{{ $fullUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="ti ti-download me-1"></i> {{ $fileName }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- المحادثة والردود --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-messages me-2 text-info"></i>
                        المحادثة والردود ({{ count($replies['data'] ?? $replies) }})
                    </h5>
                </div>
                <div class="card-body pt-4">
                    <div class="chat-history-wrapper overflow-auto pe-1" style="max-height: 550px;">
                        @php
                            $repliesList = $replies['data'] ?? (is_iterable($replies) ? $replies : []);
                        @endphp
                        @forelse($repliesList as $reply)
                            @php
                                $senderType = is_array($reply)
                                    ? $reply['sender_type'] ?? ''
                                    : $reply->sender_type ?? '';
                                $isTenant = $senderType === 'tenant_user';
                                $message = is_array($reply) ? $reply['message'] ?? '' : $reply->message ?? '';
                                $createdAt = is_array($reply) ? $reply['created_at'] ?? '' : $reply->created_at ?? '';
                                $supportName = is_array($reply)
                                    ? $reply['support_user_name'] ?? ($reply['user_name'] ?? 'موظف الدعم')
                                    : $reply->support_user_name ?? ($reply->user_name ?? 'موظف الدعم');
                                $replyAttachments = is_array($reply)
                                    ? $reply['attachments'] ?? []
                                    : $reply->attachments ?? [];
                                $replyRating = is_array($reply)
                                    ? $reply['rating']['rating'] ?? ($reply['rating'] ?? null)
                                    : $reply->rating->rating ?? ($reply->rating ?? null);
                                $replyId = is_array($reply) ? $reply['id'] ?? null : $reply->id ?? null;
                            @endphp
                            {{-- رسائل المستأجر في اليمين (justify-content-start في RTL)، ورسائل الدعم/Super Admin في اليسار (justify-content-end في RTL) --}}
                            <div class="d-flex {{ $isTenant ? 'justify-content-start' : 'justify-content-end' }} mb-4">
                                <div class="card border {{ $isTenant ? 'bg-primary-subtle border-primary' : 'bg-secondary border-secondary' }} shadow-none"
                                    style="max-width: 80%;">
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2 gap-3">
                                            <div class="d-flex align-items-center gap-2">
                                                <span class="badge {{ $isTenant ? 'bg-primary' : 'bg-secondary' }}">
                                                    {{ $isTenant ? 'أنت (' . ($ticket->customer_name ?? 'المستأجر') . ')' : 'فريق الدعم (' . $supportName . ')' }}
                                                </span>
                                            </div>
                                            <small class="text-muted" style="font-size: 0.75rem;">
                                                {{ $createdAt ? \Carbon\Carbon::parse($createdAt)->diffForHumans() : '' }}
                                            </small>
                                        </div>
                                        <div class="text-dark" style="white-space: pre-wrap; line-height: 1.5;">
                                            {{ $message }}</div>

                                        {{-- المرفقات --}}
                                        @if (!empty($replyAttachments) && count($replyAttachments) > 0)
                                            <div class="mt-2 pt-2 border-top">
                                                @foreach ($replyAttachments as $att)
                                                    @php
                                                        $attUrl = is_array($att) ? $att['url'] ?? '' : $att;
                                                        $attName = is_array($att) ? $att['name'] ?? 'مرفق' : 'مرفق';
                                                        $fullAttUrl = Str::startsWith($attUrl, 'http')
                                                            ? $attUrl
                                                            : Storage::url($attUrl);
                                                    @endphp
                                                    <a href="{{ $fullAttUrl }}" target="_blank"
                                                        class="badge bg-info text-decoration-none me-1">
                                                        <i class="ti ti-paperclip me-1"></i> {{ $attName }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- تقييم الردود (فقط لردود الدعم الفني) --}}
                                        @if (!$isTenant && $replyId)
                                            <form method="POST" class="m-0" novalidate data-form-validate
                                                data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                                                action="{{ tenant_route_name('tickets.replies.rate', ['ticket' => $ticket->ticket_number, 'reply' => $replyId]) }}">
                                                @csrf
                                                <div
                                                    class="d-flex align-items-center justify-content-between mt-2 pt-2 border-top">
                                                    <small class="text-muted">تقييم هذا الرد:</small>
                                                    <div class="reply-rating">
                                                        @for ($i = 1; $i <= 5; $i++)
                                                            <button type="submit" name="stars"
                                                                value="{{ $i }}"
                                                                class="reply-star-btn {{ $replyRating && $i <= (is_numeric($replyRating) ? $replyRating : 0) ? 'rated' : '' }}"
                                                                title="{{ $i }} من 5">
                                                                ★
                                                            </button>
                                                        @endfor
                                                    </div>
                                                </div>
                                            </form>
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

                    {{-- نموذج إضافة رد --}}
                    <hr class="my-4">
                    @if (in_array($ticket->status, ['open', 'in_progress', 'reopened']))
                        <form action="{{ tenant_route_name('tickets.reply', $ticket->ticket_number) }}" method="POST"
                            enctype="multipart/form-data" novalidate data-form-validate
                            data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                            @csrf
                            <div class="mb-3" data-form-field data-field-name="message">
                                <label class="form-label fw-bold" for="replyMessage">إضافة رد على التذكرة:</label>
                                <div class="position-relative border rounded @error('message') border-danger @enderror"
                                    style="background-color: var(--bs-paper-bg, var(--bs-card-bg, #fff));">
                                    <textarea class="form-control border-0 shadow-none" id="replyMessage" name="message" data-form-control
                                        rows="4" placeholder="اكتب ردك هنا..."
                                        style="resize: vertical; min-height: 140px; margin-bottom: 3rem; background: transparent;" required>{{ old('message') }}</textarea>

                                    {{-- شريط المرفقات المدمج داخل الـ textarea --}}
                                    <div class="position-absolute bottom-0 start-0 end-0 px-3 py-2 border-top d-flex justify-content-between align-items-center rounded-bottom"
                                        dir="rtl"
                                        style="z-index: 5; height: 3rem; background-color: var(--bs-paper-bg, var(--bs-card-bg, #fff));">
                                        {{-- النص ع اليمين --}}
                                        <div id="attachment-status-container" class="d-flex align-items-center">
                                            <span id="attachment-status-text"
                                                class="badge bg-primary small d-none align-items-center">
                                                <i class="ti ti-paperclip me-1"></i> <span
                                                    id="attachment-count-label"></span>
                                                <i class="ti ti-x ms-2 cursor-pointer" id="btn-clear-attachments"
                                                    title="إلغاء المرفقات"></i>
                                            </span>
                                        </div>

                                        {{-- الـ icon ع الشمال --}}
                                        <div class="d-flex align-items-center">
                                            <input type="file" id="attachments" name="attachments[]" data-form-control
                                                multiple class="d-none" accept="image/*,application/pdf,.doc,.docx">
                                            <button type="button" id="btn-trigger-attachments"
                                                class="btn btn-icon mb-0 cursor-pointer" title="إرفاق ملفات">
                                                <i class="ti ti-paperclip fs-5"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                @error('message')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                                @error('attachments.*')
                                    <div class="text-danger small mt-1">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="ti ti-send me-1"></i> إرسال الرد
                                </button>
                            </div>
                        </form>
                    @else
                        <div class="alert alert-info mb-0">
                            <i class="ti ti-info-circle me-2"></i>
                            لا يمكن إضافة ردود على هذه التذكرة في حالتها الحالية
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- العمود الجانبي --}}
        <div class="col-lg-4">
            {{-- بطاقة تقييم الخدمة إذا كانت التذكرة تم حلها وبانتظار التقييم --}}
            @if ($ticket->status === 'resolved_pending_survey')
                <div class="card mb-4 shadow-sm border-warning">
                    <div class="card-header bg-warning-subtle py-3 border-bottom border-warning">
                        <h5 class="card-title mb-0 text-warning">
                            <i class="ti ti-star me-2"></i> تقييم خدمة الدعم
                        </h5>
                    </div>
                    <div class="card-body pt-3">
                        <p class="small text-muted mb-3">
                            تم حل التذكرة بنجاح من قبل فريق الدعم. نرجو منك تقييم جودة الخدمة المقدمة لإتمام إغلاق التذكرة.
                        </p>
                        <button type="button" class="btn btn-warning w-100" data-bs-toggle="modal"
                            data-bs-target="#ticketSurveyModal">
                            <i class="ti ti-star me-1"></i> قيّم التذكرة الآن
                        </button>
                    </div>
                </div>
            @endif

            {{-- بيانات ومعلومات التذكرة --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-info-circle me-2 text-info"></i> معلومات التذكرة
                    </h5>
                </div>
                <div class="card-body pt-3">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">القسم:</span>
                            <span
                                class="badge bg-primary">{{ is_array($section) ? $section['name'] ?? 'الدعم الفني' : $section->name ?? 'الدعم الفني' }}</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">الحالة:</span>
                            <span>{!! $status !!}</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">صاحب التذكرة:</span>
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
                            <span class="text-muted">تاريخ الإنشاء:</span>
                            <span
                                class="small">{{ $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at)->format('Y-m-d H:i') : '-' }}</span>
                        </li>
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">منذ:</span>
                            <span
                                class="small text-muted">{{ $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() : '-' }}</span>
                        </li>
                        @if (!empty($ticket->resolved_at))
                            <li class="mb-3 d-flex justify-content-between align-items-center">
                                <span class="text-muted">تاريخ المعالجة:</span>
                                <span
                                    class="small">{{ \Carbon\Carbon::parse($ticket->resolved_at)->format('Y-m-d H:i') }}</span>
                            </li>
                        @endif
                        @if (!empty($ticket->closed_at))
                            <li class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">تاريخ الإغلاق:</span>
                                <span
                                    class="small">{{ \Carbon\Carbon::parse($ticket->closed_at)->format('Y-m-d H:i') }}</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            @php
                $allAttachments = [];
                if (!empty($ticket->attachments) && is_iterable($ticket->attachments)) {
                    foreach ($ticket->attachments as $att) {
                        $url = is_array($att) ? $att['url'] ?? '' : (is_object($att) ? $att->url ?? '' : (string) $att);
                        $name = is_array($att)
                            ? $att['name'] ?? 'مرفق التذكرة'
                            : (is_object($att)
                                ? $att->name ?? 'مرفق التذكرة'
                                : 'مرفق التذكرة');
                        $fullUrl = Str::startsWith($url, 'http') ? $url : Storage::url($url);
                        $allAttachments[] = [
                            'name' => $name,
                            'url' => $fullUrl,
                            'source' => 'التذكرة الأصلية',
                        ];
                    }
                }
                $repliesCollection = $replies['data'] ?? (is_iterable($replies) ? $replies : []);
                if (!empty($repliesCollection) && is_iterable($repliesCollection)) {
                    foreach ($repliesCollection as $reply) {
                        $replyAtts = is_array($reply) ? $reply['attachments'] ?? [] : $reply->attachments ?? [];
                        $sender = is_array($reply) ? $reply['sender_type'] ?? '' : $reply->sender_type ?? '';
                        $sourceLabel = $sender === 'tenant_user' ? 'ردك' : 'رد الدعم الفني';
                        if (!empty($replyAtts) && is_iterable($replyAtts)) {
                            foreach ($replyAtts as $att) {
                                $url = is_array($att)
                                    ? $att['url'] ?? ''
                                    : (is_object($att)
                                        ? $att->url ?? ''
                                        : (string) $att);
                                $name = is_array($att)
                                    ? $att['name'] ?? 'مرفق رد'
                                    : (is_object($att)
                                        ? $att->name ?? 'مرفق رد'
                                        : 'مرفق رد');
                                $fullUrl = Str::startsWith($url, 'http') ? $url : Storage::url($url);
                                $allAttachments[] = [
                                    'name' => $name,
                                    'url' => $fullUrl,
                                    'source' => $sourceLabel,
                                ];
                            }
                        }
                    }
                }
            @endphp

            {{-- مرفقات التذكرة والمحادثة --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-paperclip text-warning me-2"></i> المرفقات
                    </h5>
                    @if (count($allAttachments) > 0)
                        <span class="badge bg-primary">{{ count($allAttachments) }}</span>
                    @endif
                </div>
                <div class="card-body pt-3">
                    @if (count($allAttachments) > 0)
                        <div class="assignment-file-list d-flex flex-column gap-2">
                            @foreach ($allAttachments as $attachment)
                                <x-ui.file-card
                                    :name="$attachment['name']"
                                    :url="$attachment['url']"
                                    :meta="$attachment['source']"
                                    layout="list"
                                />
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center mb-0 small">لا توجد مرفقات لهذه التذكرة.</p>
                    @endif
                </div>
            </div>

            {{-- نتيجة تقييم العميل إذا تمت مسبقاً --}}
            @if (!empty($ticket->survey_result))
                <div class="card shadow-sm border-warning mb-4">
                    <div class="card-header bg-warning-subtle py-3 border-bottom border-warning">
                        <h6 class="card-title mb-0 text-warning">
                            <i class="ti ti-star-filled me-1"></i> تقييمك للخدمة
                        </h6>
                    </div>
                    <div class="card-body pt-3">
                        <div class="mb-2">
                            <strong>هل تم حل المشكلة؟:</strong>
                            <span
                                class="badge {{ $ticket->survey_result['solved'] ?? false ? 'bg-success' : 'bg-danger' }}">
                                {{ $ticket->survey_result['solved'] ?? false ? 'نعم' : 'لا' }}
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
                            <div class="mt-2 p-2 bg-secondary rounded small">
                                <em>"{{ $ticket->survey_result['comment'] }}"</em>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var fileInput = document.getElementById('attachments');
            var triggerBtn = document.getElementById('btn-trigger-attachments');
            var statusText = document.getElementById('attachment-status-text');
            var countLabel = document.getElementById('attachment-count-label');
            var clearBtn = document.getElementById('btn-clear-attachments');

            if (triggerBtn && fileInput) {
                triggerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    fileInput.click();
                });
            }

            if (fileInput) {
                fileInput.addEventListener('change', function() {
                    var count = this.files ? this.files.length : 0;
                    if (count > 0) {
                        countLabel.textContent = 'تم إرفاق ' + count + ' ملف';
                        statusText.classList.remove('d-none');
                        statusText.classList.add('d-inline-flex');
                    } else {
                        countLabel.textContent = '';
                        statusText.classList.add('d-none');
                        statusText.classList.remove('d-inline-flex');
                    }
                });
            }

            if (clearBtn && fileInput) {
                clearBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    fileInput.value = '';
                    fileInput.dispatchEvent(new Event('change'));
                });
            }
        });
    </script>
@endsection
