@props(['resourceKey'])
<div class="modal fade" tabindex="-1" aria-hidden="true" data-wizard-modal="{{ $resourceKey }}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="ti ti-file-text me-2 text-primary"></i>مسودة محفوظة</h5>
            </div>
            <div class="modal-body">
                لديك مسودة سابقة. هل تريد استرجاع البيانات والمتابعة من حيث توقفت أم بدء نموذج جديد؟
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-discard-draft><i class="ti ti-refresh me-1"></i>بدء جديد</button>
                <button type="button" class="btn btn-primary" data-restore-draft><i class="ti ti-history me-1"></i>استرجاع المسودة</button>
            </div>
        </div>
    </div>
</div>
