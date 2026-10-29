@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل التذكرة #' . $ticket->ticket_number)



@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
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
            <a href="{{ route('admin.tickets.index') }}" class="btn btn-secondary">
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
        <div class="col-lg-8">
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
                    <div class="p-3 bg-secondary rounded mb-3"
                        style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6;">{{ $ticket->description }}
                    </div>

                    @if (!empty($ticket->attachments) && count($ticket->attachments) > 0)
                        <div class="mt-3">
                            <h6 class="small text-muted mb-2"><i class="ti ti-paperclip me-1"></i> المرفقات:</h6>
                            <div class="d-flex flex-wrap gap-3">
                                @foreach ($ticket->attachments as $file)
                                    <x-ui.file-card
                                        :name="$file['name'] ?? 'مرفق'"
                                        :url="Storage::url($file['url'] ?? $file)"
                                    />
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </div>

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
                                <div class="card border {{ $isAdmin ? 'bg-primary-subtle border-primary' : 'bg-secondary border-secondary' }} shadow-none"
                                    style="max-width: 80%;">
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
                                        <div class="text-dark" style="white-space: pre-wrap; line-height: 1.5;">
                                            {{ $reply->message }}</div>

                                        @if (!empty($reply->attachments) && count($reply->attachments) > 0)
                                            <div class="mt-2 pt-2 border-top">
                                                <div class="d-flex flex-wrap gap-3">
                                                    @foreach ($reply->attachments as $att)
                                                        <x-ui.file-card
                                                            :name="$att['name'] ?? 'مرفق'"
                                                            :url="Storage::url($att['url'] ?? $att)"
                                                        />
                                                    @endforeach
                                                </div>
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
                    <form action="{{ route('admin.tickets.reply', $ticket->ticket_number) }}" method="POST"
                        enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold" for="replyMessage">إضافة رد على التذكرة:</label>
                            <div class="position-relative border rounded @error('message') border-danger @enderror"
                                style="background-color: var(--bs-paper-bg, var(--bs-card-bg, #fff));">
                                <textarea class="form-control border-0 shadow-none" id="replyMessage" name="message" rows="4"
                                    placeholder="اكتب رد الدعم الفني هنا..."
                                    style="resize: vertical; min-height: 140px; margin-bottom: 3rem; background: transparent;" required>{{ old('message') }}</textarea>

                                {{-- شريط المرفقات المدمج داخل الـ textarea --}}
                                <div class="position-absolute bottom-0 start-0 end-0 px-3 py-2 border-top d-flex justify-content-between align-items-center rounded-bottom"
                                    dir="rtl"
                                    style="z-index: 5; height: 3rem; background-color: var(--bs-paper-bg, var(--bs-card-bg, #fff));">
                                    {{-- النص ع اليمين --}}
                                    <div id="attachment-status-container" class="d-flex align-items-center">
                                        <span id="attachment-status-text"
                                            class="badge bg-primary small d-none align-items-center">
                                            <i class="ti ti-paperclip me-1"></i> <span id="attachment-count-label"></span>
                                            <i class="ti ti-x ms-2 cursor-pointer" id="btn-clear-attachments"
                                                title="إلغاء المرفقات"></i>
                                        </span>
                                    </div>

                                    {{-- الـ icon ع الشمال --}}
                                    <div class="d-flex align-items-center">
                                        <input type="file" id="attachments" name="attachments[]" multiple class="d-none"
                                            accept="image/*,application/pdf,.doc,.docx">
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
                </div>
            </div>
        </div>

        <div class="col-lg-4">
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
                                <x-form.controls.enum-options :enum="\App\Enums\Tenants\Tickets\TicketStatus::class" :selected="`$ticket->status" />
                            </select>
                        </div>
                        <button type="submit" class="btn btn-outline-warning w-100">
                            <i class="ti ti-check me-1"></i> تحديث الحالة
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0"><i class="ti ti-user me-2 text-info"></i> بيانات المستأجر</h5>
                </div>
                <div class="card-body pt-3">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex justify-content-between align-items-center">
                            <span class="text-muted">المعرف (Tenant ID):</span>
                            <span class="badge bg-secondary">{{ $ticket->tenant_id }}</span>
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
                            <span class="badge bg-primary">{{ $ticket->section?->name ?? 'الدعم الفني' }}</span>
                        </li>
                        <li class="d-flex justify-content-between align-items-center">
                            <span class="text-muted">تاريخ الإنشاء:</span>
                            <span class="small">{{ $ticket->created_at?->format('Y-m-d H:i') }}</span>
                        </li>
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
                        $sourceLabel = in_array($sender, ['support_user', 'admin']) ? 'رد الدعم الفني' : 'رد المستأجر';
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

            {{-- كارت المرفقات الخارجي --}}
            <div class="card mb-4 shadow-sm">
                <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-paperclip text-primary me-2"></i> المرفقات
                    </h5>
                    @if (count($allAttachments) > 0)
                        <span class="badge bg-primary">{{ count($allAttachments) }}</span>
                    @endif
                </div>
                <div class="card-body pt-3">
                    @if (count($allAttachments) > 0)
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($allAttachments as $attachment)
                                <x-ui.file-card
                                    :name="$attachment['name']"
                                    :url="$attachment['url']"
                                    :meta="$attachment['source']"
                                />
                            @endforeach
                        </div>
                    @else
                        <p class="text-muted text-center mb-0 small">لا توجد مرفقات لهذه التذكرة.</p>
                    @endif
                </div>
            </div>

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
