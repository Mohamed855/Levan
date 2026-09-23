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
                                                <span class="survey-star" data-value="{{ $i }}">
                                                    ★</span>
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

    <div class="row g-4">
        <div class="col-lg-8">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card mb-4">
                <div class="card-header py-4 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-ticket text-warning me-2"></i>
                        <small class="text-muted">التذكرة #{{ $ticket->ticket_number }} - {{ $ticket->title }}</small>
                    </h6>

                    <div>
                        {!! $status !!}
                        @if ($ticket->status === 'resolved_pending_survey')
                            <div class="my-1 text-center">
                                <button type="button" class="btn btn-outline-warning btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#ticketSurveyModal">
                                    قيّم التذكرة الآن <i class="ti ti-star mx-1"></i>
                                </button>
                            </div>
                        @endif
                    </div>

                </div>
                <div class="border-1 border-light border-dashed mb-2"></div>
                <div class="card-body px-5">
                    <div class="table-responsive">
                        <table class="table table-striped small text-center">
                            <tr>
                                <td colspan="5">
                                    <p>الوصف</p>
                                    <p class="text-danger fs-6 lh-lg">
                                        {{ $ticket->description }}
                                    </p>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-bubble text-warning me-2"></i>
                        <small class="text-muted">الردود</small>
                    </h6>
                </div>
                <div class="border-1 border-light border-dashed mb-2"></div>
                <div class="card-body">

                    @if (in_array($ticket->status, ['open', 'in_progress']))
                        <div>
                            <h6 class="mb-3">إضافة رد</h6>
                            <form action="{{ tenant_route_name('tickets.reply', $ticket->ticket_number) }}" method="POST"
                                enctype="multipart/form-data">
                                @csrf
                                <div class="mb-3">
                                    <textarea name="message" class="form-control @error('message') is-invalid @enderror" rows="4"
                                        placeholder="اكتب ردك هنا..." required></textarea>
                                    @error('message')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">إرفاق ملفات (اختياري)</label>
                                    <input type="file" name="attachments[]"
                                        class="form-control @error('attachments.*') is-invalid @enderror" multiple
                                        accept="image/*,application/pdf,.doc,.docx">
                                    <small class="text-muted">يمكنك إرفاق صور أو ملفات PDF أو Word</small>
                                    @error('attachments.*')
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
                    @else
                        <div class="alert alert-info">
                            <i class="ti ti-info-circle me-2"></i>
                            لا يمكن إضافة ردود على هذه التذكرة في حالتها الحالية
                        </div>
                    @endif

                    {{-- قائمة الردود --}}
                    <div class="chat-container">
                        @forelse($replies['data'] as $reply)
                            <div
                                class="chat-message {{ $reply['sender_type'] === 'tenant_user' ? 'chat-right' : 'chat-left' }}">

                                <div class="chat-meta mb-3 d-flex justify-content-between">
                                    <strong>
                                        {{ $reply['sender_type'] === 'support_user'
                                            ? $reply['support_user_name'] ?? 'موظف الدعم'
                                            : $ticket->customer_name }}
                                    </strong>
                                    <span>{{ \Carbon\Carbon::parse($reply['created_at'])->diffForHumans() }}</span>
                                </div>

                                <div class="chat-text mb-1 mx-3">
                                    {{ $reply['message'] }}
                                </div>

                                {{-- المرفقات --}}
                                @if (count($reply['attachments']) > 0)
                                    <div class="mt-2">
                                        @foreach ($reply['attachments'] as $index => $attachment)
                                            <a href="{{ Storage::url($attachment['url']) }}" target="_blank"
                                                class="attachment-item d-block">
                                                <i class="ti ti-paperclip me-1"></i>
                                                مرفق
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                {{-- تقييم الردود (فقط لردود الدعم الفني) --}}
                                @if ($reply['sender_type'] === 'support_user')
                                    @php
                                        $currentStars = $reply['rating']['rating'] ?? null;
                                    @endphp

                                    <form method="POST" class="m-0"
                                        action="{{ tenant_route_name('tickets.replies.rate', ['ticket' => $ticket->ticket_number, 'reply' => $reply['id']]) }}">
                                        @csrf
                                        <div class="d-flex justify-content-center mt-2">
                                            <div class="reply-rating">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <button type="submit" name="stars" value="{{ $i }}"
                                                        class="reply-star-btn {{ $currentStars && $i <= $currentStars ? 'rated' : '' }}">
                                                        ★
                                                    </button>
                                                @endfor
                                            </div>
                                        </div>
                                    </form>
                                @endif

                            </div>
                        @empty
                            <p class="text-center text-muted my-4">
                                لا توجد ردود حتى الآن
                            </p>
                        @endforelse
                    </div>

                </div>
            </div>
        </div>

        {{-- العمود الجانبي --}}
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header py-4 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#additionalInfo">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-info-circle text-warning me-2"></i>
                            <small class="text-muted">معلومات إضافية</small>
                        </h6>
                        <i class="ti ti-chevron-down"></i>
                    </div>
                </div>

                <div id="additionalInfo" class="collapse show">
                    <div class="border-1 border-light border-dashed mb-2"></div>
                    <div class="card-body px-5">
                        <div class="table-responsive">
                            <table class="table table-striped small text-center">
                                <tr>
                                    <td class="fw-bold">القسم</td>
                                    <td>{{ $section['name'] }}</td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">الحالة</td>
                                    <td>{!! $status !!}</td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">تاريخ الإنشاء</td>
                                    <td>{{ \Carbon\Carbon::parse($ticket->created_at)->format('Y-m-d H:i') }}</td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">منذ</td>
                                    <td>{{ \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() }}</td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">تاريخ المعالجة</td>
                                    <td>
                                        {{ $ticket->resolved_at ? \Carbon\Carbon::parse($ticket->resolved_at)->diffForHumans() : 'في انتظار المعالجة' }}
                                    </td>
                                </tr>

                                <tr>
                                    <td class="fw-bold">تاريخ الإغلاق</td>
                                    <td>
                                        {{ $ticket->closed_at ? \Carbon\Carbon::parse($ticket->closed_at)->diffForHumans() : 'غير محدد' }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            {{-- مرفقات التذكرة --}}
            <div class="card mb-4">
                <div class="card-header py-4 cursor-pointer" data-bs-toggle="collapse" data-bs-target="#attachmentsInfo">
                    <div class="d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-paperclip text-warning me-2"></i>
                            <small class="text-muted">المرفقات</small>
                        </h6>
                        <i class="ti ti-chevron-down"></i>
                    </div>
                </div>

                <div id="attachmentsInfo" class="collapse show">
                    <div class="border-1 border-light border-dashed mb-2"></div>
                    <div class="card-body px-5">
                        <div class="table-responsive small text-center">
                            @if ($ticket->attachments > 0)
                                <div class="mb-3">
                                    <div>
                                        @foreach ($ticket->attachments as $index => $attachment)
                                            <a href="{{ $attachment['url'] }}" target="_blank"
                                                class="attachment-item d-block">
                                                <i class="ti ti-paperclip me-1"></i>
                                                مرفق
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @else
                                <p class="text-muted">لا توجد مرفقات لهذه التذكرة.</p>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
