@php
    $comments = $comments ?? collect();
@endphp

@if ($comments->count() > 0)
    @foreach ($comments as $comment)
        <div class="comment-item border rounded p-3 mb-3 shadow-sm" data-comment-id="{{ $comment->id }}">

            {{-- رأس التعليق --}}
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div class="d-flex align-items-center gap-2 flex-grow-1">
                    <div class="avatar avatar-sm">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ Str::upper(Str::substr($comment->user->name, 0, 1)) }}
                        </span>
                    </div>
                    <div>
                        <h6 class="mb-0 fw-semibold">{{ $comment->user->name }}</h6>
                        <small class="text-muted">
                            <i class="ti ti-clock me-1"></i>{{ $comment->created_at->diffForHumans() }}
                        </small>
                    </div>
                </div>
                <button type="button" class="btn btn-sm btn-icon btn-outline-danger delete-comment-btn"
                    title="حذف التعليق">
                    <i class="ti ti-trash"></i>
                </button>
            </div>

            {{-- محتوى التعليق --}}
            <div class="comment-content">
                <p class="mb-0">{!! $comment->parsed_comment_text !!}</p>
            </div>

            {{-- قائمة المنشنات --}}
            @if ($comment->mentions->count() > 0)
                <div class="mt-3 pt-2 border-top">
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <small class="text-muted">
                            <i class="ti ti-users me-1"></i>تمت الإشارة إلى:
                        </small>
                        @foreach ($comment->mentions as $mention)
                            <span class="badge bg-label-info">
                                {{ $mention->mentionedUser->name }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>
    @endforeach
@else
    <div class="text-center py-5" id="no-comments-message">
        <div class="avatar avatar-xl mx-auto mb-3">
            <span class="avatar-initial rounded-circle bg-secondary">
                <i class="ti ti-message-circle-2" style="font-size: 2rem;"></i>
            </span>
        </div>
        <h6 class="text-muted mb-1">لا توجد تعليقات</h6>
        <p class="text-muted small mb-0">كن أول من يضيف تعليقاً لهذا العميل</p>
    </div>
@endif
