@foreach ($customerSettlements as $settlement)
    <div class="border rounded p-3 mb-3" id="settlement-{{ $settlement->id }}">
        <div class="row align-items-center">
            <div class="col-md-2">
                <label class="form-label">تاريخ التسوية</label>
                <div class="fw-medium">{{ $settlement->settlement_date_formatted }}</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">المبلغ</label>
                <div class="fw-medium text-success">{{ $settlement->settlement_amount_formatted }}</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">رقم التسوية</label>
                <div class="fw-medium">{{ $settlement->settlement_number }}</div>
            </div>
            <div class="col-md-2">
                <label class="form-label">نوع التسوية</label>
                <div class="text-muted small">{{ $settlement->settlement_type_text }}</div>
            </div>
            @if ($settlement->has_file)
                <div class="col-md-2">
                    <label class="form-label">المرفق</label>
                    <div class="text-muted small">
                        <i class="ti ti-paperclip me-1"></i>
                        <a href="{{ $settlement->file_url }}" target="_blank" class="text-primary">
                            ملف مرفق
                        </a>
                    </div>
                </div>
            @endif
            <div class="col-md-2 text-end">
                <button type="button" class="btn btn-link btn-sm text-secondary me-1 js-edit-settlement-btn"
                    data-id="{{ $settlement->id }}" title="تعديل">
                    <i class="ti ti-edit" style="font-size: 1.2rem;"></i>
                </button>
                <button type="button" class="btn btn-link btn-sm text-secondary js-delete-settlement-btn"
                    data-id="{{ $settlement->id }}" title="حذف">
                    <i class="ti ti-trash" style="font-size: 1.2rem;"></i>
                </button>
            </div>
        </div>
    </div>
@endforeach

@if ($customerSettlements->count() == 0)
    <div class="text-center py-5" id="no-settlements-message">
        <i class="ti ti-file-check fa-3x text-muted mb-3" style="font-size: 3rem;"></i>
        <p class="text-muted mb-0">لا توجد تسويات</p>
        <small class="text-muted">ابدأ بإضافة تسوية لهذا العميل</small>
    </div>
@endif