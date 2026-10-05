/**
 * Collection Targets Edit Form - Complete Version
 * يدعم تحميل جميع القيم تلقائياً عند فتح صفحة التعديل
 */
'use strict';

let validation;

document.addEventListener('DOMContentLoaded', function () {
    const wizard = document.querySelector('#wizard-validation');
    if (!wizard) return;

    new Stepper(wizard, { linear: false });
    initValidation(wizard);

    wizard.querySelectorAll('.btn-next').forEach(btn => {
        btn.addEventListener('click', () => validation?.validate());
    });
});

function initValidation(wizard) {
    validation = FormValidation.formValidation(wizard, {
        fields: {
            'team_ids[]': { validators: { notEmpty: { message: 'يرجى اختيار مجموعة واحدة على الأقل' } } },
            user_id: { validators: { notEmpty: { message: 'يرجى اختيار المستخدم' } } },
            target_type: { validators: { notEmpty: { message: 'يرجى اختيار نوع الهدف' } } },
            target_amount: {
                validators: {
                    notEmpty: { message: 'يرجى إدخال مبلغ الهدف' }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: ''
            }),
            autoFocus: new FormValidation.plugins.AutoFocus(),
            submitButton: new FormValidation.plugins.SubmitButton()
        }
    }).on('core.form.valid', () => wizard.querySelector('#wizard-validation-form')?.submit());
}

function updateValidationRules(targetType) {
    if (!validation) return;

    ['target_month', 'target_year', 'custom_start_date', 'custom_end_date'].forEach(field => {
        try { validation.removeField(field); } catch (e) { }
    });

    const rules = {
        monthly: {
            target_month: { validators: { notEmpty: { message: 'يرجى اختيار الشهر' } } },
            target_year: { validators: { notEmpty: { message: 'يرجى اختيار السنة' } } }
        },
        yearly: {
            target_year: { validators: { notEmpty: { message: 'يرجى اختيار السنة' } } }
        },
        custom: {
            custom_start_date: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار تاريخ البداية' },
                    date: { format: 'YYYY-MM-DD', message: 'تاريخ غير صحيح' }
                }
            },
            custom_end_date: {
                validators: {
                    notEmpty: { message: 'يرجى اختيار تاريخ النهاية' },
                    date: { format: 'YYYY-MM-DD', message: 'تاريخ غير صحيح' }
                }
            }
        }
    };

    Object.entries(rules[targetType] || {}).forEach(([field, rule]) => {
        validation.addField(field, rule);
    });
}

$(document).ready(function () {
    console.log('Edit Mode:', window.editMode);
    console.log('Selected Data:', {
        teams: window.selectedTeams,
        user: window.selectedUser,
        targetType: window.selectedTargetType
    });

    // 1. تهيئة Select2 لجميع الحقول
    initializeSelect2();
    
    // 2. إعداد Event Handlers
    setupEventHandlers();
    
    // 3. تحميل البيانات في وضع التعديل
    if (window.editMode) {
        loadEditData();
    }
});

/*
|--------------------------------------------------------------------------
| تهيئة Select2
|--------------------------------------------------------------------------
*/
function initializeSelect2() {
    // Select2 العادية (الفرع، المستخدم، نوع الهدف)
    $('.select2:not(#team_ids)').select2({
        placeholder: function () { return $(this).data('placeholder'); },
        allowClear: true, 
        width: '100%', 
        language: 'ar', 
        dir: 'rtl'
    }).on('select2:select select2:clear', function () {
        $(this).removeClass('is-invalid').closest('.col-sm-6, .col-12, .col-md-6').find('.invalid-feedback').remove();
        validation?.revalidateField(this.name);
    });

    // Select2 للمجموعات (Multiple)
    initializeTeamsSelect2();
}

function initializeTeamsSelect2() {
    $('#team_ids').select2({
        placeholder: 'اختر المجموعات',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl',
        closeOnSelect: false,
        dropdownAutoWidth: false
    }).on('select2:unselecting', function () {
        setTimeout(() => {
            $('#team_ids').select2('open');
        }, 1);
    }).on('select2:select select2:clear', function () {
        $(this).removeClass('is-invalid');
        validation?.revalidateField('team_ids[]');
    });
}

/*
|--------------------------------------------------------------------------
| Event Handlers
|--------------------------------------------------------------------------
*/
function setupEventHandlers() {
    // تغيير المجموعات - فلترة المستخدمين
    $('#team_ids').on('change', function () {
        handleTeamsChange();
    });

    // تغيير نوع الهدف
    $('#target_type').on('change', function () {
        updatePeriodFields(this.value);
        updateValidationRules(this.value);
    });

    // تنسيق المبلغ
    $(document).on('input', '#target_amount', function () {
        this.value = this.value.replace(/[^0-9.]/g, '').replace(/(\..*)\./g, '$1');
        const parts = this.value.split('.');
        if (parts[1]?.length > 2) this.value = parts[0] + '.' + parts[1].substring(0, 2);
    }).on('blur', '#target_amount', function () {
        const val = parseFloat(this.value);
        if (!isNaN(val) && val > 0) this.value = val.toFixed(2);
    });

    // مفتاح Enter
    $(document).on('keypress', '#wizard-validation-form input, select', function (e) {
        if (e.which === 13) { e.preventDefault(); $('.btn-submit').click(); }
    });
}

/*
|--------------------------------------------------------------------------
| معالجة تغيير المجموعات - فلترة المستخدمين
|--------------------------------------------------------------------------
*/
function handleTeamsChange() {
    const teamIds = ($('#team_ids').val() || []).map(String);
    const userSelect = $('#user_id');
    const selectedUserId = userSelect.val();

    userSelect.find('option').each(function () {
        const teamId = $(this).data('team-id');
        if (!this.value) {
            $(this).show();
            return;
        }

        if (teamIds.length === 0 || (teamId && teamIds.includes(String(teamId)))) {
            $(this).show().prop('disabled', false);
        } else {
            $(this).hide().prop('disabled', true);
            if (this.value === selectedUserId) {
                userSelect.val('').trigger('change');
            }
        }
    });

    userSelect.select2({
        placeholder: userSelect.data('placeholder') || 'اختر المستخدم',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });
}

/*
|--------------------------------------------------------------------------
| تحديث حقول الفترة
|--------------------------------------------------------------------------
*/
function updatePeriodFields(type) {
    const container = $('#period-fields').empty();
    if (!type) return;

    const templates = {
        monthly: $('#monthly-fields-template').html(),
        yearly: $('#yearly-fields-template').html(),
        custom: $('#custom-fields-template').html()
    };

    if (templates[type]) {
        container.html(templates[type]);
        initNewFields(type);
    }
}

function initNewFields(type) {
    $('#period-fields .select2').select2({
        placeholder: function () { return $(this).data('placeholder'); },
        allowClear: true, width: '100%', language: 'ar', dir: 'rtl'
    }).on('select2:select select2:clear', function () {
        $(this).removeClass('is-invalid');
        validation?.revalidateField(this.name);
    });

    $('#custom_start_date, #custom_end_date').on('change blur', function () {
        if (validation) {
            validation.revalidateField(this.name);
            if (this.name === 'custom_start_date' && $('#custom_end_date').val()) {
                setTimeout(() => validation.revalidateField('custom_end_date'), 100);
            }
        }
    });

    if (type === 'monthly' || type === 'yearly') {
        populateOptions();
    }

    // تحميل القيم المحفوظة بعد إنشاء الحقول
    if (window.editMode) {
        loadPeriodValues(type);
    }
}

function populateOptions() {
    if (window.months) {
        const monthSelect = $('#target_month');
        window.months.forEach(m => monthSelect.append(new Option(m.label, m.value)));
    }
    if (window.years) {
        const yearSelects = $('#target_year, #target_year_only');
        window.years.forEach(y => yearSelects.append(new Option(y.label, y.value)));
    }
}

/*
|--------------------------------------------------------------------------
| تحميل بيانات التعديل - الدالة الرئيسية
|--------------------------------------------------------------------------
*/
function loadEditData() {
    console.log('Loading Edit Data...');
    
    // 1. تحميل نوع الهدف وحقول الفترة أولاً
    if (window.selectedTargetType) {
        $('#target_type').val(window.selectedTargetType).trigger('change');
        
        // انتظر حتى يتم إنشاء حقول الفترة
        setTimeout(() => {
            loadPeriodValues(window.selectedTargetType);
        }, 500);
    }
    
    if (window.selectedTeams && window.selectedTeams.length > 0) {
        $('#team_ids').val(window.selectedTeams).trigger('change');
    }

    if (window.selectedUser) {
        $('#user_id').val(window.selectedUser).trigger('change');
    }
}

/*
|--------------------------------------------------------------------------
| تحميل قيم الفترة الزمنية
|--------------------------------------------------------------------------
*/
function loadPeriodValues(type) {
    console.log('Loading period values for type:', type);
    
    if (type === 'monthly') {
        if (window.selectedTargetMonth) {
            $('#target_month').val(window.selectedTargetMonth).trigger('change');
            console.log('Month set:', window.selectedTargetMonth);
        }
        if (window.selectedTargetYear) {
            $('#target_year').val(window.selectedTargetYear).trigger('change');
            console.log('Year set:', window.selectedTargetYear);
        }
    } else if (type === 'yearly') {
        if (window.selectedTargetYear) {
            $('#target_year_only').val(window.selectedTargetYear).trigger('change');
            console.log('Year set:', window.selectedTargetYear);
        }
    } else if (type === 'custom') {
        if (window.selectedCustomStartDate) {
            $('#custom_start_date').val(window.selectedCustomStartDate);
            console.log('Start date set:', window.selectedCustomStartDate);
        }
        if (window.selectedCustomEndDate) {
            $('#custom_end_date').val(window.selectedCustomEndDate);
            console.log('End date set:', window.selectedCustomEndDate);
        }
    }
}

/*
|--------------------------------------------------------------------------
| معالجة تغيير تاريخ البداية لإعادة التحقق من تاريخ النهاية
|--------------------------------------------------------------------------
*/
$(document).on('change', '#custom_start_date', function () {
    const endDateField = $('#custom_end_date');
    if (endDateField.length && endDateField.val() && validation) {
        setTimeout(() => {
            validation.revalidateField('custom_end_date');
        }, 200);
    }
});