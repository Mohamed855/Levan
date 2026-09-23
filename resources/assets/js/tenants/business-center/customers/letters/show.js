/**
 * ═══════════════════════════════════════════════════════════
 * خطابات الشركات - صفحة التفاصيل
 * ═══════════════════════════════════════════════════════════
 */

'use strict';

/*
|--------------------------------------------------------------------------
| تهيئة الصفحة
|--------------------------------------------------------------------------
*/
$(document).ready(function () {
    initializeComponents();
    bindEvents();
});

/*
|--------------------------------------------------------------------------
| تهيئة المكونات
|--------------------------------------------------------------------------
*/
function initializeComponents() {
    $('#template_id').select2({
        placeholder: 'اختر القالب',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });
}

/*
|--------------------------------------------------------------------------
| ربط الأحداث
|--------------------------------------------------------------------------
*/
function bindEvents() {
    // تفعيل الأزرار عند اختيار القالب
    $('#template_id').on('change', function () {
        const hasTemplate = $(this).val() !== '';
        $('#preview-btn').prop('disabled', !hasTemplate);
    });

    // زر المعاينة
    $('#preview-btn').on('click', previewTemplate);

    // نموذج الإرسال
    $('#send-email-form').on('submit', sendEmail);
}

/*
|--------------------------------------------------------------------------
| معاينة القالب
|--------------------------------------------------------------------------
*/
function previewTemplate() {
    const templateId = $('#template_id').val();

    if (!templateId) {
        toastr.warning('يرجى اختيار قالب أولاً');
        return;
    }

    $.ajax({
        url: window.routes.previewTemplate,
        method: 'POST',
        data: {
            _token: window.csrfToken,
            template_id: templateId
        },
        beforeSend: function () {
            $('#preview-btn').prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i> جاري التحميل...');
        },
        success: function (response) {
            if (response.success) {
                $('#preview-subject').text(response.subject);
                $('#preview-content').html(response.preview.replace(/\n/g, '<br>'));
                $('#template-preview').removeClass('d-none');
                $('#send-btn').prop('disabled', false);
                toastr.success('تم تحميل المعاينة بنجاح');
            }
        },
        error: function (xhr) {
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء معاينة القالب';
            toastr.error(message);
        },
        complete: function () {
            $('#preview-btn').prop('disabled', false).html('<i class="ti ti-eye me-1"></i> معاينة القالب');
        }
    });
}

/*
|--------------------------------------------------------------------------
| إرسال البريد
|--------------------------------------------------------------------------
*/
function sendEmail(e) {
    e.preventDefault();

    const templateId = $('#template_id').val();
    const recipientEmail = $('#recipient_email').val();

    if (!templateId || !recipientEmail) {
        toastr.warning('يرجى إكمال جميع الحقول المطلوبة');
        return;
    }

    Swal.fire({
        title: 'تأكيد الإرسال',
        text: 'هل أنت متأكد من إرسال البريد إلى ' + recipientEmail + '؟',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، إرسال',
        cancelButtonText: 'إلغاء',
        customClass: {
            confirmButton: 'btn btn-primary me-2',
            cancelButton: 'btn btn-label-secondary'
        },
        buttonsStyling: false
    }).then((result) => {
        if (result.isConfirmed) {
            performSend(templateId, recipientEmail);
        }
    });
}

/*
|--------------------------------------------------------------------------
| تنفيذ الإرسال
|--------------------------------------------------------------------------
*/
function performSend(templateId, recipientEmail) {
    $.ajax({
        url: window.routes.sendEmail,
        method: 'POST',
        data: {
            _token: window.csrfToken,
            template_id: templateId,
            recipient_email: recipientEmail
        },
        beforeSend: function () {
            $('#send-btn').prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i> جاري الإرسال...');
        },
        success: function (response) {
            if (response.success) {
                Swal.fire({
                    title: 'تم الإرسال بنجاح!',
                    text: 'تم إرسال البريد إلى الشركة',
                    icon: 'success',
                    confirmButtonText: 'العودة للقائمة',
                    customClass: {
                        confirmButton: 'btn btn-success'
                    },
                    buttonsStyling: false
                }).then(() => {
                    window.location.href = window.routes.companyLettersIndex;
                });
            } else {
                toastr.error(response.message || 'فشل إرسال البريد');
                $('#send-btn').prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال البريد');
            }
        },
        error: function (xhr) {
            const message = xhr.responseJSON?.message || 'حدث خطأ أثناء إرسال البريد';
            toastr.error(message);
            $('#send-btn').prop('disabled', false).html('<i class="ti ti-send me-1"></i> إرسال البريد');
        }
    });
}