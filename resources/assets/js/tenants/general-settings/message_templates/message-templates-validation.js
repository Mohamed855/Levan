'use strict';

let contentEditor;
let validationStepper;
let FormValidation1;
let FormValidation2;

document.addEventListener('DOMContentLoaded', function () {
    initializeFormWizard();
    initializeVariableHandlers();
    initializeCategoryHandlers();

    setTimeout(() => {
        initializeSelect2();
    }, 100);
});

function initializeFormWizard() {
    const wizardValidation = document.querySelector('#wizard-validation');
    if (!wizardValidation) return;

    const nextButtons = wizardValidation.querySelectorAll('.btn-next');
    const prevButtons = wizardValidation.querySelectorAll('.btn-prev');

    validationStepper = new Stepper(wizardValidation, { linear: false });

    FormValidation1 = FormValidation.formValidation(wizardValidation, {
        fields: {
            name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم القالب' },
                    stringLength: { min: 2, max: 255, message: 'اسم القالب يجب أن يكون بين 2 و 255 حرف' }
                }
            },
            category: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار فئة القالب' }
                }
            },
            channel: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار القناة' }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-sm-6, .col-12'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    }).on('core.form.valid', function () {
        validationStepper.next();
    });

    FormValidation2 = FormValidation.formValidation(wizardValidation, {
        fields: {
            content: {
                validators: {
                    notEmpty: { message: 'يرجى كتابة محتوى القالب' }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-12'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    }).on('core.form.valid', function () {
        if (contentEditor) {
            document.querySelector('#content').value = contentEditor.getData();
        }
        const form = wizardValidation.querySelector('#wizard-validation-form');
        if (form) {
            form.onsubmit = null;
            form.submit();
        }
    });

    nextButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const currentStep = validationStepper._currentIndex;
            switch (currentStep) {
                case 0: FormValidation1.validate(); break;
                case 1: FormValidation2.validate(); break;
            }
        });
    });

    prevButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            if (validationStepper._currentIndex === 1) {
                validationStepper.previous();
            } else {
                window.history.back();
            }
        });
    });
}

function initializeVariableHandlers() {
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('variable-btn')) {
            e.preventDefault();
            const variable = e.target.getAttribute('data-variable');
            insertVariable(variable);

            e.target.style.transform = 'scale(0.95)';
            setTimeout(() => { e.target.style.transform = ''; }, 150);
        }
    });

    document.addEventListener('click', function (e) {
        if (e.target.closest('.variable-group-header')) {
            e.preventDefault();
            const card = e.target.closest('.variable-group-card');
            if (!card) return;

            const groupIndex = card.getAttribute('data-group');
            const content = document.querySelector(`[data-group="${groupIndex}"].variable-group-content`);
            const icon = card.querySelector('.transition-icon');
            const cardElement = card.querySelector('.card');

            if (content && icon && cardElement) {
                const isVisible = content.style.display !== 'none';

                if (isVisible) {
                    content.style.display = 'none';
                    icon.style.transform = 'rotate(0deg)';
                    cardElement.style.borderColor = 'rgba(var(--bs-primary-rgb), 0.1)';
                    cardElement.style.boxShadow = '0 2px 0.25rem rgba(0,0,0,0.05)';
                } else {
                    // إخفاء جميع المجموعات الأخرى
                    document.querySelectorAll('.variable-group-content').forEach(c => c.style.display = 'none');
                    document.querySelectorAll('.transition-icon').forEach(i => i.style.transform = 'rotate(0deg)');
                    document.querySelectorAll('.variable-group-card .card').forEach(c => {
                        c.style.borderColor = 'rgba(var(--bs-primary-rgb), 0.1)';
                        c.style.boxShadow = '0 2px 0.25rem rgba(0,0,0,0.05)';
                    });

                    // إظهار المجموعة المختارة
                    content.style.display = 'block';
                    icon.style.transform = 'rotate(180deg)';
                    cardElement.style.borderColor = 'rgb(var(--bs-primary-rgb))';
                    cardElement.style.boxShadow = '0 0.25rem 0.75rem rgba(var(--bs-primary-rgb), 0.2)';
                }
            }
        }
    });
}

function initializeCategoryHandlers() {
    const categorySelect = document.getElementById('category');
    if (categorySelect) {
        categorySelect.addEventListener('change', function () {
            const selectedCategory = this.value;
            if (selectedCategory) {
                updateVariablesForCategory(selectedCategory);
            }
        });
    }
}

function initializeSelect2() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').each(function () {
            $(this).select2({
                placeholder: function () { return $(this).data('placeholder'); },
                allowClear: true,
                width: '100%',
                language: 'ar',
                dir: 'rtl'
            }).on('select2:select select2:clear', function () {
                $(this).removeClass('is-invalid');
                $(this).closest('.col-sm-6, .col-sm-12, .col-12').find('.invalid-feedback').remove();
                if (FormValidation1) {
                    FormValidation1.revalidateField(this.name);
                }
            });
        });
    }
}


function insertVariable(variable) {
    if (!variable) return;

    if (contentEditor) {
        try {
            const viewFragment = contentEditor.data.processor.toView(variable + ' ');
            const modelFragment = contentEditor.data.toModel(viewFragment);
            contentEditor.model.insertContent(modelFragment);
            contentEditor.editing.view.focus();
        } catch (error) {
            insertVariableInTextarea(variable);
        }
    } else {
        insertVariableInTextarea(variable);
    }

    showToast('تم إضافة المتغير: ' + variable, 'success');
}

function insertVariableInTextarea(variable) {
    const textarea = document.getElementById('content');
    if (textarea) {
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;

        textarea.value = text.substring(0, start) + variable + ' ' + text.substring(end);
        textarea.selectionStart = textarea.selectionEnd = start + variable.length + 1;
        textarea.focus();

        if (FormValidation2) {
            FormValidation2.revalidateField('content');
        }
    }
}

function updateVariablesForCategory(category) {
    if (!window.routes || !window.routes.getVariablesByCategory) return;

    const url = `${window.routes.getVariablesByCategory}?category=${encodeURIComponent(category)}`;

    fetch(url, {
        method: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json'
        }
    }).then(response => {
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        return response.json();
    }).then(data => {
        if (data.success) {
            updateVariablesContainer(data.grouped_variables);
            showToast('تم تحديث المتغيرات للفئة المختارة', 'info');
        } else {
            throw new Error(data.message || 'خطأ في الاستجابة');
        }
    }).catch(error => {
        console.error('خطأ في جلب المتغيرات:', error);
        showToast('حدث خطأ في جلب المتغيرات', 'error');
    });
}

function updateVariablesContainer(groupedVariables) {
    const container = document.getElementById('variables-container');
    if (!container) return;

    let html = '';

    if (groupedVariables && Object.keys(groupedVariables).length > 0) {
        html += '<div class="row g-2 mb-3">';

        let groupIndex = 0;
        for (const [groupName, variables] of Object.entries(groupedVariables)) {
            html += `
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="variable-group-card" data-group="${groupIndex}">
                        <div class="card h-100 variable-group-header"
                             style="cursor: pointer; border: 1.5px solid rgba(var(--bs-primary-rgb), 0.15); transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                            <div class="card-body text-center" style="padding: 0.75rem 0.5rem;">
                                <div class="mb-1">
                                    <i class="ti ti-folder text-primary" style="font-size: 1.5rem;"></i>
                                </div>
                                <h6 class="card-title mb-1" style="font-weight: 600; font-size: 0.85rem; color: rgb(var(--bs-primary-rgb));">
                                    ${escapeHtml(groupName)}
                                </h6>
                                <span class="badge bg-label-primary" style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">
                                    ${Object.keys(variables).length} متغير
                                </span>
                                <div class="mt-1">
                                    <i class="ti ti-chevron-down transition-icon text-muted"
                                       style="font-size: 0.85rem; transition: transform 0.3s ease;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>`;
            groupIndex++;
        }

        html += '</div>';

        groupIndex = 0;
        for (const [groupName, variables] of Object.entries(groupedVariables)) {
            html += `
                <div class="variable-group-content" data-group="${groupIndex}" style="display: none;">
                    <div class="card" style="border: 1.5px solid rgba(var(--bs-primary-rgb), 0.12); background: rgba(var(--bs-primary-rgb), 0.02);">
                        <div class="card-header" style="padding: 0.6rem 1rem; border-bottom: 1px solid rgba(var(--bs-primary-rgb), 0.1);">
                            <h6 class="mb-0" style="color: rgb(var(--bs-primary-rgb)); font-weight: 600; font-size: 0.9rem;">
                                <i class="ti ti-variables me-1" style="font-size: 0.95rem;"></i>
                                متغيرات ${escapeHtml(groupName)}
                            </h6>
                        </div>
                        <div class="card-body" style="padding: 0.75rem;">
                            <div class="row g-2">`;

            for (const [variable, description] of Object.entries(variables)) {
                html += `
                    <div class="col-md-2 col-sm-3 col-4">
                        <button type="button"
                            class="btn btn-outline-primary variable-btn w-100"
                            data-variable="${escapeHtml(variable)}"
                            title="${escapeHtml(description)}"
                            style="font-size: 0.7rem; padding: 0.35rem 0.5rem; border-radius: 0.25rem; transition: all 0.2s ease; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-width: 1px;">
                            ${escapeHtml(description)}
                        </button>
                    </div>`;
            }

            html += `
                            </div>
                        </div>
                    </div>
                </div>`;
            groupIndex++;
        }
    } else {
        html = `
            <div class="text-center py-4" style="color: #6c757d;">
                <i class="ti ti-info-circle" style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5;"></i>
                <p class="mb-0" style="font-size: 0.95rem;">لا توجد متغيرات متاحة لهذه الفئة</p>
            </div>`;
    }

    container.innerHTML = html;
}

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function (m) { return map[m]; });
}

function showToast(message, type = 'info') {
    if (typeof toastr !== 'undefined') {
        toastr[type](message);
        return;
    }

    const alertClasses = {
        'success': 'alert-success',
        'error': 'alert-danger',
        'warning': 'alert-warning',
        'info': 'alert-info'
    };

    const alertClass = alertClasses[type] || 'alert-info';

    const alertHtml = `
        <div class="alert ${alertClass} alert-dismissible fade show position-fixed"
             style="top: 1.25rem; right: 1.25rem; z-index: 9999; min-width: 300px;">
            ${escapeHtml(message)}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', alertHtml);

    setTimeout(() => {
        const alerts = document.querySelectorAll('.alert');
        if (alerts.length > 0) {
            alerts[alerts.length - 1].remove();
        }
    }, 5000);
}

window.MessageTemplatesHandler = {
    insertVariable: insertVariable,
    updateVariablesForCategory: updateVariablesForCategory,
    showToast: showToast
};
