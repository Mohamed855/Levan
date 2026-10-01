<div class="modal-header">
    <h5 class="modal-title">تعديل التسوية</h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
</div>

<div class="modal-body">
    <form id="editSettlementForm" data-settlement-id="{{ $settlement->id }}" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="row g-4">
            <div class="col-md-6">
                <label class="form-label">تاريخ التسوية <span class="text-danger">*</span></label>
                <input type="date" name="settlement_date" class="form-control"
                    value="{{ $settlement->settlement_date->format('Y-m-d') }}" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">مبلغ التسوية <span class="text-danger">*</span></label>
                <input type="number" name="settlement_amount" class="form-control"
                    value="{{ $settlement->settlement_amount }}" step="0.01" min="0.01" placeholder="0.00"
                    required>
            </div>

            <div class="col-md-6">
                <label class="form-label">رقم التسوية <span class="text-danger">*</span></label>
                <input type="text" name="settlement_number" class="form-control"
                    value="{{ $settlement->settlement_number }}" maxlength="100" required>
            </div>

            <div class="col-md-6">
                <label class="form-label">نوع التسوية <span class="text-danger">*</span></label>
                <select name="settlement_type" class="form-control select2-modal" data-placeholder="اختر نوع التسوية"
                    required>
                    <option value="">اختر نوع التسوية</option>
                    @foreach ($settlementTypes as $key => $value)
                        <option value="{{ $key }}"
                            {{ $settlement->settlement_type == $key ? 'selected' : '' }}>{{ $value }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label class="form-label">المرفق</label>
                @if ($settlement->has_file)
                    <div class="alert alert-info mb-2">
                        <i class="ti ti-paperclip me-1"></i>
                        <strong>المرفق الحالي:</strong>
                        <a href="{{ $settlement->file_url }}" target="_blank" class="text-primary ms-2">
                            عرض الملف الحالي ({{ $settlement->file_size_formatted }})
                        </a>
                    </div>
                @endif
                <input type="file" name="file" class="form-control"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                <div class="form-text">
                    الأنواع المدعومة: PDF, DOC, XLS, صور. الحد الأقصى: 10 ميجابايت
                </div>
            </div>
        </div>
    </form>
</div>

<div class="modal-footer">
    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
    <button type="button" class="btn btn-primary" id="updateSettlement">
        <span class="spinner-border spinner-border-sm me-2" style="display: none;"></span>
        حفظ
    </button>
</div>
