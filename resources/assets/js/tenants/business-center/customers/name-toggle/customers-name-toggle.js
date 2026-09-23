(function ($) {
    'use strict';

    const splitIds = ['first_name', 'father_name', 'grandfather_name', 'family_name'];
    const HARD_VALIDATORS = ['notEmpty', 'stringLength'];

    const fvReady = () => typeof window.FormValidation1 !== 'undefined';

    function isRegistered(fieldName) {
        try {
            const fields = FormValidation1.getFields();
            return !!(fields && fields[fieldName]);
        } catch (e) {
            return false;
        }
    }

    function clearSplitValues() {
        splitIds.forEach(id => $('#' + id).val(''));
    }

    function touchOptionalFields() {
        ['father_name', 'grandfather_name'].forEach(id => {
            if (!isRegistered(id)) return;
            FormValidation1.updateFieldStatus(id, 'NotValidated');
        });
    }

    function setValidators(field, enable) {
        if (!isRegistered(field)) return;
        HARD_VALIDATORS.forEach(v => {
            enable
                ? FormValidation1.enableValidator(field, v)
                : FormValidation1.disableValidator(field, v);
        });
        FormValidation1.updateFieldStatus(field, 'NotValidated');
    }

    function toggleNameFields() {
        if (!fvReady()) return;

        const hasSplit = Number($('#portfolio_id option:selected').data('has-split')) === 1;

        if (hasSplit) {
            $('#full-name-wrapper').addClass('d-none');
            $('#split-names-wrapper').removeClass('d-none').css('display', 'flex');
            $('#name').val('');
            setValidators('name', false);
            setValidators('first_name', true);
            setValidators('family_name', true);
        } else {
            $('#split-names-wrapper').addClass('d-none').css('display', '');
            $('#full-name-wrapper').removeClass('d-none');
            clearSplitValues();
            setValidators('first_name', false);
            setValidators('family_name', false);
            touchOptionalFields();
            setValidators('name', true);
        }
    }

    $(function () {
        const wait = setInterval(() => {
            if (fvReady()) {
                // ✅ أوقف الـ interval أولاً قبل أي شيء
                clearInterval(wait);

                try {
                    toggleNameFields();
                } catch (e) {
                    console.warn('toggleNameFields error:', e);
                }

                $('#portfolio_id').on('change', function () {
                    try {
                        toggleNameFields();
                    } catch (e) {
                        console.warn('toggleNameFields onChange error:', e);
                    }
                });
            }
        }, 50);
    });

})(jQuery);
