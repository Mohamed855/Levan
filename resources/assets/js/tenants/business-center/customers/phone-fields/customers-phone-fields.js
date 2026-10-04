/**
 * Phone-number handling for the Customer form
 * ------------------------------------------
 * يعتمد على: jQuery
 *
 * يعرّف:
 *   addPhoneField()
 *   removePhoneField(btn)
 *   initializePhoneRestrictions()
 */

(function ($) {
    'use strict';

    let phoneCounter = $('#additional-phones .phone-entry').length || 1;

    function initializePhoneRestrictions() {
        $('.phone-input')
            .off('input.phoneRestrict')
            .attr('inputmode', 'numeric');
    }

    function addPhoneField() {
        const $container = $('#additional-phones');
        const idx = phoneCounter++;

        $container.append(`
            <div class="phone-entry mb-2">
                <div class="input-group">
                    <input type="text" name="phones[${idx}][phone]" class="form-control phone-input additional-phone" placeholder="رقم الهاتف" maxlength="10">
                    <select name="phones[${idx}][type]" class="form-select" style="max-width: 100px;">
                        <option value="mobile">جوال</option>
                        <option value="home">منزل</option>
                        <option value="work">عمل</option>
                        <option value="other">أخرى</option>
                    </select>
                    <button type="button" class="btn btn-outline-danger js-remove-phone-field-btn">
                        <i class="ti ti-minus"></i>
                    </button>
                </div>
            </div>
        `);

        initializePhoneRestrictions();
    }

    function removePhoneField(btn) {
        $(btn).closest('.phone-entry').remove();
    }

    // ─────────────────────── الأحداث ───────────────────────
    $(function () {
        initializePhoneRestrictions();

        // إتاحة الدوال عالمياً حتى تعمل الـ onclick الموجودة فى الـ Blade
        window.addPhoneField = addPhoneField;
        window.removePhoneField = removePhoneField;
        window.initializePhoneRestrictions = initializePhoneRestrictions;

        $(document).on('click', '.js-add-phone-field-btn', function () {
            addPhoneField();
        });

        $(document).on('click', '.js-remove-phone-field-btn', function () {
            removePhoneField(this);
        });
    });

})(jQuery);
