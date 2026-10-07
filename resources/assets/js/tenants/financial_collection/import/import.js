'use strict';
let importDropzone = null;
let fileAnalyzed = false;
let uploadedFileData = null;

$(document).ready(function () {
    initializeComponents();
    bindEvents();
    setupSystemOptions();
    initializeSelect2();
});

/*
|--------------------------------------------------------------------------
| Component Initialization
|--------------------------------------------------------------------------
*/
function initializeComponents() {
    Dropzone.autoDiscover = false;
    importDropzone = new Dropzone("#import-dropzone", {
        url: "#",
        maxFiles: 1,
        maxFilesize: 10,
        acceptedFiles: '.xlsx,.xls',
        addRemoveLinks: false,
        autoProcessQueue: false,
        clickable: '#file-upload-area',
        previewTemplate: "<div></div>",
        init: function () {
            this.on("addedfile", (file) => {
                updateDropzoneUIAfterUpload(file);
                processUploadedFile(file);
            });
            this.on("removedfile", () => {
                resetDropzoneUIToInitial();
                resetFileState();
            });
            this.on("error", (file, msg) => {
                toastr.error(msg.message || msg);
                this.removeFile(file);
                resetFileState();
            });
            this.on("dragenter", () => $('#file-upload-area').addClass('dragover'));
            this.on("dragleave", () => $('#file-upload-area').removeClass('dragover'));
            this.on("drop", () => $('#file-upload-area').removeClass('dragover'));
        }
    });
}

function initializeSelect2() {
    if (typeof $ !== 'undefined' && $.fn.select2) {
        $('.select2').each(function () {
            $(this).select2({
                placeholder: function () { return $(this).data('placeholder'); },
                width: '100%',
                language: 'ar',
                dir: 'rtl'
            }).on('select2:select select2:clear', function () {
                $(this).removeClass('is-invalid');
                $(this).closest('.col-sm-6, .col-sm-12, .col-12').find('.invalid-feedback').remove();
                updateImportSubmitState();
            });
        });
    }
}
/*
|--------------------------------------------------------------------------
| Event Binding
|--------------------------------------------------------------------------
*/
function bindEvents() {
    $('#import-form').on('submit', handleImportSubmit);
    window.resetImport = resetImport;
    window.removeSelectedFile = removeSelectedFile;
    window.downloadErrorReport = downloadErrorReport;

    $('#import-form').on('change input', 'select', updateImportSubmitState);
}

/*
|--------------------------------------------------------------------------
| File Processing Logic
|--------------------------------------------------------------------------
*/
function processUploadedFile(file) {
    showAnalyzingState();
    const formData = new FormData();
    formData.append('import_file', file);
    formData.append('_token', window.csrfToken);
    $.ajax({
        url: window.routes.uploadAndAnalyze,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {
            if (response.success && response.data) {
                uploadedFileData = response.data;
                fileAnalyzed = true;
                $('#temp_path').val(uploadedFileData.temp_path);
                showConfigurationForm(uploadedFileData.columns);
                showFileAnalyzedSuccess(uploadedFileData.total_rows);
            } else {
                handleAnalysisError(response.message || 'فشل في تحليل الملف');
            }
        },
        error: function (xhr) {
            const errorMsg = xhr.responseJSON?.message || 'حدث خطأ أثناء تحليل الملف';
            handleAnalysisError(errorMsg);
        }
    });
}

function handleAnalysisError(errorMessage) {
    toastr.error(errorMessage);
    if (importDropzone && importDropzone.files.length > 0) {
        importDropzone.removeAllFiles();
    }
    resetFileState();
}

function resetFileState() {
    fileAnalyzed = false;
    uploadedFileData = null;
    $('#temp_path').val('');
    hideConfigurationForm();
    updateImportSubmitState();
}

/*
|--------------------------------------------------------------------------
| UI Update Functions
|--------------------------------------------------------------------------
*/
function updateDropzoneUIAfterUpload(file) {
    const fileSize = (file.size / 1024 / 1024).toFixed(2);
    const dropzoneElement = $('#import-dropzone');
    dropzoneElement.html(`
        <div class="file-selected-state" id="file-display">
            <div class="d-flex justify-content-between align-items-center">
                <div class="file-info">
                    <i class="ti ti-file-check file-icon text-success"></i>
                    <div class="file-details">
                        <div class="file-name">${file.name}</div>
                        <div class="file-size">الحجم: ${fileSize} MB</div>
                        <div class="file-status text-muted">جاري التحليل...</div>
                    </div>
                </div>
                <button type="button" class="remove-file-btn" title="إزالة الملف" onclick="removeSelectedFile()">
                    <i class="ti ti-x"></i>
                </button>
            </div>
        </div>
    `);
}

function showAnalyzingState() {
    $('#file-display .file-status').html(`
        <span class="text-primary">
            <span class="spinner-border spinner-border-sm me-1" role="status"></span>
            جاري تحليل الملف...
        </span>
    `);
}

function showFileAnalyzedSuccess(totalRows) {
    $('#file-display .file-status').html(`
        <span class="text-success">
            <i class="ti ti-check me-1"></i>
            تم التحليل بنجاح - ${totalRows.toLocaleString()} صف
        </span>
    `);
}

function resetDropzoneUIToInitial() {
    $('#import-dropzone').html(`
        <div class="dz-message">
            <i class="ti ti-cloud-upload" style="font-size: 3rem;"></i>
            <h5 class="mb-2">اسحب الملف هنا أو انقر للاختيار</h5>
            <p class="text-muted mb-0">.xlsx, .xls فقط - الحد الأقصى 10MB</p>
        </div>
    `);
}

function showConfigurationForm(columns) {
    populateColumnSelects(columns);
    $('#configuration-form').slideDown(300);
    updateImportSubmitState();
}

function hideConfigurationForm() {
    $('#configuration-form').slideUp(300);
}

function removeSelectedFile() {
    if (importDropzone && importDropzone.files.length > 0) {
        importDropzone.removeAllFiles();
    }
    resetFileState();
}

/*
|--------------------------------------------------------------------------
| Form Population Functions
|--------------------------------------------------------------------------
*/
function populateColumnSelects(columns) {
    const selects = ['account_column', 'amount_column', 'date_column'];
    selects.forEach(selectName => {
        const select = $(`select[name="${selectName}"]`);
        select.empty().append('<option value="">اختر الحقل</option>');
        columns.forEach(column => {
            select.append(`<option value="${column.index}">${column.name}</option>`);
        });
        // إعادة تهيئة Select2 بعد تحديث الخيارات
        select.trigger('change');
    });
}

function setupSystemOptions() {
    const options = window.systemOptions;
    populateSelect('payment_method_id', options.payment_methods);
    populateSelect('collection_type', options.collection_types);
    populateSelect('payment_type', options.payment_types);
    populateSelect('date_format', options.date_formats);
    populateSelect('assigned_user_id', options.users, true);
}

function populateSelect(selectName, options, allowEmpty = false) {
    const select = $(`select[name="${selectName}"]`);
    select.empty();

    if (allowEmpty) {
        select.append('<option value="">المستخدم الحالي</option>');
    } else {
        select.append('<option value="">اختر</option>');
    }

    options.forEach(option => {
        const value = option.value || option.id;
        const label = option.label || option.name;
        select.append(`<option value="${value}">${label}</option>`);
    });

    // إعادة تهيئة Select2 بعد تحديث الخيارات
    select.trigger('change');
}

function updateImportSubmitState() {
    const required = ['account_column', 'amount_column', 'date_column', 'date_format', 'payment_method_id', 'collection_type', 'payment_type'];
    const ready = fileAnalyzed && required.every((name) => String($(`[name="${name}"]`).val() || '') !== '');
    $('#import-form button[type="submit"]').prop('disabled', !ready);
}

/*
|--------------------------------------------------------------------------
| Form Validation and Submission
|--------------------------------------------------------------------------
*/
function handleImportSubmit(e) {
    e.preventDefault();
    if (!validateFormData()) {
        return false;
    }
    const formData = new FormData(e.target);
    const importData = Object.fromEntries(formData);
    showImportConfirmation(importData);
}

function validateFormData() {
    if (!fileAnalyzed || !uploadedFileData) {
        toastr.error('يجب رفع وتحليل الملف أولاً');
        return false;
    }
    if (!uploadedFileData.temp_path) {
        toastr.error('مسار الملف المؤقت غير صحيح');
        return false;
    }
    const requiredFields = [
        { name: 'account_column', label: 'حقل رقم حساب العميل' },
        { name: 'amount_column', label: 'حقل مبلغ السداد' },
        { name: 'date_column', label: 'حقل تاريخ السداد' },
        { name: 'date_format', label: 'تنسيق التاريخ' },
        { name: 'payment_method_id', label: 'طريقة التحصيل' },
        { name: 'collection_type', label: 'نوع التحصيل' },
        { name: 'payment_type', label: 'نوع السداد' }
    ];
    for (const field of requiredFields) {
        const value = $(`[name="${field.name}"]`).val();
        if (!value || value === '') {
            toastr.error(`يرجى تحديد ${field.label}`);
            $(`[name="${field.name}"]`).focus();
            return false;
        }
    }
    const accountColumn = parseInt($(`[name="account_column"]`).val());
    const amountColumn = parseInt($(`[name="amount_column"]`).val());
    const dateColumn = parseInt($(`[name="date_column"]`).val());
    if (accountColumn === amountColumn || accountColumn === dateColumn || amountColumn === dateColumn) {
        toastr.error('لا يمكن اختيار نفس الحقل لحقول مختلفة');
        return false;
    }
    return true;
}

function showImportConfirmation(importData) {
    Swal.fire({
        title: 'تأكيد الاستيراد',
        text: `سيتم استيراد ${uploadedFileData.total_rows.toLocaleString()} سجل. هل أنت متأكد من المتابعة؟`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، استورد الآن!',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#28a745',
    }).then((result) => {
        if (result.isConfirmed) {
            startImportProcess(importData);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Import Processing
|--------------------------------------------------------------------------
*/
function startImportProcess(importData) {
    $('#step-2').slideUp();
    $('#step-3').slideDown();
    $('#import-progress').show();
    $.ajax({
        url: window.routes.processImport,
        type: 'POST',
        data: { ...importData, _token: window.csrfToken },
        success: function (response) {
            if (response.success) {
                if (response.data.is_queued) {
                    startProgressPolling(response.data.job_id);
                } else {
                    setTimeout(() => {
                        $('#import-progress').hide();
                        showImportResults(response.data);
                    }, 1500);
                }
            } else {
                $('#import-progress').hide();
                toastr.error(response.message);
            }
        },
        error: (xhr) => {
            $('#import-progress').hide();
            toastr.error(xhr.responseJSON?.message || 'حدث خطأ أثناء الاستيراد.');
        }
    });
}

function startProgressPolling(jobId) {
    let consecutiveErrors = 0;
    const maxConsecutiveErrors = 5;

    const progressInterval = setInterval(() => {
        $.ajax({
            url: window.routes.getProgress,
            type: 'GET',
            data: { job_id: jobId },
            timeout: 10000,
            success: function (response) {
                consecutiveErrors = 0;

                if (response.success) {
                    updateProgressUI(response.data);

                    if (response.data.completed || response.data.failed) {
                        clearInterval(progressInterval);
                        setTimeout(() => {
                            $('#import-progress').hide();
                            if (response.data.failed) {
                                toastr.error(response.data.message);
                            } else {
                                showImportResults(response.data.results);
                            }
                        }, 1000);
                    }
                } else {
                    consecutiveErrors++;
                    if (consecutiveErrors >= maxConsecutiveErrors) {
                        clearInterval(progressInterval);
                        $('#import-progress').hide();
                        toastr.error('توقفت عملية مراقبة التقدم بسبب أخطاء متكررة');
                    }
                }
            },
            error: function (xhr, status, error) {
                consecutiveErrors++;
                console.error('خطأ في مراقبة التقدم:', error);

                if (consecutiveErrors >= maxConsecutiveErrors) {
                    clearInterval(progressInterval);
                    $('#import-progress').hide();
                    toastr.error('فقدت الاتصال مع خادم التقدم. يرجى تحديث الصفحة للتحقق من حالة الاستيراد.');
                }
            }
        });
    }, 2000);

    // إضافة تنظيف تلقائي بعد 30 دقيقة
    setTimeout(() => {
        clearInterval(progressInterval);
    }, 30 * 60 * 1000);
}

/*
|--------------------------------------------------------------------------
| Update Progress UI - Enhanced Version
|--------------------------------------------------------------------------
*/
function updateProgressUI(progressData) {
    const progressBar = $('.progress-bar');
    const progressText = $('#progress-text');
    const progressPercentage = Math.round(progressData.progress);

    // تحديث عرض الشريط
    progressBar.css('width', progressPercentage + '%');

    // تحديث النص داخل الشريط
    let displayText = `${progressPercentage}%`;

    // إضافة تفاصيل المعالجة إذا كانت متوفرة
    if (progressData.message && progressData.message.includes('تم معالجة')) {
        const detailsMatch = progressData.message.match(/تم معالجة ([\d,]+) من ([\d,]+) سجل/);
        if (detailsMatch) {
            const processed = detailsMatch[1];
            const total = detailsMatch[2];
            displayText = `${progressPercentage}% (${processed}/${total})`;
        }
    }

    // تأكد من أن النص يظهر داخل الشريط
    progressText.text(displayText).show();

    // تحديث الرسالة الرئيسية - بدون التفاصيل الرقمية
    let mainMessage = progressData.message;
    if (mainMessage && mainMessage.includes('تم معالجة')) {
        mainMessage = 'جاري معالجة البيانات...';
    }
    $('#progress-message').text(mainMessage);

    // تحديث التفاصيل
    $('#progress-details').text(
        progressData.updated_at ?
            `آخر تحديث: ${new Date(progressData.updated_at).toLocaleTimeString('ar')}` :
            ''
    );
}

/*
|--------------------------------------------------------------------------
| Results and Utility Functions
|--------------------------------------------------------------------------
*/
function showImportResults(results) {
    let resultsHtml = '';
    if (results.successful > 0) {
        resultsHtml += `
            <div class="result-success text-center mb-4">
                <i class="ti ti-check-circle text-success" style="font-size: 3rem;"></i>
                <h5 class="text-success mb-2">نجاح الاستيراد</h5>
                <p class="mb-0 fw-medium">تم استيراد <span class="fw-bold">${results.successful.toLocaleString()}</span> تحصيل بنجاح.</p>
            </div>`;
    }
    if (results.failed > 0) {
        resultsHtml += `
            <div class="result-error text-center mb-4">
                <i class="ti ti-x-circle text-danger" style="font-size: 2rem;"></i>
                <h6 class="text-danger mb-2">فشل في بعض السجلات</h6>
                <p class="mb-3">فشل استيراد <span class="fw-bold">${results.failed.toLocaleString()}</span> سجل.</p>
                <button type="button" class="btn btn-outline-danger" onclick="downloadErrorReport('${results.error_report_path}')">
                    <i class="ti ti-file-download me-1"></i> تحميل تقرير الأخطاء
                </button>
            </div>`;
    }
    resultsHtml += `
        <div class="d-flex gap-3 justify-content-center">
            <button type="button" class="btn btn-outline-secondary" onclick="resetImport()">استيراد جديد</button>
            <a href="${window.routes.collectionsIndex}" class="btn btn-primary">عرض التحصيلات</a>
        </div>`;
    $('#results-summary').html(resultsHtml).show();
}

function downloadTemplate() {
    window.open(window.routes.downloadTemplate, '_blank');
    toastr.success('جاري تحميل النموذج...');
}

function downloadErrorReport(reportPath) {
    window.open(`${window.routes.downloadErrorReport}?report_path=${encodeURIComponent(reportPath)}`, '_blank');
    toastr.success('جاري تحميل تقرير الأخطاء...');
}

function resetImport() {
    const tempPath = $('#temp_path').val();
    if (tempPath) {
        $.post(window.routes.cancelImport, { _token: window.csrfToken, temp_path: tempPath });
    }
    window.location.reload();
}
