@props(['resourceKey'])
<div class="modal fade" tabindex="-1" aria-hidden="true" data-wizard-modal="{{ $resourceKey }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">مسودة محفوظة</h5>
            </div>
            <div class="modal-body">
                لديك مسودة سابقة. هل تريد استرجاع البيانات والمتابعة من حيث توقفت أم بدء نموذج جديد؟
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-discard-draft>بدء جديد</button>
                <button type="button" class="btn btn-primary" data-restore-draft>استرجاع المسودة</button>
            </div>
        </div>
    </div>
</div>
