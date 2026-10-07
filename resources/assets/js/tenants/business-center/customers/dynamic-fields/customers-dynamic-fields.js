/**
 * Dynamic extra-fields loader for the Customer form
 * -------------------------------------------------
 * يعتمد على: jQuery – Toastr – SharedFormValidation
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
                            input = `<input type="number" step="0.01" name="${inputName}" class="form-control" data-form-control ${isReq ? 'required' : ''} value="${value}">`;
                            break;
                        case 'date':
                            input = `<input type="date" name="${inputName}" class="form-control" data-form-control ${isReq ? 'required' : ''} value="${value}">`;
                            break;
                        default:
                            input = `<input type="text" name="${inputName}" class="form-control" data-form-control ${isReq ? 'required' : ''} value="${value}">`;
                    }

                    // حقن فى الـ DOM
                    $wrap.append(`
                        <div class="col-sm-6 mb-3" data-form-field data-field-name="${inputName}">
                            <label class="form-label">${fname}${isReq ? '<span class="text-danger">*</span>' : ''}</label>
                            ${input}
                        </div>
                    `);

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
