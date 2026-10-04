@foreach ($customerNotes as $note)
    <div class="note-field border rounded p-3 mb-3" id="note-{{ $note->id }}">
        <div class="row">
            <div class="col-11">
                <p class="mb-2">{{ $note->note }}</p>
                <small class="text-muted">
                    {{ $note->created_at->locale('ar')->translatedFormat('d F Y - H:i') }}
                    • {{ $note->createdBy->name ?? 'غير محدد' }}
                </small>
            </div>
            <div class="col-1 text-end">
                <button type="button" class="btn btn-outline-danger btn-sm js-delete-note-btn"
                    data-id="{{ $note->id }}" title="حذف">
                    <i class="ti ti-trash text-danger"></i>
                </button>
            </div>
        </div>
    </div>
@endforeach

@if ($customerNotes->count() == 0)
    <div class="text-center py-5" id="no-notes-message">
        <div class="avatar avatar-xl mx-auto mb-3">
            <span class="avatar-initial rounded-circle bg-secondary">
                <i class="ti ti-notes" style="font-size: 2rem;"></i>
            </span>
        </div>
        <h6 class="text-muted mb-1">لا توجد ملاحظات</h6>
        <p class="text-muted small mb-0">ابدأ بإضافة ملاحظات لهذا العميل</p>
    </div>
@endif
