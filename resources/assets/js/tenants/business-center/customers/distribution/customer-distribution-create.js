/**
 * Customer Distribution Create - JavaScript
 */

'use strict';

let distributionStepper;
let fv;
let previewData = null;
let totalPortfolioAmount = 0;

document.addEventListener('DOMContentLoaded', function () {
    initializeComponents();
    initializeStepper();
    initializeValidation();
    bindEvents();
    initializeCalculator();
});

function initializeComponents() {
    // تفعيل Select2 للحقول العامة
    $('.select2:not(#collector_ids):not(#portfolio_id):not(#team_ids)').each(function () {
        $(this).select2({
            placeholder: $(this).data('placeholder') || 'اختر',
            allowClear: true,
            width: '100%',
            language: 'ar',
            dir: 'rtl'
        });
    });

    // تفعيل Select2 للمحفظة
    $('#portfolio_id').select2({
        placeholder: 'اختر المحفظة',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });

    // تفعيل Select2 للمجموعات (Multiple)
    initializeTeamsSelect2();

    // تفعيل Select2 للمحصلين
    initializeCollectorsSelect2();

    updateDistributionMethodStyles();

    // تعطيل الحقول المعتمدة
    $('#portfolio_id').prop('disabled', true);
    $('#team_ids').prop('disabled', true);
    $('#collector_ids').prop('disabled', true);
}

/*
|--------------------------------------------------------------------------
| Select2 للمجموعات (Multiple)
|--------------------------------------------------------------------------
*/
function initializeTeamsSelect2() {
    $('#team_ids').select2({
        placeholder: 'اختر المجموعات',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl',
        closeOnSelect: false,
        dropdownAutoWidth: false
    });

    // إبقاء القائمة مفتوحة عند إلغاء الاختيار
    $('#team_ids').on('select2:unselecting', function (e) {
        setTimeout(() => {
            $('#team_ids').select2('open');
        }, 1);
    });
}

/*
|--------------------------------------------------------------------------
| حاسبة الأهداف المستقلة
|--------------------------------------------------------------------------
*/
function initializeCalculator() {
    // ✅ تحديد آخر حقل تم تعديله
    $('#monthly-target').on('input', function () {
        lastChangedField = 'monthly-target'; // ✅ تحديث المتغير
        calculateMissingValue();
    });

    $('#employees-count').on('input', function () {
        lastChangedField = 'employees-count'; // ✅ تحديث المتغير
        calculateMissingValue();
    });

    $('#duration-months').on('input', function () {
        lastChangedField = 'duration-months'; // ✅ تحديث المتغير
        calculateMissingValue();
    });

    $('#apply-targets').on('change', function () {
        const isChecked = $(this).is(':checked');
        $('#calculator-enabled-hidden').val(isChecked ? '1' : '0');

        if (isChecked) {
            $('#apply-targets-warning').show();
            updateCollectorCountValidation();
            updateHiddenCalculatorFields();
        } else {
            $('#apply-targets-warning').hide();
            $('#collector-count-info').hide();
        }
    });

    // ✅ معالج زر المسح
    $('#reset-calculator-btn').on('click', resetCalculator);
}
let lastChangedField = null; // ✅ تعريف المتغير في الأعلى

function calculateMissingValue() {
    const monthlyTarget = parseFloat($('#monthly-target').val()) || 0;
    const employeesCount = parseInt($('#employees-count').val()) || 0;
    const duration = parseInt($('#duration-months').val()) || 0;

    if (totalPortfolioAmount <= 0) {
        $('#calculator-validation').html(`
            <div class="alert alert-warning">
                <i class="ti ti-alert-triangle me-2"></i>
                يرجى اختيار محفظة أولاً
            </div>
        `).show();
        return;
    }

    // عد الحقول الممتلئة
    const filledCount = [monthlyTarget > 0, employeesCount > 0, duration > 0].filter(Boolean).length;

    // إذا أقل من 2 حقول، لا تحسب
    if (filledCount < 2) {
        $('#calculator-validation').hide();
        return;
    }

    let newMonthly = monthlyTarget;
    let newEmployees = employeesCount;
    let newDuration = duration;

    // ✅ إذا كانت حقلين فقط → احسب الثالث (الفارغ)
    if (filledCount === 2) {
        // الهدف فارغ → احسبه
        if (monthlyTarget === 0 && employeesCount > 0 && duration > 0) {
            newMonthly = totalPortfolioAmount / (employeesCount * duration);
            $('#monthly-target').val(newMonthly.toFixed(2));
        }
        // الموظفين فارغ → احسبهم
        else if (employeesCount === 0 && monthlyTarget > 0 && duration > 0) {
            newEmployees = Math.ceil(totalPortfolioAmount / (monthlyTarget * duration));
            $('#employees-count').val(newEmployees);
        }
        // المدة فارغة → احسبها
        else if (duration === 0 && monthlyTarget > 0 && employeesCount > 0) {
            newDuration = Math.ceil(totalPortfolioAmount / (monthlyTarget * employeesCount));
            $('#duration-months').val(newDuration);
        }
    }
    // ✅ إذا كانت الثلاثة ممتلئة → احسب بناءً على آخر تعديل
    else if (filledCount === 3) {
        if (lastChangedField === 'monthly-target') {
            // عدّل الهدف → احسب المدة
            newDuration = Math.ceil(totalPortfolioAmount / (monthlyTarget * employeesCount));
            $('#duration-months').val(newDuration);
        } else if (lastChangedField === 'employees-count') {
            // عدّل الموظفين → احسب المدة
            newDuration = Math.ceil(totalPortfolioAmount / (monthlyTarget * employeesCount));
            $('#duration-months').val(newDuration);
        } else if (lastChangedField === 'duration-months') {
            // عدّل المدة → احسب الموظفين
            newEmployees = Math.ceil(totalPortfolioAmount / (monthlyTarget * duration));
            $('#employees-count').val(newEmployees);
        }
    }

    // احسب القيم المحدثة من الحقول
    newMonthly = parseFloat($('#monthly-target').val()) || 0;
    newEmployees = parseInt($('#employees-count').val()) || 0;
    newDuration = parseInt($('#duration-months').val()) || 0;

    // اعرض النتائج
    showCalculatorValidation(newMonthly, newEmployees, newDuration);
    updateHiddenCalculatorFields();
    updateCollectorCountValidation();
}
function showCalculatorValidation(monthlyTarget, employeesCount, duration) {
    const calculatedTotal = monthlyTarget * employeesCount * duration;
    const difference = Math.abs(calculatedTotal - totalPortfolioAmount);
    const percentageDiff = (difference / totalPortfolioAmount * 100).toFixed(2);

    let validationHtml = '';

    if (difference < 1) {
        // ✅ نجاح تام
        validationHtml = `
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start">
                    <i class="ti ti-check-circle me-2 mt-1" style="font-size: 1.3rem; color: #28a745;"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-2">✅ رائع! الحساب صحيح تماماً</h6>
                        <p class="mb-2">
                            <strong>${number_format(monthlyTarget, 2)}</strong> ريال/شهر ×
                            <strong>${employeesCount}</strong> موظف ×
                            <strong>${duration}</strong> شهر =
                            <strong>${number_format(calculatedTotal, 2)}</strong> ريال
                        </p>
                        <small class="text-muted">المبلغ المحسوب يطابق تماماً إجمالي المحفظة</small>
                    </div>
                </div>
            </div>
        `;
    } else if (percentageDiff <= 5) {
        // ⚠️ تحذير بسيط
        validationHtml = `
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start">
                    <i class="ti ti-alert-circle me-2 mt-1" style="font-size: 1.3rem; color: #ff9800;"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-2">⚠️ فرق بسيط - النتيجة مقبولة جداً</h6>
                        <p class="mb-2">
                            <strong>المبلغ المحسوب:</strong> ${number_format(calculatedTotal, 2)} ريال<br>
                            <strong>إجمالي المحفظة:</strong> ${number_format(totalPortfolioAmount, 2)} ريال<br>
                            <strong>الفرق:</strong> ${number_format(difference, 2)} ريال (${percentageDiff}%)
                        </p>
                        <small class="text-muted">هذا الفرق طبيعي تماماً ويعود لتقريب الأرقام. يمكنك المتابعة بثقة.</small>
                    </div>
                </div>
            </div>
        `;
    } else {
        // ❌ خطأ كبير
        validationHtml = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <div class="d-flex align-items-start">
                    <i class="ti ti-alert-triangle me-2 mt-1" style="font-size: 1.3rem; color: #dc3545;"></i>
                    <div class="flex-grow-1">
                        <h6 class="alert-heading mb-2">❌ فرق كبير - جرّب قيماً أخرى</h6>
                        <p class="mb-2">
                            <strong>المبلغ المحسوب:</strong> ${number_format(calculatedTotal, 2)} ريال<br>
                            <strong>إجمالي المحفظة:</strong> ${number_format(totalPortfolioAmount, 2)} ريال<br>
                            <strong>الفرق:</strong> ${number_format(difference, 2)} ريال (${percentageDiff}%)
                        </p>
                        <small class="text-muted">
                            🔧 نصيحة: جرّب تغيير أحد الحقول:
                            <ul class="mt-1 mb-0">
                                <li>أضف أو اطرح موظف واحد</li>
                                <li>غيّر المدة الزمنية بـ شهر واحد</li>
                                <li>اختر محفظة مختلفة</li>
                            </ul>
                        </small>
                    </div>
                </div>
            </div>
        `;
    }

    $('#calculator-validation').html(validationHtml).show();
}

function updateCollectorCountValidation() {
    if (!$('#apply-targets').is(':checked')) {
        $('#collector-count-info').hide();
        return;
    }

    const employeesCount = parseInt($('#employees-count').val()) || 0;
    const selectedCount = $('#collector_ids').val() ? $('#collector_ids').val().length : 0;

    if (employeesCount > 0) {
        let message = '';
        let className = '';

        if (selectedCount === 0) {
            message = `يجب اختيار <strong>${employeesCount}</strong> محصلين بالضبط حسب الحاسبة`;
            className = 'text-muted';
        } else if (selectedCount === employeesCount) {
            message = `✅ تم اختيار العدد الصحيح: <strong>${selectedCount}</strong> محصلين`;
            className = 'text-success';
        } else if (selectedCount < employeesCount) {
            message = `⚠️ اخترت <strong>${selectedCount}</strong> محصلين فقط. يجب اختيار <strong>${employeesCount}</strong> محصلين`;
            className = 'text-danger';
        } else {
            message = `⚠️ اخترت <strong>${selectedCount}</strong> محصلين. يجب اختيار <strong>${employeesCount}</strong> محصلين فقط`;
            className = 'text-danger';
        }

        $('#collector-count-text').html(message).removeClass('text-muted text-success text-danger').addClass(className);
        $('#collector-count-info').show();
    } else {
        $('#collector-count-info').hide();
    }
}

function updateHiddenCalculatorFields() {
    $('#calculator-monthly-target-hidden').val($('#monthly-target').val());
    $('#calculator-employees-count-hidden').val($('#employees-count').val());
    $('#calculator-duration-hidden').val($('#duration-months').val());
}

function resetCalculator() {
    // 1. تنظيف جميع الحقول
    $('#monthly-target, #employees-count, #duration-months').val('');

    // 2. إخفاء الرسائل
    $('#calculator-validation').fadeOut();
    $('#apply-targets-warning').hide();
    $('#collector-count-info').hide();

    // 3. تنظيف الحقول المخفية
    $('#calculator-monthly-target-hidden, #calculator-employees-count-hidden, #calculator-duration-hidden').val('');
    $('#calculator-enabled-hidden').val('0');

    // 4. إعادة تعيين checkbox
    $('#apply-targets').prop('checked', false);

    // 5. تركيز على أول حقل
    $('#monthly-target').focus();

    // 6. عرض رسالة تأكيد
    toastr.info('تم مسح جميع القيم - ابدأ من جديد', 'تم التنظيف', {
        positionClass: 'toast-top-end',
        timeOut: 2000
    });
}
/*
|--------------------------------------------------------------------------
| Select2 للمحصلين
|--------------------------------------------------------------------------
*/
function initializeCollectorsSelect2() {
    $('#collector_ids').select2({
        placeholder: 'اختر المحصلين',
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl',
        closeOnSelect: false,
        dropdownAutoWidth: false
    });

    $('#collector_ids').on('select2:unselecting', function (e) {
        setTimeout(() => {
            $('#collector_ids').select2('open');
        }, 1);
    });

    $('#collector_ids').on('change', function () {
        updateCollectorCountValidation();
    });

    $(document).on('click', function (e) {
        if (!$(e.target).closest('.select2-container, .select2-dropdown').length) {
            if ($('#collector_ids').hasClass('select2-hidden-accessible')) {
                $('#collector_ids').select2('close');
            }
        }
    });

    $(document).on('click', '.select2-dropdown', function (e) {
        e.stopPropagation();
    });
}

function initializeStepper() {
    const wizardElement = document.querySelector('#distribution-wizard');
    if (wizardElement) {
        distributionStepper = new Stepper(wizardElement, {
            linear: true
        });
        wizardElement.addEventListener('show.bs-stepper', function (event) {
            if (event.detail.from === 2) {
                cancelPreview();
            }
        });
    }
}

function initializeValidation() {
    const stepCriteria = document.querySelector('#step-criteria');
    fv = FormValidation.formValidation(stepCriteria, {
        fields: {
            'collector_ids[]': {
                validators: {
                    notEmpty: {
                        message: 'يرجى اختيار محصل واحد على الأقل'
                    }
                }
            },
            company_id: {
                validators: {
                    notEmpty: {
                        message: 'يرجى اختيار الشركة'
                    }
                }
            },
            portfolio_id: {
                validators: {
                    notEmpty: {
                        message: 'يرجى اختيار المحفظة'
                    }
                }
            },
            'team_ids[]': {
                validators: {
                    notEmpty: {
                        message: 'يرجى اختيار مجموعة واحدة على الأقل'
                    }
                }
            }
        },
        plugins: {
            trigger: new FormValidation.plugins.Trigger(),
            bootstrap5: new FormValidation.plugins.Bootstrap5({
                eleValidClass: ''
            }),
            autoFocus: new FormValidation.plugins.AutoFocus()
        }
    });

    // Revalidation Events
    $('#collector_ids').on('change', () => fv.revalidateField('collector_ids[]'));
    $('#company_id').on('change', () => fv.revalidateField('company_id'));
    $('#portfolio_id').on('change', () => {
        fv.revalidateField('portfolio_id');
        loadPortfolioTotal();
    });
    $('#team_ids').on('change', () => fv.revalidateField('team_ids[]'));
}

function bindEvents() {
    $('input[name="distribution_method"]').on('change', updateDistributionMethodStyles);
    $('#company_id').on('change', filterPortfoliosByCompany);
    $('#team_ids').on('change', filterCollectorsByTeams);

    $('#step-method .btn-next').on('click', () => distributionStepper.next());
    $('#step-criteria .btn-preview').on('click', handlePreview);
    $('.btn-prev').on('click', () => distributionStepper.previous());
    $('#step-preview .btn-execute').on('click', handleExecute);
}

/*
|--------------------------------------------------------------------------
| فلترة المحصلين بناءً على المجموعات المختارة - AJAX
|--------------------------------------------------------------------------
*/
function filterCollectorsByTeams() {
    const teamIds = $('#team_ids').val(); // array

    // إعادة تعيين المحصلين
    $('#collector_ids').val(null).trigger('change');

    if (!teamIds || teamIds.length === 0) {
        $('#collector_ids').prop('disabled', true).empty();
        return;
    }

    // جلب المحصلين بناءً على المجموعات
    $.ajax({
        url: window.routes.collectorsByTeam,
        type: 'GET',
        data: {
            team_ids: teamIds
        },
        beforeSend: function () {
            $('#collector_ids').prop('disabled', true);
            $('#collector_ids').empty().append(new Option('جاري التحميل...', '', false, false));
        },
        success: function (response) {
            console.log('Collectors Response:', response); // للتأكد من البيانات

            if (response.success && response.collectors && response.collectors.length > 0) {
                $('#collector_ids').empty();

                // إضافة المحصلين
                response.collectors.forEach(function (collector) {
                    $('#collector_ids').append(new Option(collector.name, collector.id, false, false));
                });

                // إعادة تهيئة Select2
                $('#collector_ids').select2('destroy');
                initializeCollectorsSelect2();

                // تفعيل حقل المحصلين
                $('#collector_ids').prop('disabled', false);

                // فتح القائمة تلقائياً
                setTimeout(() => {
                    $('#collector_ids').select2('open');
                }, 150);

                // toastr.success(`تم تحميل ${response.collectors.length} محصل`);
            } else {
                // toastr.warning('لا يوجد محصلين لهذه المجموعات');
                $('#collector_ids').prop('disabled', true).empty();
            }
        },
        error: function (xhr) {
            console.error('Collectors Error:', xhr);
            toastr.error('فشل في تحميل المحصلين');
            $('#collector_ids').prop('disabled', true).empty();
        }
    });
}

function loadPortfolioTotal() {
    const portfolioId = $('#portfolio_id').val();
    const companyId = $('#company_id').val();

    if (!portfolioId || !companyId) {
        totalPortfolioAmount = 0;
        $('#portfolio-total-display').text('0.00 ريال');
        $('#portfolio-total-alert').hide();
        resetCalculator();
        return;
    }

    $.ajax({
        url: `${window.routes.portfoliosWithUnassignedCustomers}`,
        type: 'GET',
        data: {
            company_id: companyId,
            portfolio_id: portfolioId,
            get_total: true
        },
        success: function (response) {
            if (response.success) {
                totalPortfolioAmount = parseFloat(response.total_debt) || 0;
                $('#portfolio-total-display').text(number_format(totalPortfolioAmount, 2) + ' ريال');
                $('#portfolio-total-alert').show();
                calculateMissingValue();
            }
        },
        error: function (xhr) {
            console.error('Error loading portfolio total:', xhr);
            totalPortfolioAmount = 0;
            $('#portfolio-total-display').text('0.00 ريال');
            $('#portfolio-total-alert').hide();
            resetCalculator();
        }
    });
}

function updateDistributionMethodStyles() {
    $('.distribution-method-card .form-check-label').removeClass('border-primary');
    $('input[name="distribution_method"]:checked').siblings('.form-check-label').addClass('border-primary');
}

function filterPortfoliosByCompany() {
    const selectedCompany = $('#company_id').val();

    if (!selectedCompany) {
        $('#portfolio_id').val(null).trigger('change');
        $('#portfolio_id').prop('disabled', true);
        $('#portfolio-total-alert').hide();
        return;
    }

    $('#portfolio_id').prop('disabled', false);

    $.ajax({
        url: `${window.routes.portfoliosWithUnassignedCustomers}?company_id=${selectedCompany}`,
        type: 'GET',
        success: function (response) {
            if (response.success && response.portfolios) {
                $('#portfolio_id').empty();
                $('#portfolio_id').append(new Option('اختر المحفظة', '', false, false));

                response.portfolios.forEach(function (portfolio) {
                    $('#portfolio_id').append(new Option(portfolio.name, portfolio.id, false, false));
                });

                $('#portfolio_id').select2({
                    placeholder: 'اختر المحفظة',
                    allowClear: true,
                    width: '100%',
                    language: 'ar',
                    dir: 'rtl'
                });

                setTimeout(() => {
                    $('#portfolio_id').select2('open');
                }, 100);
            }
        },
        error: function () {
            toastr.error('فشل في تحميل المحافظ');
            $('#portfolio_id').prop('disabled', true);
        }
    });
}

function handlePreview() {
    if ($('#apply-targets').is(':checked')) {
        const employeesCount = parseInt($('#employees-count').val()) || 0;
        const selectedCount = $('#collector_ids').val() ? $('#collector_ids').val().length : 0;

        if (employeesCount === 0) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ في الحاسبة',
                text: 'يرجى إكمال بيانات الحاسبة (عدد الموظفين مطلوب)',
                confirmButtonText: 'حسناً',
                customClass: { confirmButton: 'btn btn-danger' }
            });
            return;
        }

        if (selectedCount !== employeesCount) {
            Swal.fire({
                icon: 'error',
                title: 'خطأ في عدد المحصلين',
                html: `
                    <p>الحاسبة تتطلب <strong>${employeesCount}</strong> موظفين</p>
                    <p>لكنك اخترت <strong>${selectedCount}</strong> محصلين فقط</p>
                    <hr>
                    <p class="text-muted">يرجى اختيار <strong>${employeesCount}</strong> محصلين بالضبط أو إلغاء تفعيل "تطبيق الأهداف"</p>
                `,
                confirmButtonText: 'حسناً',
                customClass: { confirmButton: 'btn btn-danger' }
            });
            return;
        }
    }

    fv.validate().then(status => {
        if (status === 'Valid') {
            showPreview();
        }
    });
}

function showPreview() {
    $('#preview-container').hide();
    $('#preview-loading').show();
    distributionStepper.next();

    $.ajax({
        url: window.routes.distributionPreview,
        type: 'POST',
        data: new FormData(document.getElementById('distribution-form')),
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.success && response.data) {
                previewData = response.data;
                displayPreview(response.data);
            } else {
                toastr.error(response.message || 'فشل في جلب بيانات المعاينة.');
                distributionStepper.previous();
            }
        },
        error: function (xhr) {
            const message = xhr.responseJSON?.message || 'حدث خطأ في الاتصال بالخادم.';
            toastr.error(message);
            distributionStepper.previous();
        }
    });
}

function displayPreview(data) {
    $('#preview-loading').hide();
    $('#preview-container').show();

    $('#summary-method').text(data.method_label || '-');
    $('#summary-collectors').text(number_format(data.collectors_count));
    $('#summary-customers').text(number_format(data.total_customers));
    $('#summary-debt').text(number_format(data.total_debt, 2) + ' ريال');

    const tableBody = $('#distribution-table tbody');
    tableBody.empty();

    if (Object.keys(data.distribution).length > 0) {
        Object.values(data.distribution).forEach(item => {
            const percentage = data.total_debt > 0 ? (item.total_debt / data.total_debt * 100) : 0;
            const formattedPercentage = percentage.toFixed(1);

            tableBody.append(`
                <tr>
                    <td>
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-sm me-3">
                               <span class="avatar-initial rounded-circle bg-label-primary">${item.collector.name.charAt(0)}</span>
                            </div>
                            <span>${item.collector.name}</span>
                        </div>
                    </td>
                    <td class="text-center">${number_format(item.customers_count)}</td>
                    <td class="text-end">${number_format(item.total_debt, 2)} ريال</td>
                    <td class="text-center">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="progress me-2" style="height: 0.5rem; width: 60px;">
                                <div class="progress-bar" style="width: ${percentage}%;" role="progressbar"></div>
                            </div>
                            <span class="small">${formattedPercentage}%</span>
                        </div>
                    </td>
                </tr>
            `);
        });
    } else {
        tableBody.append('<tr><td colspan="4" class="text-center p-4">لا يوجد عملاء مطابقين للمعايير المحددة.</td></tr>');
    }

    $('.btn-execute').prop('disabled', data.total_customers === 0);
}

function cancelPreview() {
    $('#preview-container').hide();
    previewData = null;
}

function handleExecute() {
    Swal.fire({
        title: 'تأكيد التنفيذ',
        text: `سيتم توزيع ${previewData.total_customers} عميل. هل أنت متأكد؟`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، نفذ الآن!',
        cancelButtonText: 'إلغاء',
        customClass: { confirmButton: 'btn btn-primary me-2', cancelButton: 'btn btn-secondary' },
        buttonsStyling: false
    }).then(result => {
        if (result.isConfirmed) {
            performDistribution();
        }
    });
}

function performDistribution() {
    const btn = $('#step-preview .btn-execute');
    btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>جاري التنفيذ...');

    $.ajax({
        url: window.routes.distributionExecute,
        type: 'POST',
        data: new FormData(document.getElementById('distribution-form')),
        processData: false,
        contentType: false,
        success: function () {
            Swal.fire({
                icon: 'success',
                title: 'تم التوزيع بنجاح',
                text: 'تم إسناد العملاء للمحصلين المختارين.',
                customClass: { confirmButton: 'btn btn-primary' }
            }).then(() => {
                window.location.href = window.routes.distributionIndex;
            });
        },
        error: function (xhr) {
            con
            const message = xhr.responseJSON?.message || 'حدث خطأ غير متوقع.';
            Swal.fire({
                icon: 'error',
                title: 'فشل التنفيذ',
                text: message,
                customClass: { confirmButton: 'btn btn-danger' }
            });
            btn.prop('disabled', false).html('تنفيذ التوزيع');
        }
    });
}

function number_format(number, decimals = 0) {
    return new Intl.NumberFormat('ar-SA-u-nu-latn', {
        minimumFractionDigits: decimals,
        maximumFractionDigits: decimals
    }).format(parseFloat(number) || 0);
}
