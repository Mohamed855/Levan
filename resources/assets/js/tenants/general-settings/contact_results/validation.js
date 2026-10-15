'use strict';
document.addEventListener('DOMContentLoaded', function () {
    initializeFormValidation();
    setTimeout(() => {
        initializeSelect2();
    }, 100);
});

function initializeFormValidation() {
    const form = document.querySelector('#contact-result-form');
    if (!form) return;

    const validation = FormValidation.formValidation(form, {
        fields: {
            name: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال اسم نتيجة الاتصال' },
                    stringLength: { min: 2, max: 255, message: 'اسم نتيجة الاتصال يجب أن يكون بين 2 و 255 حرف' }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: '',
                rowSelector: '.col-md-6, .col-12'
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    }).on('core.form.valid', function () {
        form.submit();
    });
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
                $(this).closest('.col-md-6, .col-12').find('.invalid-feedback').remove();
            });
        });
    }
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

function escapeHtml(text) {
    const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
    return text.replace(/[&<>"']/g, function (m) { return map[m]; });
}

window.ContactResultsHandler = {
    showToast: showToast
};