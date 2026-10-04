/**
 * إدارة خطابات الشركات - الصفحة الرئيسية
 * ==========================================
 * يحتوي هذا الملف على جميع الوظائف المطلوبة لإدارة صفحة خطابات الشركات الرئيسية
 * بما في ذلك تهيئة الجدول (DataTable)، الفلترة المتقدمة، معاينة وإرسال الخطابات،
 * والتعامل مع الإحصائيات.
 */

'use strict';

// =================================================================
// المتغيرات العامة (Global Variables)
// =================================================================

// سيتم تخزين كائن DataTable هنا بعد تهيئته
let companyLettersTable;

// =================================================================
// نقطة بداية تنفيذ الكود عند تحميل الصفحة (Document Ready)
// =================================================================

 $(function () {
    initializeComponents();
    bindEvents();
});

// =================================================================
// تهيئة المكونات الأساسية (Initialization Functions)
// =================================================================

/**
 * تهيئة المكونات الأساسية مثل Select2 و Flatpickr
 */
function initializeComponents() {
    // تهيئة مكتبة Select2 للقوائم المنسدلة
    $('.select2').select2({
        allowClear: true,
        placeholder: function() {
            // استخدام العنصر النائب المحدد في HTML
            return $(this).data('placeholder');
        },
        dir: 'rtl', // دعم اتجاه النص من اليمين لليسار
        width: '100%',
        language: {
            noResults: function() {
                return "لا توجد نتائج";
            },
            searching: function() {
                return "جاري البحث...";
            }
        }
    });

}


// =================================================================
// ربط الأحداث (Event Binding)
// =================================================================

/**
 * ربط الأحداث بالعناصر المختلفة في الصفحة
 */
function bindEvents() {
    // فلتر الشركة: تحديث خيارات المحفظة عند تغيير الشركة
    $('#filter-company').on('change', function() {
        const selectedCompanyId = $(this).val();
        const portfolioSelect = $('#filter-portfolio');
        
        // إعادة تعيين قيمة المحفظة
        portfolioSelect.val('').trigger('change.select2');

        // فلترة خيارات المحفظة بناءً على الشركة المحددة
        portfolioSelect.find('option').each(function() {
            const optionCompanyId = $(this).data('company');
            // إظهار الخيار إذا لم يتم تحديد شركة أو إذا تطابق مع الشركة
            if (!selectedCompanyId || optionCompanyId == selectedCompanyId || $(this).val() === '') {
                $(this).prop('disabled', false);
            } else {
                $(this).prop('disabled', true);
            }
        });
        
        // إعادة تهيئة Select2 لتطبيق التغييرات
        portfolioSelect.select2('destroy').select2();
    });

    // استخدام event delegation للتعامل مع الأزرار الديناميكية داخل الجدول
    // زر "عرض التفاصيل"
    $('#company-letters-table').on('click', '.btn-view-letter', function(e) {
        e.preventDefault();
        const letterId = $(this).data('id');
        showLetterDetails(letterId);
    });
}

/**
 * دالة مساعدة لاستخراج قيمة التاريخ من حقل Flatpickr
 * @param {string} range - 'from' أو 'to'
 * @returns {string|null} قيمة التاريخ أو null
 */
function getFlatpickrDate(range) {
    const dateStr = $('#filter-date').val();
    if (!dateStr) return null;

    const dates = dateStr.split(' to ');
    if (range === 'from' && dates[0]) {
        return dates[0];
    }
    if (range === 'to' && dates[1]) {
        return dates[1];
    }
    // إذا كان هناك تاريخ واحد فقط (تم اختياره بدون نطاق)
    if (range === 'from' && dates.length === 1) {
        return dates[0];
    }
    return null;
}

// =================================================================
// وظائف الإجراءات (Action Functions)
// =================================================================

/**
 * عرض تفاصيل الخطاب في مودال مخصص
 * @param {number} letterId - معرف الخطاب
 */
function showLetterDetails(letterId) {
    // عرض مؤشر تحميل
    showLoadingIndicator();

    const url = window.routes.companyLettersShow.replace(':id', letterId);

    $.ajax({
        url: url,
        type: 'GET',
        success: function (response) {
            hideLoadingIndicator();
            // إنشاء مودال Bootstrap ديناميكياً
            const modalId = 'letterDetailsModal';
            // التأكد من عدم وجود مودال مسبق
            $(`#${modalId}`).remove();

            const modalHtml = `
                <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="${modalId}Label">تفاصيل الخطاب</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <!-- سيتم وضع محتوى الرد هنا -->
                                ${response.html}
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            $('body').append(modalHtml);
            const modal = new bootstrap.Modal(document.getElementById(modalId));
            modal.show();

            // ربط أحداث المودال إذا لزم الأمر (مثل إرسال البريد)
            bindModalEvents(letterId, modal);

        },
        error: function (xhr) {
            hideLoadingIndicator();
            handleAjaxError(xhr, 'فشل في تحميل تفاصيل الخطاب');
        }
    });
}

/**
 * ربط الأحداث داخل المودال (مثل معاينة وإرسال البريد)
 * @param {number} letterId - معرف الخطاب
 * @param {object} modal - كائن المودال
 */
function bindModalEvents(letterId, modal) {
    const modalElement = modal._element;

    // حدث معاينة القالب
    $(modalElement).on('change', '#template-select', function() {
        const templateId = $(this).val();
        if (templateId) {
            previewEmailTemplate(letterId, templateId);
        } else {
            $('#preview-container').html('<p class="text-muted">يرجى اختيار قالب لمعاينته.</p>');
        }
    });

    // حدث إرسال البريد
    $(modalElement).on('click', '#send-email-btn', function() {
        const templateId = $('#template-select').val();
        const recipientEmail = $('#recipient-email').val();

        if (!templateId || !recipientEmail) {
            toastr.warning('يرجى اختيار القالب وإدخال البريد الإلكتروني');
            return;
        }

        sendEmail(letterId, templateId, recipientEmail, modal);
    });
}

/**
 * معاينة محتوى قالب البريد الإلكتروني
 * @param {number} letterId
 * @param {number} templateId
 */
function previewEmailTemplate(letterId, templateId) {
    const url = `${window.routes.companyLettersShow.replace(':id', letterId)}/preview-template`;
    const previewContainer = $('#preview-container');
    
    previewContainer.html('<div class="text-center"><i class="ti ti-loader ti-spin"></i> جاري المعاينة...</div>');

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            template_id: templateId
        },
        success: function(response) {
            if (response.success) {
                previewContainer.html(`
                    <h6>الموضوع: ${response.subject}</h6>
                    <hr>
                    <div class="border p-3 bg-light">
                        ${response.preview.replace(/\n/g, '<br>')}
                    </div>
                `);
            } else {
                previewContainer.html('<p class="text-danger">فشلت المعاينة.</p>');
                toastr.error(response.message || 'حدث خطأ أثناء المعاينة');
            }
        },
        error: function(xhr) {
            previewContainer.html('<p class="text-danger">فشلت المعاينة.</p>');
            handleAjaxError(xhr, 'حدث خطأ أثناء معاينة القالب');
        }
    });
}

/**
 * إرسال البريد الإلكتروني
 * @param {number} letterId
 * @param {number} templateId
 * @param {string} recipientEmail
 * @param {object} modal - كائن المودال لإغلاقه بعد الإرسال
 */
function sendEmail(letterId, templateId, recipientEmail, modal) {
    const url = `${window.routes.companyLettersShow.replace(':id', letterId)}/send`;
    const sendButton = $('#send-email-btn');
    const originalButtonText = sendButton.html();

    // تعطيل الزر وإظهار حالة التحميل
    sendButton.prop('disabled', true).html('<i class="ti ti-loader ti-spin"></i> جاري الإرسال...');

    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: window.csrfToken,
            template_id: templateId,
            recipient_email: recipientEmail
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                modal.hide(); // إغلاق المودال
                // تحديث الجدول لعرض الحالة الجديدة
                companyLettersTable.ajax.reload(null, false);
            } else {
                toastr.error(response.message);
            }
        },
        error: function(xhr) {
            handleAjaxError(xhr, 'فشل إرسال البريد');
        },
        complete: function() {
            // إعادة تفعيل الزر ونصه الأصلي
            sendButton.prop('disabled', false).html(originalButtonText);
        }
    });
}


// =================================================================
// وظائف مساعدة (Utility Functions)
// =================================================================

/**
 * عرض مؤشر تحميل عام
 */
function showLoadingIndicator() {
    if (typeof NProgress !== 'undefined') {
        NProgress.start();
    }
}

/**
 * إخفاء مؤشر التحميل العام
 */
function hideLoadingIndicator() {
    if (typeof NProgress !== 'undefined') {
        NProgress.done();
    }
}

/**
 * معالجة أخطاء AJAX بشكل موحد
 * @param {object} xhr - كائن XMLHttpRequest
 * @param {string} defaultMessage - رسالة الخطاء الافتراضية
 */
function handleAjaxError(xhr, defaultMessage) {
    let message = defaultMessage;
    if (xhr.responseJSON && xhr.responseJSON.message) {
        message = xhr.responseJSON.message;
    } else if (xhr.status === 404) {
        message = 'المورد المطلوب غير موجود.';
    } else if (xhr.status === 403) {
        message = 'ليس لديك صلاحية للقيام بهذا الإجراء.';
    } else if (xhr.status === 500) {
        message = 'حدث خطأ داخلي في الخادم.';
    }
    
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    } else {
        alert(message);
    }
}

// =================================================================
// لا حاجة لتصدير دوال للنطاق العام (Global Scope)
// لأننا استخدمنا event delegation لربط الأحداث
// =================================================================