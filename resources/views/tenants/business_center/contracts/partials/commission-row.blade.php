@php
$ctype = $commission['commission_type'] ?? null;
$showProc = $ctype === 'procedure';
$showDur = in_array($ctype, ['duration', 'delinquency']);
@endphp

<div class="commission-row border rounded p-3 mb-3 bg-white" data-index="{{ $index }}">
    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
        <h6 class="mb-0 text-primary fw-bold">
            <i class="ti ti-percentage me-1"></i>
            شريحة العمولة {{ $index + 1 }}
        </h6>
        <button type="button" class="btn btn-danger btn-sm remove-commission-btn" title="حذف الشريحة">
            <i class="ti ti-trash"></i>
        </button>
    </div>

    <div class="row g-3">
        <div class="col-md-4 commission-type-col{{ $ctype ? '' : '' }}">
            <label class="form-label fw-semibold">نوع الشريحة <span class="text-danger">*</span></label>
            <select name="commissions[{{ $index }}][commission_type]" class="form-select commission-type-select">
                <option value="procedure" {{ ($ctype==='procedure' ) ? 'selected' : '' }}>إجراء</option>
                <option value="duration" {{ ($ctype==='duration' ) ? 'selected' : '' }}>مدة</option>
                <option value="delinquency" {{ ($ctype==='delinquency' ) ? 'selected' : '' }}>مدة التعثر</option>
            </select>
        </div>

        <div class="col-md-4 procedure-type-col" style="{{ $showProc ? '' : 'display:none;' }}">
            <label class="form-label fw-semibold">نوع الإجراء <span class="text-danger">*</span></label>
            <select name="commissions[{{ $index }}][procedure_type]" class="form-select">
                <option value="">اختر نوع الإجراء</option>
                <option value="friendly" {{ (isset($commission['procedure_type']) &&
                    $commission['procedure_type']==='friendly' ) ? 'selected' : '' }}>ودي</option>
                <option value="legal" {{ (isset($commission['procedure_type']) &&
                    $commission['procedure_type']==='legal' ) ? 'selected' : '' }}>قانوني</option>
            </select>
        </div>

        <div class="col-md-4 duration-from-col duration-fields" style="{{ $showDur ? '' : 'display:none;' }}">
            <label class="form-label fw-semibold">من (يوم) <span class="text-danger">*</span></label>
            <input type="number" name="commissions[{{ $index }}][duration_from]" class="form-control"
                value="{{ $commission['duration_from'] ?? '' }}" placeholder="1" min="1" max="9999">
        </div>

        <div class="col-md-4 duration-to-col duration-fields" style="{{ $showDur ? '' : 'display:none;' }}">
            <label class="form-label fw-semibold">إلى (يوم) <span class="text-danger">*</span></label>
            <input type="number" name="commissions[{{ $index }}][duration_to]" class="form-control"
                value="{{ $commission['duration_to'] ?? '' }}" placeholder="30" min="1" max="9999">
        </div>

        <div class="col-md-4">
            <label class="form-label fw-semibold">نسبة العمولة (%) <span class="text-danger">*</span></label>
            <input type="number" step="0.01" name="commissions[{{ $index }}][commission_rate]"
                class="form-control commission-rate-input" value="{{ $commission['commission_rate'] ?? '' }}"
                placeholder="0.00" min="0.01" max="100" required>
        </div>
    </div>
</div>