/*
|--------------------------------------------------------------------------
| Contracts Wizard Validation & Dynamic Multi-Type Commission Slices
|--------------------------------------------------------------------------
*/

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    (function () {
        /*
        |--------------------------------------------------------------------------
        | المتغيرات الأساسية
        |--------------------------------------------------------------------------
        */
        const wizard = document.querySelector('#wizard-validation');
        if (!wizard) return;

        const stepper = new Stepper(wizard, { linear: false });
        const btnNext = [].slice.call(wizard.querySelectorAll('.btn-next'));
        const btnPrev = [].slice.call(wizard.querySelectorAll('.btn-prev'));
        const btnSubmit = wizard.querySelector('.btn-submit');

        let commissionIndex = window.initialCommissionIndex || 0;

        const $wrapper = $('#commissions-wrapper');
        const $commissionTypes = $('#commission_types');
        const $commissionTypeHidden = $('#commission_type');
        const $commissionTypeButtons = $('#commission-type-buttons');
        const $company = $('#company_id');
        const $start = $('#start_date');
        const $end = $('#end_date');
        const $durationDays = $('#duration_readonly');
        const $durationMonths = $('#duration_months');

        const form = document.getElementById('wizard-validation-form');
        const formValidator = {
            revalidateField(fieldName) {
                return window.SharedFormValidation.validateFields(form, [fieldName]);
            },
            validateField(fieldName) {
                return window.SharedFormValidation.validateFields(form, [fieldName])
                    .then((valid) => valid ? 'Valid' : 'Invalid');
            },
            updateFieldStatus(fieldName, status) {
                if (status === 'Valid') {
                    window.SharedFormValidation.setFieldValid(form, fieldName);
                }
            }
        };

        /*
        |--------------------------------------------------------------------------
        | التهيئة الأولية
        |--------------------------------------------------------------------------
        */
        initializeComponents();

        /*
        |--------------------------------------------------------------------------
        | وظائف التهيئة
        |--------------------------------------------------------------------------
        */
        function initializeComponents() {
            initializeDateRestrictions();
            initSelect2($('#company_id'));
            // initSelect2Multiple($commissionTypes);
            setupEventListeners();

            $('.commission-row').each(function () {
                const $row = $(this);
                const type = $row.data('type');

                $row.find('.select2-commission').each(function () {
                    initSelect2($(this));
                });

                updateRowFields($row, type, false);
            });

            commissionIndex = Math.max(commissionIndex, $('.commission-row').length);
            toggleCommissionTypeButtons();
            recalcDurations();

            const initialTypes = $commissionTypes.val() || [];
            if (initialTypes.length > 0) {
                renderCommissionTypeButtons(initialTypes);
            }
        }

        function initializeDateRestrictions() {
            const startDate = $start.val();
            if (startDate) {
                $end.attr('min', startDate);
            }
        }

        function initSelect2($elements) {
            $elements.each(function () {
                const $element = $(this);
                if ($element.hasClass('select2-hidden-accessible')) {
                    $element.select2('destroy');
                }
                $element.select2({
                    dir: 'rtl',
                    placeholder: $element.data('placeholder') || 'اختر...',
                    allowClear: true,
                    width: '100%',
                    language: {
                        noResults: function () { return 'لا توجد نتائج'; },
                        searching: function () { return 'جاري البحث...'; }
                    }
                });
            });
        }

        function initSelect2Multiple($element) {
            if ($element.hasClass('select2-hidden-accessible')) {
                $element.select2('destroy');
            }
            $element.select2({
                dir: 'rtl',
                placeholder: $element.data('placeholder') || 'اختر...',
                allowClear: true,
                width: '100%',
                multiple: true,
                language: {
                    noResults: function () { return 'لا توجد نتائج'; },
                    searching: function () { return 'جاري البحث...'; }
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | إعداد الأحداث
        |--------------------------------------------------------------------------
        */
        function setupEventListeners() {
            setupStepperEvents();
            setupFieldEvents();
            setupCommissionEvents();
        }

        function setupStepperEvents() {
            btnNext.forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    handleNextStep();
                });
            });

            btnPrev.forEach(function (btn) {
                btn.addEventListener('click', function (e) {
                    e.preventDefault();
                    handlePreviousStep();
                });
            });

            // The shared validator owns the native submit event and final validation.
        }

        function setupFieldEvents() {
            $company.off('change.validation').on('change.validation', function () {
                formValidator.revalidateField('company_id');
                clearSelect2Error($company, 'company_id');
            });

            $commissionTypes.off('change.validation').on('change.validation', function () {
                const selectedTypes = $(this).val() || [];

                if (selectedTypes.length > 0) {
                    $commissionTypeHidden.val(selectedTypes[0]);
                } else {
                    $commissionTypeHidden.val('');
                }

                formValidator.revalidateField('commission_types[]');
                clearSelect2Error($commissionTypes, 'commission_types[]');

                renderCommissionTypeButtons(selectedTypes);
                toggleCommissionTypeButtons();
            });

            $start.off('change.validation').on('change.validation', function () {
                handleStartDateChange();
            });

            $end.off('change.validation').on('change.validation', function () {
                handleEndDateChange();
            });
        }

        function setupCommissionEvents() {
            $wrapper.off('click.commission', '.remove-commission-btn')
                .on('click.commission', '.remove-commission-btn', function (e) {
                    e.preventDefault();
                    $(this).closest('.commission-row').remove();
                    updateCommissionIndices();
                    updateCommissionNumbers();
                });
        }

        /*
        |--------------------------------------------------------------------------
        | معالجات الخطوات
        |--------------------------------------------------------------------------
        */
        function handleNextStep() {
            const currentIndex = stepper._currentIndex;

            if (currentIndex === 0) {
                validateStep1().then(function (isValid) {
                    if (isValid) {
                        stepper.next();
                    }
                });
            } else if (currentIndex === 1) {
                validateStep2().then(function (isValid) {
                    if (isValid) {
                    }
                });
            }
        }

        function handlePreviousStep() {
            if (stepper._currentIndex === 1) {
                stepper.previous();
            }
        }

        function handleFormSubmit() {
            validateStep2().then(function (isValid) {
                if (isValid && validateCommissionSlices()) {
                    submitForm();
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | وظائف التحقق
        |--------------------------------------------------------------------------
        */
        function validateStep1() {
            return new Promise(function (resolve) {
                formValidator.validateField('company_id').then(function (result1) {
                    formValidator.validateField('start_date').then(function (result2) {
                        formValidator.validateField('end_date').then(function (result3) {
                            const isValid = result1 === 'Valid' && result2 === 'Valid' && result3 === 'Valid';
                            resolve(isValid);
                        });
                    });
                });
            });
        }

        function validateStep2() {
            return new Promise(function (resolve) {
                formValidator.validateField('commission_types[]').then(function (result) {
                    const isValid = result === 'Valid';
                    resolve(isValid);
                });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | معالجات التاريخ
        |--------------------------------------------------------------------------
        */
        function handleStartDateChange() {
            const startDate = $start.val();
            const endDate = $end.val();

            if (startDate) {
                $end.attr('min', startDate);

                if (endDate && endDate < startDate) {
                    $end.val('');
                    $durationDays.val(0);
                    $durationMonths.val('');
                }
            } else {
                $end.removeAttr('min');
            }

            formValidator.revalidateField('start_date');

            if ($end.val()) {
                setTimeout(function () {
                    formValidator.revalidateField('end_date');
                }, 50);
            }

            recalcDurations();
        }

        function handleEndDateChange() {
            setTimeout(function () {
                formValidator.revalidateField('end_date');
            }, 50);

            recalcDurations();
        }

        /*
        |--------------------------------------------------------------------------
        | وظائف المساعدة
        |--------------------------------------------------------------------------
        */
        function clearSelect2Error($select, fieldName) {
            if (!$select.val()) return;

            formValidator.updateFieldStatus(fieldName, 'Valid');
            $select.removeClass('is-invalid');

            const $messageContainer = $select.closest('.has-validation').find('.invalid-feedback, .fv-plugins-message-container');
            $messageContainer.empty();

            const $select2Container = $select.next('.select2').find('.select2-selection');
            $select2Container.removeClass('is-invalid');
        }

        function recalcDurations() {
            const startDate = $start.val();
            const endDate = $end.val();

            if (!startDate || !endDate) {
                $durationDays.val(0);
                $durationMonths.val('');
                toggleCommissionTypeButtons();
                return;
            }

            const startDateObj = new Date(startDate);
            const endDateObj = new Date(endDate);

            if (!(endDateObj >= startDateObj)) {
                $durationDays.val(0);
                $durationMonths.val('');
                toggleCommissionTypeButtons();
                return;
            }

            const timeDifference = endDateObj - startDateObj;
            const daysDifference = Math.round(timeDifference / (1000 * 60 * 60 * 24)) + 1;

            $durationDays.val(daysDifference);

            const monthsDifference = Math.max(1,
                (endDateObj.getFullYear() - startDateObj.getFullYear()) * 12 +
                (endDateObj.getMonth() - startDateObj.getMonth())
            );
            $durationMonths.val(monthsDifference);

            toggleCommissionTypeButtons();
        }

        /*
        |--------------------------------------------------------------------------
        | وظائف إدارة أزرار أنواع العمولة
        |--------------------------------------------------------------------------
        */
        function renderCommissionTypeButtons(selectedTypes) {
            const $buttonsContainer = $commissionTypeButtons.find('.d-flex');
            $buttonsContainer.empty();

            const typeLabels = {
                'procedure': 'حسب الإجراء',
                'duration': 'حسب المدة',
                'delinquency': 'حسب مدة التعثر'
            };

            selectedTypes.forEach(function (type) {
                const label = typeLabels[type] || type;
                const $button = $(`
                    <button type="button" class="btn btn-sm btn-outline-primary add-commission-type-btn" 
                        data-type="${type}" title="إضافة شريحة ${label}">
                        <i class="fas fa-plus-circle me-1"></i>${label}
                    </button>
                `);

                $button.on('click', function () {
                    addCommissionRow(type);
                });

                $buttonsContainer.append($button);
            });
        }

        function toggleCommissionTypeButtons() {
            const selectedTypes = $commissionTypes.val() || [];
            const startDate = $start.val();
            const endDate = $end.val();

            const validDates = !!startDate && !!endDate && (new Date(endDate) >= new Date(startDate));
            const canShow = selectedTypes.length > 0 && validDates;

            $commissionTypeButtons.toggleClass('d-none', !canShow);
        }

        /*
        |--------------------------------------------------------------------------
        | وظائف إدارة العمولات
        |--------------------------------------------------------------------------
        */
        function hardResetSlices() {
            $wrapper.empty();
            commissionIndex = 0;
            $('.is-invalid').removeClass('is-invalid');
            $('.fv-plugins-message-container').empty();
            $commissionTypeButtons.addClass('d-none').find('.d-flex').empty();
        }

        function addCommissionRow(type) {
            const template = $('#commission-row-template').html();
            if (!template) return;

            const typeLabels = {
                'procedure': 'حسب الإجراء',
                'duration': 'حسب المدة',
                'delinquency': 'حسب مدة التعثر'
            };

            const typeLabel = typeLabels[type] || type;
            const typeRows = $(`.commission-row[data-type="${type}"]`);
            const typeNumber = typeRows.length + 1;

            const rowHtml = template
                .replace(/__INDEX__/g, commissionIndex)
                .replace(/__TYPE__/g, type)
                .replace(/__TYPE_LABEL__/g, typeLabel)
                .replace(/__NUMBER__/g, typeNumber);

            $wrapper.append(rowHtml);

            const $newRow = $('.commission-row').last();
            updateRowFields($newRow, type, true);

            $newRow.find('.select2-commission').each(function () {
                initSelect2($(this));
            });

            commissionIndex++;
        }

        function updateRowFields($row, type, shouldClearValues) {
            const $procedureColumn = $row.find('.procedure-type-col');
            const $procedureSelect = $row.find('select[name*="[procedure_type]"]');
            const $durationFromColumn = $row.find('.duration-from-col');
            const $durationToColumn = $row.find('.duration-to-col');
            const $durationFromInput = $row.find('input[name*="[duration_from]"]');
            const $durationToInput = $row.find('input[name*="[duration_to]"]');
            const $rateColumn = $row.find('.commission-rate-col');

            $procedureColumn.hide();
            $durationFromColumn.hide();
            $durationToColumn.hide();

            if (shouldClearValues) {
                $procedureSelect.val('');
                $durationFromInput.val('');
                $durationToInput.val('');
            }

            let visibleFieldsCount = 1;

            if (type === 'procedure') {
                $procedureColumn.show().removeClass('col-md-4 col-md-6 col-md-12').addClass('col-md-6');
                $rateColumn.removeClass('col-md-4 col-md-6 col-md-12').addClass('col-md-6');
                visibleFieldsCount = 2;
            } else if (type === 'duration' || type === 'delinquency') {
                $durationFromColumn.show().removeClass('col-md-4 col-md-6 col-md-12').addClass('col-md-4');
                $durationToColumn.show().removeClass('col-md-4 col-md-6 col-md-12').addClass('col-md-4');
                $rateColumn.removeClass('col-md-4 col-md-6 col-md-12').addClass('col-md-4');
                visibleFieldsCount = 3;
            } else {
                $rateColumn.removeClass('col-md-4 col-md-6 col-md-12').addClass('col-md-12');
            }

            $row.find('input, select').removeClass('is-invalid');
            $row.find('.invalid-feedback').remove();
        }

        function updateCommissionIndices() {
            $('.commission-row').each(function (index) {
                $(this).attr('data-index', index);
                $(this).find('input, select').each(function () {
                    const fieldName = $(this).attr('name');
                    if (fieldName) {
                        $(this).attr('name', fieldName.replace(/\[\d+\]/, '[' + index + ']'));
                    }
                });
            });
            commissionIndex = $('.commission-row').length;
        }

        function updateCommissionNumbers() {
            const types = {};

            $('.commission-row').each(function () {
                const $row = $(this);
                const type = $row.data('type');

                if (!types[type]) {
                    types[type] = 0;
                }
                types[type]++;

                $row.find('.commission-number').text(types[type]);
            });
        }

        /*
        |--------------------------------------------------------------------------
        | التحقق من العمولات
        |--------------------------------------------------------------------------
        */
        function validateCommissionSlices() {
            const commissionRows = $('.commission-row');

            if (commissionRows.length === 0) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('يجب إضافة شريحة عمولة واحدة على الأقل');
                } else {
                    alert('يجب إضافة شريحة عمولة واحدة على الأقل');
                }
                return false;
            }

            let isValid = true;

            commissionRows.each(function () {
                const $row = $(this);
                const sliceType = $row.data('type');

                const $rateInput = $row.find('input[name*="[commission_rate]"]');
                const rateValue = parseFloat($rateInput.val());

                if (!$rateInput.val() || isNaN(rateValue) || rateValue <= 0 || rateValue > 100) {
                    $rateInput.addClass('is-invalid');
                    isValid = false;
                } else {
                    $rateInput.removeClass('is-invalid');
                }

                if (sliceType === 'duration' || sliceType === 'delinquency') {
                    const $fromInput = $row.find('input[name*="[duration_from]"]');
                    const $toInput = $row.find('input[name*="[duration_to]"]');
                    const fromValue = parseInt($fromInput.val(), 10);
                    const toValue = parseInt($toInput.val(), 10);

                    if (!$fromInput.val() || isNaN(fromValue) || fromValue <= 0) {
                        $fromInput.addClass('is-invalid');
                        isValid = false;
                    } else {
                        $fromInput.removeClass('is-invalid');
                    }

                    if (!$toInput.val() || isNaN(toValue) || toValue < fromValue) {
                        $toInput.addClass('is-invalid');
                        isValid = false;
                    } else {
                        $toInput.removeClass('is-invalid');
                    }
                } else if (sliceType === 'procedure') {
                    const $procedureSelect = $row.find('select[name*="[procedure_type]"]');

                    if (!$procedureSelect.val()) {
                        $procedureSelect.addClass('is-invalid');
                        isValid = false;
                    } else {
                        $procedureSelect.removeClass('is-invalid');
                    }
                }
            });

            if (!isValid) {
                if (typeof toastr !== 'undefined') {
                    toastr.error('يرجى تصحيح الأخطاء في شرائح العمولة');
                } else {
                    alert('يرجى تصحيح الأخطاء في شرائح العمولة');
                }
            }

            return isValid;
        }

        /*
        |--------------------------------------------------------------------------
        | إرسال النموذج
        |--------------------------------------------------------------------------
        */
        function submitForm() {
            const form = document.getElementById('wizard-validation-form');
            if (!form) return;

            if (typeof NProgress !== 'undefined') {
                NProgress.start();
            }

            $(form).find('button[type="submit"], .btn-submit').prop('disabled', true);

            form.onsubmit = null;
            form.submit();
        }

    })();
});
