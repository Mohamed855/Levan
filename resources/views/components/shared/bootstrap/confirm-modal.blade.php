@props([
    'id' => 'globalConfirmationModal',
])

<div id="{{ $id }}" class="modal fade" tabindex="-1" aria-labelledby="{{ $id }}Heading" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content text-center p-3">
            <div class="modal-body pt-4 px-4 pb-2">
                <div id="{{ $id }}IconWrapper" class="avatar avatar-xl mx-auto rounded-circle d-flex align-items-center justify-content-center bg-label-danger mb-3">
                    <i id="{{ $id }}Icon" class="ti ti-trash fs-2 text-danger"></i>
                </div>
                <h5 id="{{ $id }}Heading" class="modal-title fw-bold mb-2">تأكيد العملية</h5>
                <p id="{{ $id }}Message" class="text-muted mb-0">هل أنت متأكد من رغبتك في المتابعة؟</p>

                <div id="{{ $id }}CustomInputContainer" class="mt-3 text-start d-none">
                    <label id="{{ $id }}CustomInputLabel" for="{{ $id }}CustomInput" class="form-label small fw-medium"></label>
                    <textarea id="{{ $id }}CustomInput" class="form-control" rows="3"></textarea>
                    <div id="{{ $id }}CustomInputError" class="text-danger small mt-1 d-none"></div>
                </div>
            </div>
            <div class="modal-footer justify-content-center border-0 pt-3 pb-2 gap-2">
                <button type="button" class="btn btn-label-secondary px-4" data-bs-dismiss="modal" id="{{ $id }}CancelBtn">إلغاء</button>
                <button type="button" class="btn btn-danger px-4" id="{{ $id }}SubmitBtn">تأكيد</button>
            </div>
        </div>
    </div>
</div>
