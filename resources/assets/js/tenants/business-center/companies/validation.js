/*
|--------------------------------------------------------------------------
| Companies Single-Step Wizard + Dynamic Authorized People
|--------------------------------------------------------------------------
| One-step wizard with client-side validation for company name and dynamic
| add/remove rows for authorized people with default person selection.
*/

'use strict';

document.addEventListener('DOMContentLoaded', function () {
    (function () {
        const wizardValidation = document.querySelector('#wizard-validation');
        if (!wizardValidation) return;

        const nextButtons = [].slice.call(wizardValidation.querySelectorAll('.btn-next'));
        const prevButtons = [].slice.call(wizardValidation.querySelectorAll('.btn-prev'));
        const validationStepper = new Stepper(wizardValidation, { linear: false });

        const isUpdateForm = document.querySelector('input[name="_method"]')?.value === 'PUT';

        let fv = FormValidation.formValidation(wizardValidation, {
            fields: {
                name: {
                    validators: {
                        notEmpty: { message: 'يرجى إدخال اسم الشركة' },
                        stringLength: { min: 2, max: 255, message: 'اسم الشركة يجب أن يكون بين 2 و 255 حرف' }
                    }
                },
                logo: {
                    validators: {
                        notEmpty: {
                            enabled: !isUpdateForm,
                            message: 'يرجى إختيار شعار الشركة'
                        },
                        file: {
                            extension: 'jpg,jpeg,png,webp',
                            type: 'image/jpeg,image/png,image/webp',
                            maxSize: 2097152,
                            message: 'يرجى اختيار ملف صورة صالح (JPG, JPEG, PNG, WebP) بحجم أقصى 2MB'
                        }
                    }
                },
                target_percentage: {
                    validators: {
                        notEmpty: {
                            enabled: !isUpdateForm,
                            message: 'يرجى إدخال نسبة المستهدف'
                        },
                        numeric: {
                            message: 'نسبة المستهدف يجب أن تكون رقماً'
                        },
                        between: {
                            min: 0,
                            max: 100,
                            message: 'نسبة المستهدف يجب أن تكون بين 0 و 100'
                        }
                    }
                }
            },
            plugins: {
                trigger: new FormValidation.plugins.Trigger(),
                bootstrap5: new FormValidation.plugins.Bootstrap5({
                    eleValidClass: '',
                    rowSelector: '.col-md-4, .col-12, .authorized-person-item .col-md-3, .authorized-person-item .col-md-2'
                }),
                autoFocus: new FormValidation.plugins.AutoFocus(),
                submitButton: new FormValidation.plugins.SubmitButton()
            }
        }).on('core.form.valid', function () {
            updateDefaultPersonValues();
            const form = wizardValidation.querySelector('#wizard-validation-form');
            if (form) form.submit();
        });

        function addPersonValidation(index) {
            const phoneFieldName = `authorized_people[${index}][phone]`;
            const emailFieldName = `authorized_people[${index}][email]`;

            fv.addField(phoneFieldName, {
                validators: {
                    stringLength: {
                        min: 10,
                        max: 10,
                        message: 'رقم الهاتف يجب أن يكون 10 أرقام بالضبط'
                    }
                }
            });

            fv.addField(emailFieldName, {
                validators: {
                    emailAddress: {
                        message: 'يرجى إدخال بريد إلكتروني صحيح'
                    }
                }
            });
        }

        function removePersonValidation(index) {
            const phoneFieldName = `authorized_people[${index}][phone]`;
            const emailFieldName = `authorized_people[${index}][email]`;

            fv.removeField(phoneFieldName);
            fv.removeField(emailFieldName);
        }

        nextButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                fv.validate();
            });
        });

        prevButtons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                return;
            });
        });

        const list = document.getElementById('authorized-people-list');
        const addBtn = document.getElementById('btn-add-person');

        function nextIndex() {
            const items = list.querySelectorAll('.authorized-person-item');
            return items.length;
        }

        function updateDefaultPersonLogic() {
            const items = list.querySelectorAll('.authorized-person-item');
            const totalPeople = items.length;

            items.forEach((item, index) => {
                const radioContainer = item.querySelector('.form-check');
                const radio = item.querySelector('.default-person-radio');

                if (totalPeople === 1) {
                    // إذا كان شخص واحد فقط، يكون افتراضياً تلقائياً
                    if (radio) {
                        radio.checked = true;
                        radio.disabled = true;
                        radioContainer.style.opacity = '0.6';
                    }
                    updateDefaultPersonValues();
                } else {
                    // إذا كان أكثر من شخص، تفعيل الاختيار
                    if (radio) {
                        radio.disabled = false;
                        radioContainer.style.opacity = '1';
                    }
                }
            });

            // التأكد من وجود شخص افتراضي واحد على الأقل
            const checkedRadio = list.querySelector('.default-person-radio:checked');
            if (totalPeople > 0 && !checkedRadio) {
                const firstRadio = list.querySelector('.default-person-radio');
                if (firstRadio) {
                    firstRadio.checked = true;
                    updateDefaultPersonValues();
                }
            }
        }

        function updateDefaultPersonValues() {
            // تحديث قيم is_default بناءً على الراديو المحدد
            const allRadios = list.querySelectorAll('.default-person-radio');
            allRadios.forEach(radio => {
                const item = radio.closest('.authorized-person-item');
                const hiddenInput = item.querySelector('.is-default-input');
                if (hiddenInput) {
                    hiddenInput.value = radio.checked ? '1' : '0';
                }
            });
        }

        function buildRow(idx) {
            const row = document.createElement('div');
            row.className = 'authorized-person-item mb-3 p-3 border rounded';
            row.innerHTML = `
                <div class="row g-4">
                    <div class="col-md-3">
                        <label class="form-label small">اسم المفوّض</label>
                        <input type="text" class="form-control" name="authorized_people[${idx}][name]" placeholder="ادخل اسم المفوّض" value="">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">رقم الهاتف</label>
                        <input type="text" class="form-control phone-input" name="authorized_people[${idx}][phone]" placeholder="ادخل رقم الهاتف" value="" maxlength="10">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">البريد الإلكتروني</label>
                        <input type="email" class="form-control email-input" name="authorized_people[${idx}][email]" placeholder="ادخل البريد الإلكتروني" value="">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">المنصب</label>
                        <input type="text" class="form-control" name="authorized_people[${idx}][position]" placeholder="ادخل المنصب" value="">
                    </div>
                    <div class="col-md-1 d-flex align-items-center justify-content-center">
                        <div class="form-check mt-4">
                            <input class="form-check-input default-person-radio" type="radio" name="default_person" value="${idx}">
                            <label class="form-check-label small text-muted">
                                افتراضي
                            </label>
                        </div>
                    </div>
                    <div class="col-md-1 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-sm btn-remove-person w-100">
                            حذف
                        </button>
                    </div>
                    <input type="hidden" name="authorized_people[${idx}][is_default]" value="0" class="is-default-input">
                </div>
            `;
            return row;
        }

        if (addBtn && list) {
            addBtn.addEventListener('click', function () {
                const idx = nextIndex();
                const newRow = buildRow(idx);
                list.appendChild(newRow);

                addPersonValidation(idx);
                updateDefaultPersonLogic();
            });

            list.addEventListener('click', function (e) {
                if (e.target.closest('.btn-remove-person')) {
                    const item = e.target.closest('.authorized-person-item');
                    if (!item) return;

                    const phoneInput = item.querySelector('input[name*="[phone]"]');
                    if (phoneInput) {
                        const match = phoneInput.name.match(/authorized_people\[(\d+)\]/);
                        if (match) {
                            const index = parseInt(match[1]);
                            removePersonValidation(index);
                        }
                    }

                    item.remove();
                    updateDefaultPersonLogic();
                }
            });

            // معالجة تغيير الراديو
            list.addEventListener('change', function (e) {
                if (e.target.classList.contains('default-person-radio')) {
                    updateDefaultPersonValues();
                }
            });
        }

        // Enhanced phone input validation
        document.addEventListener('input', function (e) {
            if (e.target && e.target.classList.contains('phone-input')) {
                e.target.value = e.target.value.replace(/[^0-9]/g, '').slice(0, 10);
            }
        });

        // Initialize validation for existing authorized people
        const initializeExisting = () => {
            const existingItems = list.querySelectorAll('.authorized-person-item');
            existingItems.forEach((item, index) => {
                addPersonValidation(index);
            });
            updateDefaultPersonLogic();
        };

        if (document.readyState === 'complete' || document.readyState === 'interactive') {
            initializeExisting();
        }
    })();
})();