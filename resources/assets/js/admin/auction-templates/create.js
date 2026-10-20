'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('wizard-validation-form');
    if (!form) return;

    const wizardElement = document.getElementById('wizard-validation');

    function escapeHtml(value) {
        if (value === null || value === undefined) return '';
        const element = document.createElement('div');
        element.textContent = String(value);
        return element.innerHTML;
    }

    function updateLivePreview() {
        // Name
        const nameVal = $('#name').val()?.trim();
        $('#preview-template-name').text(nameVal || 'اسم القالب');
        $('#review-name').text(nameVal || '-');

        // Type
        const typeSelect = document.getElementById('type');
        const selectedTypeOption = typeSelect?.options[typeSelect.selectedIndex];
        const typeText = selectedTypeOption ? selectedTypeOption.text : '-';
        const typeVal = $('#type').val();
        $('#preview-template-type').text(typeText || 'نوع القالب');
        $('#review-type').text(typeText || '-');

        // Description
        const descVal = $('#description').val()?.trim();
        $('#preview-template-desc').text(descVal || 'وصف القالب وملاحظات التصميم');
        $('#review-description').text(descVal || '-');

        // File
        const fileInput = document.getElementById('file');
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
            const file = fileInput.files[0];
            const sizeMb = (file.size / (1024 * 1024)).toFixed(2);
            $('#review-file-name').html(`
                <span class="badge bg-label-primary font-monospace">
                    <i class="ti ti-file me-1"></i>${escapeHtml(file.name)} (${sizeMb} MB)
                </span>
            `);
        } else {
            $('#review-file-name').text('لم يتم اختيار ملف بعد');
        }

        // Thumbnail
        const thumbInput = document.getElementById('thumbnail');
        if (thumbInput && thumbInput.files && thumbInput.files.length > 0) {
            const thumbFile = thumbInput.files[0];
            $('#review-thumbnail-name').html(`
                <span class="badge bg-label-info font-monospace">
                    <i class="ti ti-photo me-1"></i>${escapeHtml(thumbFile.name)}
                </span>
            `);

            // Update preview image
            const reader = new FileReader();
            reader.onload = function (e) {
                const previewImg = document.getElementById('preview-template-img');
                const previewIcon = document.getElementById('preview-template-icon');
                if (previewImg) {
                    previewImg.src = e.target.result;
                    previewImg.classList.remove('d-none');
                }
                if (previewIcon) {
                    previewIcon.classList.add('d-none');
                }
            };
            reader.readAsDataURL(thumbFile);
        } else {
            $('#review-thumbnail-name').text('سيتم توليدها تلقائياً');
            const previewImg = document.getElementById('preview-template-img');
            const previewIcon = document.getElementById('preview-template-icon');
            if (previewImg && !previewImg.dataset.existing) {
                previewImg.classList.add('d-none');
            }
            if (previewIcon) {
                previewIcon.classList.remove('d-none');
            }
        }

        // Status
        const isActive = $('#is_active').is(':checked');
        if (isActive) {
            $('#preview-template-status').html('<span class="badge bg-label-success"><i class="ti ti-check me-1"></i>مفعل ومتاح للمستأجرين</span>');
            $('#review-status').html('<span class="badge bg-label-success"><i class="ti ti-check me-1"></i>مفعل</span>');
        } else {
            $('#preview-template-status').html('<span class="badge bg-label-danger"><i class="ti ti-ban me-1"></i>معطل</span>');
            $('#review-status').html('<span class="badge bg-label-danger"><i class="ti ti-ban me-1"></i>معطل</span>');
        }

        // Specification Guide Synchronize
        $('.spec-guide-item').addClass('d-none');
        if (typeVal === 'brochure') {
            $('#spec-guide-brochure').removeClass('d-none');
            if (fileInput) fileInput.setAttribute('accept', '.pdf,application/pdf');
        } else if (typeVal === 'social') {
            $('#spec-guide-social').removeClass('d-none');
            if (fileInput) fileInput.setAttribute('accept', '.pdf,application/pdf,image/png,image/jpeg,image/webp');
        } else if (typeVal === 'billboard') {
            $('#spec-guide-billboard').removeClass('d-none');
            if (fileInput) fileInput.setAttribute('accept', '.pdf,application/pdf,image/png,image/jpeg,image/webp');
        } else {
            $('#spec-guide-default').removeClass('d-none');
            if (fileInput) fileInput.setAttribute('accept', '.pdf,application/pdf,image/png,image/jpeg,image/webp');
        }
    }

    // Input listeners
    $('#name').on('input', updateLivePreview);
    $('#type').on('change', updateLivePreview);
    $('#description').on('input', updateLivePreview);
    $('#file').on('change', updateLivePreview);
    $('#thumbnail').on('change', updateLivePreview);
    $('#is_active').on('change', updateLivePreview);

    // Quick jump step buttons in Step 3 Review
    document.querySelectorAll('[data-edit-step]').forEach(button => {
        button.addEventListener('click', () => {
            const target = Number(button.dataset.editStep);
            if (!target) return;
            if (wizardElement?.bsStepper) {
                wizardElement.bsStepper.to(target);
            } else if (window.stepper) {
                window.stepper.to(target);
            }
        });
    });

    // Wizard draft restored handler
    $(form).on('wizardDraftRestored', function () {
        updateLivePreview();
    });

    // Initial sync
    updateLivePreview();
});
