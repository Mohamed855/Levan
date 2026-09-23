@foreach ($customerAttachments as $attachment)
    <div class="border rounded p-3 mb-3" id="attachment-{{ $attachment->id }}">
        <div class="row align-items-center">
            <div class="col-md-8">
                <div class="d-flex align-items-center">
                    <i class="{{ $attachment->file_type_icon }} me-2"></i>
                    <div>
                        <div class="fw-medium">{{ basename($attachment->file_path) }}</div>
                        @if ($attachment->description)
                            <small class="text-muted">{{ $attachment->description }}</small>
                        @endif
                        <div class="small text-muted">
                            <span
                                class="badge {{ $attachment->file_type_class }} me-1">{{ strtoupper($attachment->file_type) }}</span>
                            {{ $attachment->file_size_formatted }} •
                            {{ $attachment->created_at->locale('ar')->translatedFormat('d M Y') }} •
                            {{ $attachment->createdBy->name ?? 'غير محدد' }}
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-end">
                <a href="{{ tenant_route_name('customers.attachments.download', ['customer' => $attachment->customer_id, 'customerAttachment' => $attachment->id]) }}"
                    class="btn btn-sm btn-outline-primary me-1" title="تحميل">
                    <i class="ti ti-download"></i>
                </a>
                <button type="button" class="btn btn-sm btn-outline-danger js-delete-attachment-btn"
                    data-id="{{ $attachment->id }}" title="حذف">
                    <i class="ti ti-trash"></i>
                </button>
            </div>
        </div>
    </div>
@endforeach

@if ($customerAttachments->count() == 0)
    <div class="text-center py-4 text-muted" id="no-attachments-message">
        <i class="ti ti-paperclip" style="font-size: 48px; opacity: 0.3;"></i>
        <div class="mt-2">لا توجد مرفقات لهذا العميل</div>
    </div>
@endif
