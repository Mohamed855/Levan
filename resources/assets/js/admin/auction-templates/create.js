'use strict';

document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('wizard-validation-form');
    if (!form) return;

    const wizardElement = document.getElementById('wizard-validation');
    const templateFileInput = document.getElementById('file');
    const definitionsInput = document.getElementById('field_definitions');
    const workspace = document.getElementById('template-placement-workspace');
    const previewBox = document.getElementById('template-preview-box');
    const requiredKeys = document.getElementById('template-required-keys');
    let selectedKey = null;
    let definitions = [];

    function templateGuide() {
        return window.auctionTemplateFieldGuides?.[$('#type').val()]?.fields || [];
    }

    function saveDefinitions() {
        if (definitionsInput) definitionsInput.value = JSON.stringify(definitions);
    }

    function renderKeyButtons() {
        if (!requiredKeys) return;
        const guide = templateGuide();
        requiredKeys.innerHTML = guide.map((field) => {
            const defined = definitions.some((definition) => definition.key === field.name);
            const status = defined ? 'success' : (field.required ? 'danger' : 'secondary');
            return `<button type="button" class="btn btn-sm btn-label-${status} ${selectedKey === field.name ? 'active' : ''}" data-template-key="${escapeHtml(field.name)}">${escapeHtml(field.label)}${field.required ? ' *' : ''}</button>`;
        }).join('');
        requiredKeys.querySelectorAll('[data-template-key]').forEach((button) => {
            button.addEventListener('click', () => {
                selectedKey = button.dataset.templateKey;
                renderKeyButtons();
            });
        });
    }

    function renderZones() {
        const canvas = previewBox?.querySelector('.template-placement-canvas');
        if (!canvas) return;
        canvas.querySelectorAll('.template-placement-zone').forEach((zone) => zone.remove());
        definitions.forEach((definition) => {
            const zone = document.createElement('div');
            zone.className = 'template-placement-zone';
            zone.dataset.key = definition.key;
            zone.style.cssText = `left:${definition.x * 100}%;top:${definition.y * 100}%;width:${definition.width * 100}%;height:${definition.height * 100}%;`;
            zone.innerHTML = `<span>${escapeHtml(definition.key)}</span><button type="button" aria-label="حذف الموضع" data-remove-template-key="${escapeHtml(definition.key)}"><i class="ti ti-x"></i></button>`;
            zone.querySelector('[data-remove-template-key]').addEventListener('click', (event) => {
                event.stopPropagation();
                definitions = definitions.filter((item) => item.key !== definition.key);
                saveDefinitions();
                renderZones();
                renderKeyButtons();
            });
            canvas.appendChild(zone);
        });
    }

    function setupImagePlacement(file) {
        if (!workspace || !previewBox || !requiredKeys) return;
        workspace.classList.remove('d-none');
        definitions = [];
        selectedKey = null;
        saveDefinitions();
        const objectUrl = URL.createObjectURL(file);
        previewBox.innerHTML = `<div class="template-placement-canvas"><img src="${objectUrl}" alt="معاينة القالب"></div>`;
        const canvas = previewBox.querySelector('.template-placement-canvas');
        let start = null;

        const point = (event) => {
            const rect = canvas.getBoundingClientRect();
            return {
                x: Math.max(0, Math.min(1, (event.clientX - rect.left) / rect.width)),
                y: Math.max(0, Math.min(1, (event.clientY - rect.top) / rect.height)),
            };
        };

        canvas.addEventListener('pointerdown', (event) => {
            if (!selectedKey || event.target.closest('.template-placement-zone')) return;
            start = point(event);
            canvas.setPointerCapture(event.pointerId);
        });
        canvas.addEventListener('pointerup', (event) => {
            if (!start || !selectedKey) return;
            const end = point(event);
            const definition = {
                key: selectedKey,
                page: 1,
                x: Math.min(start.x, end.x),
                y: Math.min(start.y, end.y),
                width: Math.abs(end.x - start.x),
                height: Math.abs(end.y - start.y),
            };
            start = null;
            if (definition.width < 0.02 || definition.height < 0.02) return;
            definitions = [...definitions.filter((item) => item.key !== selectedKey), definition];
            saveDefinitions();
            renderZones();
            renderKeyButtons();
        });
        renderKeyButtons();
    }

    function showTemplatePreview() {
        const file = templateFileInput?.files?.[0];
        if (!file || !workspace || !previewBox) return;
        if (file.type.startsWith('image/')) {
            setupImagePlacement(file);
            return;
        }
        workspace.classList.remove('d-none');
        definitions = [];
        saveDefinitions();
        previewBox.innerHTML = `<iframe src="${URL.createObjectURL(file)}#toolbar=0" title="معاينة القالب" class="w-100 border-0" style="height: 620px;"></iframe>`;
        requiredKeys.innerHTML = templateGuide().map((field) => `<code class="badge bg-label-secondary">${escapeHtml(field.name)}</code>`).join('');
    }

    wizardElement?.addEventListener('click', (event) => {
        const nextButton = event.target.closest('[data-wizard-next], .btn-next:not(.btn-submit)');
        const step = nextButton?.closest('[data-form-step]');
        const file = templateFileInput?.files?.[0];
        if (!nextButton || step?.id !== 'step-files' || !file?.type.startsWith('image/')) return;
        const missing = templateGuide().filter((field) => field.required && !definitions.some((definition) => definition.key === field.name));
        if (missing.length === 0) return;
        event.preventDefault();
        event.stopImmediatePropagation();
        window.SharedFormValidation?.setFieldError(form, 'field_definitions', 'يرجى تحديد مواضع جميع الحقول الإلزامية داخل التصميم قبل المتابعة.');
        window.toastr?.error('يرجى تحديد مواضع جميع الحقول الإلزامية داخل التصميم قبل المتابعة.');
    }, true);

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


        // Status
        const isActive = $('#is_active').is(':checked');
        if (isActive) {
            $('#preview-template-status').html('<span class="badge bg-label-success">مفعل ومتاح للمستأجرين</span>');
            $('#review-status').html('<span class="badge bg-label-success">مفعل</span>');
        } else {
            $('#preview-template-status').html('<span class="badge bg-label-danger">معطل</span>');
            $('#review-status').html('<span class="badge bg-label-danger">معطل</span>');
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
    $('#file').on('change', () => {
        updateLivePreview();
        showTemplatePreview();
    });
    $('#is_active').on('change', updateLivePreview);
    $('#type').on('change', () => {
        if (templateFileInput?.files?.[0]?.type.startsWith('image/')) setupImagePlacement(templateFileInput.files[0]);
    });

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
