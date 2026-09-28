@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل التذكرة #' . $ticket->ticket_number)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/css/tenants/tickets/show.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/js/tenants/tickets/show.js'])

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
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    {{-- مودال تقييم التذكرة --}}
    <div class="modal fade" id="ticketSurveyModal" tabindex="-1" aria-labelledby="ticketSurveyModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="ticketSurveyModalLabel">
                        <i class="ti ti-star text-warning me-2"></i>
                        تقييم التذكرة #{{ $ticket->ticket_number }}
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>

                <form action="{{ tenant_route_name('tickets.survey.submit', $ticket->ticket_number) }}" method="POST">
                    @csrf

                    <div class="modal-body text-center">
                        <p class="mb-3 fw-bold fs-5">
                            شاركنا تجربتك! هل تم حل مشكلتك بنجاح؟
                        </p>
                        <div class="d-flex justify-content-center gap-3 mb-4 choice-buttons">
                            <input type="radio" class="btn-check" name="solved" id="solvedYes" value="1"
                                autocomplete="off" required {{ old('solved') == '1' ? 'checked' : '' }}>
                            <label class="btn btn-outline-success" for="solvedYes">
                                نعم، تم الحل
                            </label>

                            <input type="radio" class="btn-check" name="solved" id="solvedNo" value="0"
                                autocomplete="off" required {{ old('solved') == '0' ? 'checked' : '' }}>
                            <label class="btn btn-outline-danger" for="solvedNo">
                                لا، لم تُحل
                            </label>
                        </div>

                        <div id="ratingSection" style="display: {{ old('solved') == '1' ? 'block' : 'none' }};">
                            <hr class="my-4">

                            @foreach ($surveyQuestions as $field => $label)
                                <div class="my-4">
                                    <label class="form-label d-block mb-2 fw-semibold">{{ $label }}</label>

                                    <input type="hidden" name="{{ $field }}" value="{{ old($field) }}" />

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
                            <textarea name="comment" class="form-control" rows="3" placeholder="شاركنا رأيك أو أي ملاحظات لديك...">{{ old('comment') }}</textarea>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary px-4" id="submitSurveyBtn">
                            <i class="ti ti-send me-1"></i> إرسال التقييم
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>

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
            <a href="{{ tenant_route_name('tickets.index') }}" class="btn btn-label-secondary">
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
                    <div class="p-3 bg-lighter rounded mb-3 text-secondary"
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
                                <div class="card border {{ $isTenant ? 'bg-primary-subtle border-primary' : 'bg-light border-secondary' }} shadow-none"
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
                                                        class="badge bg-label-info text-decoration-none me-1">
                                                        <i class="ti ti-paperclip me-1"></i> {{ $attName }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif

                                        {{-- تقييم الردود (فقط لردود الدعم الفني) --}}
                                        @if (!$isTenant && $replyId)
                                            <form method="POST" class="m-0"
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
                            enctype="multipart/form-data">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label fw-bold" for="replyMessage">إضافة رد على التذكرة:</label>
                                <textarea class="form-control @error('message') is-invalid @enderror" id="replyMessage" name="message"
                                    rows="4" placeholder="اكتب ردك هنا..." required>{{ old('message') }}</textarea>
                                @error('message')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="row align-items-center">
                                <div class="col-md-8 mb-2 mb-md-0">
                                    <label class="form-label small text-muted mb-1" for="attachments">مرفقات
                                        (اختياري):</label>
                                    <input type="file"
                                        class="form-control form-control-sm @error('attachments.*') is-invalid @enderror"
                                        id="attachments" name="attachments[]" multiple
                                        accept="image/*,application/pdf,.doc,.docx">
                                    <small class="text-muted d-block mt-1">يمكنك إرفاق صور أو ملفات PDF أو Word</small>
                                    @error('attachments.*')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4 text-md-end">
                                    <button type="submit" class="btn btn-primary mt-3 mt-md-0">
                                        <i class="ti ti-send me-1"></i> إرسال الرد
                                    </button>
                                </div>
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
                                class="badge bg-label-primary">{{ is_array($section) ? $section['name'] ?? 'الدعم الفني' : $section->name ?? 'الدعم الفني' }}</span>
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
                        $url = is_array($att) ? ($att['url'] ?? '') : (is_object($att) ? ($att->url ?? '') : (string)$att);
                        $name = is_array($att) ? ($att['name'] ?? 'مرفق التذكرة') : (is_object($att) ? ($att->name ?? 'مرفق التذكرة') : 'مرفق التذكرة');
                        $fullUrl = Str::startsWith($url, 'http') ? $url : Storage::url($url);
                        $allAttachments[] = [
                            'name'   => $name,
                            'url'    => $fullUrl,
                            'source' => 'التذكرة الأصلية',
                        ];
                    }
                }
                $repliesCollection = $replies['data'] ?? (is_iterable($replies) ? $replies : []);
                if (!empty($repliesCollection) && is_iterable($repliesCollection)) {
                    foreach ($repliesCollection as $reply) {
                        $replyAtts = is_array($reply) ? ($reply['attachments'] ?? []) : ($reply->attachments ?? []);
                        $sender = is_array($reply) ? ($reply['sender_type'] ?? '') : ($reply->sender_type ?? '');
                        $sourceLabel = $sender === 'tenant_user' ? 'ردك' : 'رد الدعم الفني';
                        if (!empty($replyAtts) && is_iterable($replyAtts)) {
                            foreach ($replyAtts as $att) {
                                $url = is_array($att) ? ($att['url'] ?? '') : (is_object($att) ? ($att->url ?? '') : (string)$att);
                                $name = is_array($att) ? ($att['name'] ?? 'مرفق رد') : (is_object($att) ? ($att->name ?? 'مرفق رد') : 'مرفق رد');
                                $fullUrl = Str::startsWith($url, 'http') ? $url : Storage::url($url);
                                $allAttachments[] = [
                                    'name'   => $name,
                                    'url'    => $fullUrl,
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
                        <span class="badge bg-label-primary">{{ count($allAttachments) }}</span>
                    @endif
                </div>
                <div class="card-body pt-3">
                    @if (count($allAttachments) > 0)
                        <div class="d-flex flex-column gap-2">
                            @foreach ($allAttachments as $attachment)
                                <a href="{{ $attachment['url'] }}" target="_blank"
                                    class="d-flex align-items-center justify-content-between p-2 border rounded text-decoration-none text-body bg-light">
                                    <div class="d-flex align-items-center text-truncate me-2">
                                        <i class="ti ti-paperclip text-warning me-2"></i>
                                        <div class="d-flex flex-column text-truncate">
                                            <span class="small fw-semibold text-truncate">{{ $attachment['name'] }}</span>
                                            <small class="text-muted" style="font-size: 0.75rem;">{{ $attachment['source'] }}</small>
                                        </div>
                                    </div>
                                    <i class="ti ti-download text-muted"></i>
                                </a>
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
