'use strict';

let validation, stepper;

const config = {
    maxFields: 50,
    animationSpeed: 200,
    loadingDelay: 300
};

const state = {
    isLoading: false,
    currentStep: 0
};

function generateUUID() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
        const r = Math.random() * 16 | 0;
        const v = c === 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
}

const NotificationManager = {
    success(message) {
        if (typeof toastr !== 'undefined') toastr.success(message);
    },
    error(message) {
        if (typeof toastr !== 'undefined') toastr.error(message);
    },
    warning(message) {
        if (typeof toastr !== 'undefined') toastr.warning(message);
    }
};

const LoadingManager = {
    showSubmissionLoading() {
        const submitBtn = document.querySelector('.btn-submit');
        if (!submitBtn) return;

        submitBtn.disabled = true;
        submitBtn.dataset.originalText = submitBtn.innerHTML;

        const loadingText = window.isEditMode ? 'جاري التحديث...' : 'جاري الحفظ...';
        submitBtn.innerHTML = `
            <span class="spinner-border spinner-border-sm me-2" role="status"></span>
            ${loadingText}
        `;
    },

    hideSubmissionLoading() {
        const submitBtn = document.querySelector('.btn-submit');
        if (!submitBtn || !submitBtn.dataset.originalText) return;

        submitBtn.disabled = false;
        submitBtn.innerHTML = submitBtn.dataset.originalText;
        delete submitBtn.dataset.originalText;
    }
};

const TimeValidation = {
    validateCallTimes() {
        const startTimeInput = document.querySelector('#call_start_time');
        const endTimeInput = document.querySelector('#call_end_time');

        if (!startTimeInput || !endTimeInput) return true;

        const startTime = startTimeInput.value;
        const endTime = endTimeInput.value;

        // مسح الأخطاء السابقة
        FieldManager.clearFieldError(startTimeInput);
        FieldManager.clearFieldError(endTimeInput);

        // إذا تم إدخال واحد فقط
        if ((startTime && !endTime) || (!startTime && endTime)) {
            const errorMsg = 'يجب إدخال وقت البدء والنهاية معاً';
            if (!endTime) {
                FieldManager.showFieldError(endTimeInput, errorMsg);
            } else {
                FieldManager.showFieldError(startTimeInput, errorMsg);
            }
            return false;
        }

        if (startTime && endTime) {
            const [startHour, startMin] = startTime.split(':').map(Number);
            const [endHour, endMin] = endTime.split(':').map(Number);

            const startMinutes = startHour * 60 + startMin;
            const endMinutes = endHour * 60 + endMin;

            if (endMinutes <= startMinutes) {
                FieldManager.showFieldError(endTimeInput, 'وقت النهاية يجب أن يكون بعد وقت البدء');
                return false;
            }
        }

        return true;
    }
};

const FieldManager = {
    addField(fieldData = null) {
        const template = document.querySelector('#portfolio-field-template');
        const container = document.querySelector('#portfolio-fields-container');

        if (!template || !container) return null;

        const existingFields = container.querySelectorAll('.portfolio-field-row').length;
        if (existingFields >= config.maxFields) {
            NotificationManager.warning(`لا يمكن إضافة أكثر من ${config.maxFields} حقل`);
            return null;
        }

        const div = document.createElement('div');
        div.innerHTML = template.innerHTML;
        const fieldRow = div.firstElementChild;

        fieldRow.setAttribute('data-field-id', generateUUID());

        this.configureFieldRow(fieldRow, fieldData);
        container.appendChild(fieldRow);
        this.updateFieldNumbers();
        this.toggleEmptyState();

        return fieldRow;
    },

    configureFieldRow(fieldRow, fieldData) {
        const hiddenIdInput = fieldRow.querySelector('.field-id-input');
        if (hiddenIdInput && fieldData?.id) {
            hiddenIdInput.value = fieldData.id;
        }

        const nameInput = fieldRow.querySelector('.field-name-input');
        const typeSelect = fieldRow.querySelector('.field-type-select');

        if (nameInput && fieldData) {
            nameInput.value = fieldData.field_name || fieldData.name || '';
            if (typeSelect) typeSelect.value = fieldData.field_type || 'text';
        }

        this.initializeSelect2ForField(fieldRow);
        this.setupRequiredFieldHandlers(fieldRow, fieldData);
    },

    initializeSelect2ForField(fieldRow) {
        if (typeof $ === 'undefined' || !$.fn.select2) return;

        const typeSelect = fieldRow.querySelector('.field-type-select');

        if (typeSelect) {
            $(typeSelect).select2({
                placeholder: 'اختر النوع',
                allowClear: false,
                width: '100%',
                language: 'ar',
                dir: 'rtl'
            });
        }
    },

    setupRequiredFieldHandlers(fieldRow, fieldData = null) {
        const requiredCheckbox = fieldRow.querySelector('.field-required-checkbox');
        const requiredHidden = fieldRow.querySelector('.field-required-hidden');

        if (!requiredCheckbox || !requiredHidden) return;

        const isRequired = fieldData ? (fieldData.is_required == 1 || fieldData.is_required === true) : false;

        requiredCheckbox.checked = isRequired;
        requiredHidden.value = isRequired ? '1' : '0';

        requiredCheckbox.addEventListener('change', function () {
            requiredHidden.value = this.checked ? '1' : '0';
            const fieldRowElement = this.closest('.portfolio-field-row');
            if (fieldRowElement) {
                fieldRowElement.setAttribute('data-required', this.checked ? 'true' : 'false');
            }
        });

        fieldRow.setAttribute('data-required', isRequired ? 'true' : 'false');
    },

    updateFieldNumbers() {
        const fieldRows = document.querySelectorAll('.portfolio-field-row');
        fieldRows.forEach((row, index) => {
            const numberBadge = row.querySelector('.field-number');
            if (numberBadge) numberBadge.textContent = index + 1;

            const requiredContainer = row.querySelector('.required-field-container');
            const requiredCheckbox = row.querySelector('.field-required-checkbox');

            if (requiredContainer) requiredContainer.setAttribute('data-field-index', index);
            if (requiredCheckbox) requiredCheckbox.setAttribute('data-field-index', index);
        });
    },

    removeField(fieldRow) {
        if (!fieldRow) return;

        const fieldName = fieldRow.querySelector('.field-name-input')?.value || 'غير محدد';

        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'تأكيد الحذف',
                text: `هل تريد حذف الحقل "${fieldName}"؟`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'نعم، احذف',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    fieldRow.remove();
                    this.updateFieldNumbers();
                    this.toggleEmptyState();
                }
            });
        } else {
            if (confirm(`هل تريد حذف الحقل "${fieldName}"؟`)) {
                fieldRow.remove();
                this.updateFieldNumbers();
                this.toggleEmptyState();
            }
        }
    },

    toggleEmptyState() {
        const container = document.querySelector('#portfolio-fields-container');
        const emptyState = document.querySelector('#empty-fields-state');
        const allFields = container?.querySelectorAll('.portfolio-field-row') || [];

        if (emptyState) {
            emptyState.style.display = allFields.length === 0 ? 'block' : 'none';
        }
    },

    clearAllFields() {
        const container = document.querySelector('#portfolio-fields-container');
        if (!container) return;

        const allFields = container.querySelectorAll('.portfolio-field-row');
        allFields.forEach(field => field.remove());
        this.toggleEmptyState();
    },

    loadExistingFields() {
        if (!window.existingFields || !Array.isArray(window.existingFields)) return;

        window.existingFields.forEach((fieldData) => {
            const fieldRow = this.addField(fieldData);

            if (fieldRow) {
                const nameInput = fieldRow.querySelector('.field-name-input');
                const typeSelect = fieldRow.querySelector('.field-type-select');
                const requiredCheckbox = fieldRow.querySelector('.field-required-checkbox');
                const requiredHidden = fieldRow.querySelector('.field-required-hidden');

                if (nameInput) nameInput.value = fieldData.field_name || '';
                if (typeSelect) {
                    typeSelect.value = fieldData.field_type || 'text';
                    if (typeof $ !== 'undefined' && $.fn.select2) $(typeSelect).trigger('change');
                }

                const isRequired = fieldData.is_required == 1 || fieldData.is_required === true;
                if (requiredCheckbox) requiredCheckbox.checked = isRequired;
                if (requiredHidden) requiredHidden.value = isRequired ? '1' : '0';

                fieldRow.setAttribute('data-required', isRequired ? 'true' : 'false');
            }
        });

        this.updateFieldNumbers();
    },

    validateFields() {
        const fieldInputs = document.querySelectorAll('.field-name-input');
        let hasValidFields = false;
        let errors = [];

        fieldInputs.forEach(input => this.clearFieldError(input));

        fieldInputs.forEach((input, index) => {
            const value = input.value.trim();
            if (value) {
                hasValidFields = true;
                const otherInputs = Array.from(fieldInputs).filter((_, i) => i !== index);
                const isDuplicate = otherInputs.some(other => other.value.trim() === value);

                if (isDuplicate) {
                    errors.push(`الحقل "${value}" مكرر`);
                    this.showFieldError(input, 'اسم الحقل مكرر');
                }
            }
        });

        if (errors.length > 0) {
            NotificationManager.error(errors.join('\n'));
            return false;
        }

        return true;
    },

    showFieldError(field, message) {
        field.classList.add('is-invalid');
        const existingError = field.parentNode.querySelector('.invalid-feedback');
        if (existingError) existingError.remove();

        const errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        errorDiv.style.display = 'block';
        errorDiv.textContent = message;
        field.parentNode.appendChild(errorDiv);
    },

    clearFieldError(field) {
        field.classList.remove('is-invalid');
        const errorDiv = field.parentNode.querySelector('.invalid-feedback');
        if (errorDiv) errorDiv.remove();
    }
};

const ValidationManager = {
    initializeValidation(wizard) {
        if (typeof FormValidation === 'undefined') return null;

        validation = FormValidation.formValidation(wizard, {
            fields: {
                name: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال اسم المحفظة' },
                        stringLength: { min: 2, max: 255, message: 'اسم المحفظة يجب أن يكون بين 2 و 255 حرف' }
                    }
                },
                company_id: { validators: { notEmpty: { message: 'يرجى اختيار الشركة' } } }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleValidClass: '',
                    rowSelector: '.col-sm-6, .col-12, .col-md-6'
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton()
            }
        }).on('core.form.valid', () => {
            if (FieldManager.validateFields() && TimeValidation.validateCallTimes()) {
                LoadingManager.showSubmissionLoading();
                wizard.querySelector('#wizard-validation-form')?.submit();
            }
        });

        return validation;
    },

    async validateFirstStep() {
        const nameField = document.querySelector('#name');
        const companyField = document.querySelector('#company_id');
        let isValid = true;

        if (!nameField?.value.trim()) {
            FieldManager.showFieldError(nameField, 'يرجى إدخال اسم المحفظة');
            isValid = false;
        } else {
            FieldManager.clearFieldError(nameField);
        }

        if (!companyField?.value) {
            FieldManager.showFieldError(companyField, 'يرجى اختيار الشركة');
            isValid = false;
        } else {
            FieldManager.clearFieldError(companyField);
        }

        return isValid;
    },

    validateAllSteps() {
        const nameField = document.querySelector('#name');
        const companyField = document.querySelector('#company_id');
        let isValid = true;

        if (!nameField?.value.trim()) {
            FieldManager.showFieldError(nameField, 'يرجى إدخال اسم المحفظة');
            isValid = false;
        }

        if (!companyField?.value) {
            FieldManager.showFieldError(companyField, 'يرجى اختيار الشركة');
            isValid = false;
        }

        if (!FieldManager.validateFields()) {
            isValid = false;
        }

        if (!TimeValidation.validateCallTimes()) {
            isValid = false;
        }

        if (!nameField?.value.trim() || !companyField?.value) {
            StepperManager.goToStep(1);
        } else if (!FieldManager.validateFields() || !TimeValidation.validateCallTimes()) {
            StepperManager.goToStep(2);
        }

        return isValid;
    }
};

const StepperManager = {
    initialize(wizard) {
        if (typeof Stepper === 'undefined') return null;
        stepper = new Stepper(wizard, { linear: false });
        return stepper;
    },

    goToStep(stepNumber) {
        if (stepper) {
            stepper.to(stepNumber);
            state.currentStep = stepNumber - 1;
        }
    },

    async nextStep() {
        if (state.currentStep === 0) {
            const isValid = await ValidationManager.validateFirstStep();
            if (isValid && stepper) {
                stepper.next();
                state.currentStep++;
            }
        }
    },

    previousStep() {
        if (stepper && state.currentStep > 0) {
            stepper.previous();
            state.currentStep--;
        }
    }
};

const InitializationManager = {
    initializeSelect2() {
        if (typeof $ === 'undefined' || !$.fn.select2) return;

        $('.select2').select2({
            placeholder: function () { return $(this).data('placeholder'); },
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl'
        }).on('select2:select select2:clear', function () {
            $(this).removeClass('is-invalid');
            FieldManager.clearFieldError(this);
            if (validation && this.name) validation.revalidateField(this.name);
        });
    },

    setupEventHandlers() {
        document.querySelectorAll('.btn-next').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                StepperManager.nextStep();
            });
        });

        document.querySelectorAll('.btn-prev').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                StepperManager.previousStep();
            });
        });

        document.querySelectorAll('.btn-submit').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                if (ValidationManager.validateAllSteps()) {
                    validation?.validate();
                }
            });
        });

        const addFieldBtn = document.querySelector('#add-portfolio-field-btn');
        if (addFieldBtn) {
            addFieldBtn.addEventListener('click', () => {
                FieldManager.addField();
            });
        }

        document.addEventListener('click', (e) => {
            if (e.target.closest('.remove-portfolio-field-btn')) {
                e.preventDefault();
                const fieldRow = e.target.closest('.portfolio-field-row');
                FieldManager.removeField(fieldRow);
            }
        });

        document.addEventListener('input', (e) => {
            if (e.target.matches('#name, .field-name-input')) {
                FieldManager.clearFieldError(e.target);
            }
        });

        document.addEventListener('change', (e) => {
            if (e.target.matches('#company_id, .field-type-select')) {
                FieldManager.clearFieldError(e.target);
            }
            
            if (e.target.matches('#call_start_time, #call_end_time')) {
                FieldManager.clearFieldError(e.target);
                TimeValidation.validateCallTimes();
            }
        });

        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                LoadingManager.hideSubmissionLoading();
            }
        });
    },

    async loadInitialData() {
        FieldManager.toggleEmptyState();

        if (window.isEditMode && window.existingFields) {
            FieldManager.loadExistingFields();
        }
    }
};

const PortfolioWizard = {
    async initialize() {
        try {
            const wizard = document.querySelector('#wizard-validation');
            if (!wizard) throw new Error('Wizard element not found');

            StepperManager.initialize(wizard);
            ValidationManager.initializeValidation(wizard);
            InitializationManager.initializeSelect2();
            InitializationManager.setupEventHandlers();
            await InitializationManager.loadInitialData();

        } catch (error) {
            NotificationManager.error('فشل في تهيئة النظام: ' + error.message);
        }
    }
};

document.addEventListener('DOMContentLoaded', () => {
    PortfolioWizard.initialize();
});

window.PortfolioWizard = PortfolioWizard;
window.FieldManager = FieldManager;
window.LoadingManager = LoadingManager;
window.TimeValidation = TimeValidation;