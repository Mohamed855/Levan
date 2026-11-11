@php
    $isAdmin = $isAdmin ?? false;
    $repliesList = $replies['data'] ?? (is_iterable($replies) ? $replies : []);
    $repliesCount = is_countable($repliesList) ? count($repliesList) : 0;

    $normalizeAttachments = static function ($items, string $defaultName, ?string $source = null): array {
        $result = [];
        if (empty($items) || !is_iterable($items)) {
            return $result;
        }
        foreach ($items as $att) {
            $url = is_array($att) ? $att['url'] ?? '' : (is_object($att) ? $att->url ?? '' : (string) $att);
            $name = is_array($att)
                ? $att['name'] ?? $defaultName
                : (is_object($att)
                    ? $att->name ?? $defaultName
                    : $defaultName);
            $fullUrl = \Illuminate\Support\Str::startsWith($url, 'http')
                ? $url
                : \Illuminate\Support\Facades\Storage::url($url);
            $entry = [
                'name' => $name,
                'url' => $fullUrl,
            ];
            if ($source !== null) {
                $entry['source'] = $source;
            }
            $result[] = $entry;
        }

        return $result;
    };

    $ticketAttachments = $normalizeAttachments($ticket->attachments ?? [], 'مرفق');
    $allAttachments = $normalizeAttachments($ticket->attachments ?? [], 'مرفق التذكرة', 'التذكرة الأصلية');

    if (!empty($repliesList) && is_iterable($repliesList)) {
        foreach ($repliesList as $reply) {
            $replyAtts = is_array($reply) ? $reply['attachments'] ?? [] : $reply->attachments ?? [];
            $sender = is_array($reply) ? $reply['sender_type'] ?? '' : $reply->sender_type ?? '';
            $sourceLabel = $isAdmin
                ? (in_array($sender, ['support_user', 'admin'], true)
                    ? 'رد الدعم الفني'
                    : 'رد المستأجر')
                : ($sender === 'tenant_user'
                    ? 'ردك'
                    : 'رد الدعم الفني');

            $allAttachments = array_merge($allAttachments, $normalizeAttachments($replyAtts, 'مرفق رد', $sourceLabel));
        }
    }

    $ticketStatusValue = $ticket->status instanceof \BackedEnum ? $ticket->status->value : (string) $ticket->status;
    $canReply = $isAdmin || in_array($ticketStatusValue, ['open', 'in_progress', 'reopened'], true);
@endphp

<div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $backUrl }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة
        </a>
        @if (!$isAdmin && $ticketStatusValue === 'resolved_pending_survey')
            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#ticketSurveyModal">
                <i class="ti ti-star me-1"></i> قيّم التذكرة الآن
            </button>
        @endif
    </div>
</div>

<div class="row">
    {{-- بطاقة ملخص التذكرة ومعلوماتها والمرفقات (العمود الأيمن) --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mx-auto mb-3">
                    <div class="avatar avatar-xl bg-label-primary rounded-circle p-3 d-flex align-items-center justify-content-center mx-auto shadow-sm"
                        style="width: 100px; height: 100px;">
                        <i class="ti ti-ticket fs-1 text-primary"></i>
                    </div>
                </div>

                <h4 class="mb-1 fw-bold text-primary">#{{ $ticket->ticket_number }}</h4>
                <p class="mb-2 text-muted fw-medium">{{ $ticket->title }}</p>

                <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                    {!! $status !!}
                    <span class="badge bg-label-primary fs-6">{{ $sectionName }}</span>
                </div>

                <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                    <div>
                        <h5 class="mb-0">{{ $repliesCount }}</h5>
                        <small class="text-muted">الردود</small>
                    </div>
                    <div>
                        <h5 class="mb-0 text-primary">{{ count($allAttachments) }}</h5>
                        <small class="text-muted">المرفقات</small>
                    </div>
                </div>

                <div class="info-container text-start">
                    <ul class="list-unstyled mb-0">
                        @if ($isAdmin)
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-hash text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المعرف (Tenant ID):</span>
                                <span class="badge bg-label-secondary">{{ $ticket->tenant_id }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-building text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المكتب / الشركة:</span>
                                <span>{{ $ticket->tenant?->company_name ?? 'مستأجر' }}</span>
                            </li>
                        @endif
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-user text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">صاحب التذكرة:</span>
                            <span>{{ $ticket->customer_name ?? '-' }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-mail text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">البريد الإلكتروني:</span>
                            <span class="text-break">{{ $ticket->customer_email ?? '-' }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-phone text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">رقم الهاتف:</span>
                            <span dir="ltr">{{ $ticket->customer_phone ?? '-' }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-category text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">القسم:</span>
                            <span class="badge bg-label-primary">{{ $sectionName }}</span>
                        </li>
                        <li
                            class="{{ !$isAdmin || !empty($ticket->resolved_at) || !empty($ticket->closed_at) ? 'mb-3' : 'mb-2' }} d-flex align-items-center">
                            <i class="ti ti-calendar-plus text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">تاريخ الإنشاء:</span>
                            <span>@datetime($ticket->created_at)</span>
                        </li>
                        @if (!$isAdmin)
                            <li
                                class="{{ !empty($ticket->resolved_at) || !empty($ticket->closed_at) ? 'mb-3' : 'mb-2' }} d-flex align-items-center">
                                <i class="ti ti-clock text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">منذ:</span>
                                <span>{{ $ticket->created_at ? \Carbon\Carbon::parse($ticket->created_at)->diffForHumans() : '-' }}</span>
                            </li>
                        @endif
                        @if (!empty($ticket->resolved_at))
                            <li class="{{ !empty($ticket->closed_at) ? 'mb-3' : 'mb-2' }} d-flex align-items-center">
                                <i class="ti ti-calendar-check text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ المعالجة:</span>
                                <span>@datetime($ticket->resolved_at)</span>
                            </li>
                        @endif
                        @if (!empty($ticket->closed_at))
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-calendar-x text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ الإغلاق:</span>
                                <span>@datetime($ticket->closed_at)</span>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>
        </div>

        @if ($isAdmin)
            <div class="card mb-4">
                <div class="card-header pb-2 mb-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-adjustments text-primary me-2"></i>الحالة
                    </h6>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.tickets.updateStatus', $ticket->ticket_number) }}" method="POST">
                        @csrf
                        <x-form.controls.select
                            id="ticketStatusSelect"
                            name="status"
                            :enum="\App\Enums\Tenants\Tickets\TicketStatus::class"
                            :value="$ticketStatusValue"
                            :select2="true"
                            :required="true"
                            col="mb-3"
                            data-select2-search="false"
                            data-placeholder="اختر الحالة"
                        />
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="ti ti-check me-1"></i> تحديث الحالة
                        </button>
                    </form>
                </div>
            </div>
        @elseif ($ticketStatusValue === 'resolved_pending_survey')
            <div class="card mb-4 border-warning-subtle">
                <div class="card-header bg-label-warning pb-2 mb-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-star me-2"></i>تقييم خدمة الدعم
                    </h6>
                </div>
                <div class="card-body">
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

        {{-- نتيجة التقييم --}}
        @if (!empty($ticket->survey_result))
            <div class="card border-warning-subtle">
                <div class="card-header bg-label-warning pb-2 mb-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-star-filled me-2"></i>{{ $isAdmin ? 'نتيجة تقييم العميل' : 'تقييمك للخدمة' }}
                    </h6>
                </div>
                <div class="card-body">
                    <div class="mb-2 d-flex justify-content-between align-items-center">
                        <span class="fw-bold">هل تم حل المشكلة؟</span>
                        <span
                            class="badge bg-label-{{ $ticket->survey_result['solved'] ?? false ? 'success' : 'danger' }}">
                            {{ $ticket->survey_result['solved'] ?? false ? 'نعم' : 'لا' }}
                        </span>
                    </div>
                    @if (isset($ticket->survey_result['agent_rating']))
                        <div class="mb-1 small d-flex justify-content-between">
                            <span class="text-muted">تقييم موظف الدعم:</span>
                            <span class="text-warning fw-semibold">★
                                {{ $ticket->survey_result['agent_rating'] }}/5</span>
                        </div>
                    @endif
                    @if (isset($ticket->survey_result['system_rating']))
                        <div class="mb-1 small d-flex justify-content-between">
                            <span class="text-muted">تقييم النظام:</span>
                            <span class="text-warning fw-semibold">★
                                {{ $ticket->survey_result['system_rating'] }}/5</span>
                        </div>
                    @endif
                    @if (isset($ticket->survey_result['solution_rating']))
                        <div class="mb-1 small d-flex justify-content-between">
                            <span class="text-muted">جودة الحل:</span>
                            <span class="text-warning fw-semibold">★
                                {{ $ticket->survey_result['solution_rating'] }}/5</span>
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

    {{-- تفاصيل التذكرة والمحادثة والردود (العمود الأيسر) --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        {{-- تفاصيل التذكرة --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-file-text text-primary me-2"></i>تفاصيل التذكرة
                </h5>
                {!! $status !!}
            </div>
            <div class="card-body">
                <h6 class="fw-bold mb-2">{{ $ticket->title }}</h6>
                <div class="p-3 bg-secondary rounded mb-3">
                    {{ $ticket->description }}</div>

                @if (count($ticketAttachments) > 0)
                    <div class="mt-3">
                        <h6 class="small text-muted mb-2"><i class="ti ti-paperclip me-1"></i> المرفقات:</h6>
                        <div class="d-flex flex-wrap gap-3">
                            @foreach ($ticketAttachments as $file)
                                <x-ui.file-card :name="$file['name']" :url="$file['url']" />
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>

        {{-- المحادثة والردود --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-messages text-primary me-2"></i>المحادثة والردود ({{ $repliesCount }})
                </h5>
                @if ($repliesCount > 0)
                    <span class="badge bg-label-primary">{{ $repliesCount }} رد</span>
                @endif
            </div>
            <div class="card-body">
                <div class="chat-history-wrapper overflow-auto pe-1" style="max-height: 550px;">
                    @forelse($repliesList as $reply)
                        @php
                            $senderType = is_array($reply) ? $reply['sender_type'] ?? '' : $reply->sender_type ?? '';
                            $isSupportSender = in_array($senderType, ['support_user', 'admin'], true);
                            $isOwnMessage = $isAdmin ? $isSupportSender : $senderType === 'tenant_user';

                            $message = is_array($reply) ? $reply['message'] ?? '' : $reply->message ?? '';
                            $createdAt = is_array($reply) ? $reply['created_at'] ?? '' : $reply->created_at ?? '';
                            $supportName = is_array($reply)
                                ? $reply['support_user_name'] ??
                                    ($reply['user_name'] ?? ($isAdmin ? 'الإدارة' : 'موظف الدعم'))
                                : $reply->support_user_name ??
                                    ($reply->user_name ?? ($isAdmin ? 'الإدارة' : 'موظف الدعم'));

                            $replyAttachmentsRaw = is_array($reply)
                                ? $reply['attachments'] ?? []
                                : $reply->attachments ?? [];
                            $replyAttachments = $normalizeAttachments($replyAttachmentsRaw, 'مرفق');

                            $replyRating = is_array($reply)
                                ? $reply['rating']['rating'] ?? ($reply['rating'] ?? null)
                                : $reply->rating->rating ?? ($reply->rating ?? null);
                            $replyId = is_array($reply) ? $reply['id'] ?? null : $reply->id ?? null;

                            $senderLabel = $isSupportSender
                                ? 'فريق الدعم (' . $supportName . ')'
                                : ($isAdmin
                                    ? 'المستأجر (' . ($ticket->customer_name ?? 'العميل') . ')'
                                    : 'أنت (' . ($ticket->customer_name ?? 'المستأجر') . ')');
                        @endphp

                        <div
                            class="d-flex {{ $isOwnMessage ? 'justify-content-start' : 'justify-content-end' }} mb-4">
                            <div class="card border {{ $isOwnMessage ? 'bg-primary-subtle border-primary-subtle' : 'bg-secondary-subtle border-secondary-subtle' }}"
                                style="max-width: 80%;">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2 gap-3">
                                        <span
                                            class="badge {{ $isOwnMessage ? 'bg-label-primary' : 'bg-label-secondary' }}">
                                            {{ $senderLabel }}
                                        </span>
                                        <small class="text-muted">
                                            {{ $createdAt ? \Carbon\Carbon::parse($createdAt)->diffForHumans() : '' }}
                                        </small>
                                    </div>
                                    @if (filled($message))
                                        <div style="white-space: pre-wrap; line-height: 1.5;">{{ $message }}</div>
                                    @endif

                                    @if (count($replyAttachments) > 0)
                                        <div class="{{ filled($message) ? 'mt-2 pt-2 border-top' : '' }}">
                                            <div class="d-flex flex-wrap gap-3">
                                                @foreach ($replyAttachments as $att)
                                                    <x-ui.file-card :name="$att['name']" :url="$att['url']" />
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    @if ($isAdmin && $replyRating)
                                        <div class="mt-2 text-warning small">
                                            <span>تقييم العميل للرد:</span>
                                            @for ($i = 1; $i <= 5; $i++)
                                                <span>{{ $i <= $replyRating ? '★' : '☆' }}</span>
                                            @endfor
                                        </div>
                                    @elseif (!$isAdmin && !$isOwnMessage && $replyId)
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
                @if ($canReply)
                    <form id="ticket-reply-form" action="{{ $replyAction }}" method="POST" enctype="multipart/form-data"
                        @if (!$isAdmin) novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}" @endif>
                        @csrf
                        <div class="mb-3">
                            <div @if (!$isAdmin) data-form-field data-field-name="message" @endif>
                                <label class="form-label fw-bold" for="replyMessage">إضافة رد على التذكرة:</label>
                                <div class="ticket-reply-composer @error('message') border-danger @enderror">
                                    <textarea class="form-control border-0 shadow-none" id="replyMessage" name="message"
                                        @if (!$isAdmin) data-form-control data-validation-dependents="attachments[]" @endif
                                        rows="4"
                                        placeholder="{{ $isAdmin ? 'اكتب رد الدعم الفني هنا...' : 'اكتب ردك هنا...' }}">{{ old('message') }}</textarea>

                                    {{-- شريط المرفقات المدمج داخل صندوق الرد --}}
                                    <div class="ticket-reply-composer-toolbar px-3 py-2 border-top d-flex justify-content-between align-items-center rounded-bottom"
                                        dir="rtl">
                                        <div id="attachment-status-container" class="dropdown d-flex align-items-center">
                                            <button type="button" id="attachment-status-text"
                                                class="btn btn-sm badge bg-label-primary small d-none align-items-center gap-1 border-0"
                                                data-bs-toggle="dropdown" data-bs-auto-close="outside"
                                                aria-expanded="false" aria-controls="ticket-attachments-dropdown">
                                                <i class="ti ti-paperclip"></i>
                                                <span id="attachment-count-label"></span>
                                                <i class="ti ti-chevron-down"></i>
                                                <i class="ti ti-x ms-1 cursor-pointer" id="btn-clear-attachments"
                                                    title="إلغاء جميع المرفقات"></i>
                                            </button>

                                            <div class="dropdown-menu dropdown-menu-start ticket-attachments-dropdown-menu"
                                                id="ticket-attachments-dropdown" aria-labelledby="attachment-status-text">
                                                <ul id="ticket-attachments-list" class="list-unstyled mb-0"></ul>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center">
                                            <button type="button" id="btn-trigger-attachments"
                                                class="btn btn-icon mb-0 cursor-pointer" title="إرفاق ملفات">
                                                <i class="ti ti-paperclip fs-5"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div id="replyMessage-error" class="invalid-feedback d-block" data-form-error role="alert"
                                    @if (! $errors->has('message')) hidden @endif>
                                    {{ $errors->first('message') }}
                                </div>
                            </div>
                            <div @if (!$isAdmin) data-form-field data-field-name="attachments[]" @endif>
                                <input type="file" id="attachments" name="attachments[]"
                                    @if (!$isAdmin) data-form-control data-validation-dependents="message" @endif
                                    multiple class="d-none" accept="image/*,application/pdf,.doc,.docx">
                                <div id="attachments-error" class="invalid-feedback d-block" data-form-error role="alert"
                                    @if (! $errors->has('attachments') && ! $errors->has('attachments.*')) hidden @endif>
                                    {{ $errors->first('attachments') ?: $errors->first('attachments.*') }}
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-send me-1"></i> إرسال الرد
                            </button>
                        </div>
                    </form>
                @else
                    <div class="alert alert-info mb-0">
                        <i class="ti ti-info-circle"></i>
                        <span>
                            لا يمكن إضافة ردود على هذه التذكرة في حالتها الحالية
                        </span>
                    </div>
                @endif
            </div>
        </div>

        {{-- كارت المرفقات الشامل --}}
        @php
            $ticketFileGroups = collect($allAttachments)
                ->groupBy(fn (array $att) => $att['source'] ?: 'مرفقات التذكرة')
                ->map(fn ($items, string $source) => [
                    'label' => $source,
                    'items' => $items->values()->all(),
                ])
                ->values()
                ->all();
        @endphp
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-paperclip text-primary me-2"></i>المرفقات
                </h5>
                @if (count($allAttachments) > 0)
                    <span class="badge bg-label-primary">{{ count($allAttachments) }}</span>
                @endif
            </div>
            <x-ui.file-groups :groups="$ticketFileGroups" empty-text="لا توجد مرفقات لهذه التذكرة." />
        </div>
    </div>
</div>
