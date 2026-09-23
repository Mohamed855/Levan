/**
 * Dynamic extra-fields loader for the Customer form
 * -------------------------------------------------
 * يعتمد على: jQuery – Toastr – FormValidation2
 *
 * يجب أن توفّر الصفحة:
 *   window.routes.fieldsByPortfolio   (string)
 *   window.initialDynamic             (object) – اختيارى (فقط فى صفحة التعديل)
 */

(function ($) {
    'use strict';

    function loadDynamicFields(portfolioId, initial = {}) {
        const $wrap = $('#dynamic-fields').empty();
        if (!portfolioId) return;

        // إزالة الحقول السابقة من FormValidation2
        if (window.FormValidation2) {
            Object.keys(window.dynamicFieldNames || {}).forEach(name => {
                window.FormValidation2.removeField(name, false);
            });
            window.dynamicFieldNames = {};
        }

        $.get(window.routes.fieldsByPortfolio, { portfolio_id: portfolioId })
            .done(res => {
                if (!res.success) return;

                res.data.forEach(field => {
                    const fid = field.id;
                    const fname = field.name;
                    const inputName = `dynamic[${fid}]`;
                    const value = initial[fid] ?? '';
                    const isReq = field.is_required;
                    const type = field.field_type;   // text | numeric | date

                    // إنشاء عنصر الإدخال
                    let input;
                    switch (type) {
                        case 'numeric':
                            input = `<input type="number" step="0.01" name="${inputName}" class="form-control" value="${value}">`;
                            break;
                        case 'date':
                            input = `<input type="date" name="${inputName}" class="form-control" value="${value}">`;
                            break;
                        default:
                            input = `<input type="text" name="${inputName}" class="form-control" value="${value}">`;
                    }

                    // حقن فى الـ DOM
                    $wrap.append(`
                        <div class="col-sm-6 mb-3">
                            <label class="form-label">${fname}${isReq ? '<span class="text-danger">*</span>' : ''}</label>
                            ${input}
                        </div>
                    `);

                    // إضافة التحقق إن وُجد
                    if (window.FormValidation2) {
                        const validators = {};
                        if (isReq) validators.notEmpty = { message: 'هذا الحقل مطلوب' };
                        if (type === 'numeric') {
                            validators.numeric = { message: 'يجب إدخال رقم صالح' };
                        } else if (type === 'date') {
                            validators.date = { format: 'YYYY-MM-DD', message: 'صيغة التاريخ غير صحيحة' };
                        }
                        window.FormValidation2.addField(inputName, { validators });
                        window.dynamicFieldNames[inputName] = true;
                    }
                });
            })
            .fail(() => toastr.error('تعذّر تحميل الحقول'));
    }

    // ─────────────────────── الأحداث ───────────────────────
    $(function () {
        // تحميل ابتدائى (فى صفحة التعديل فقط)
        const firstPortfolio = $('#portfolio_id').val();
        if (firstPortfolio) {
            loadDynamicFields(firstPortfolio, window.initialDynamic || {});
        }

        // عند تغيير المحفظة
        $('#portfolio_id').on('change', function () {
            loadDynamicFields(this.value);
        });
    });

    // متاح عالمياً
    window.loadDynamicFields = loadDynamicFields;

})(jQuery);
