// resources/assets/js/tenants/business-center/customers/import/index.js
/**
 * Customer Import JavaScript
 * نظام استيراد العملاء
 */

'use strict';

/*
|--------------------------------------------------------------------------
| Global Variables
|--------------------------------------------------------------------------
*/
let currentStep = 1;
let selectedPortfolioId = null;
let analysisData = null;
let tempFilePath = null;
let importDropzone = null;
let hiddenFileInput = null;
let currentSessionId = null;
let pollingInterval = null;

/*
|--------------------------------------------------------------------------
| Initialization
|--------------------------------------------------------------------------
*/
$(document).ready(function () {
    initializeComponents();
    bindEvents();
});

function initializeComponents() {
    $('.select2').select2({
        placeholder: function () { return $(this).data('placeholder'); },
        allowClear: true,
        width: '100%',
        language: 'ar',
        dir: 'rtl'
    });

    initializeDropzone();
    initializeFileUpload();
}

function initializeDropzone() {
    Dropzone.autoDiscover = false;

    importDropzone = new Dropzone("#import-dropzone", {
        url: window.routes.uploadAndAnalyze,
        maxFiles: 1,
        maxFilesize: 50,
        acceptedFiles: '.xlsx,.xls',
        addRemoveLinks: true,
        autoProcessQueue: false,
        dictDefaultMessage: '',
        dictRemoveFile: 'حذف',
        clickable: false,

        init: function () {
            this.on("addedfile", function (file) {
                resetAllOldResults();
                $('#btn-analyze-file').prop('disabled', false);
                updateFileUploadArea(file.name);
            });

            this.on("removedfile", function (file) {
                $('#btn-analyze-file').prop('disabled', true);
                resetFileUploadArea();
                resetAnalysisResults();
            });

            this.on("error", function (file, message) {
                toastr.error(`خطأ في الملف: ${message}`);
                this.removeFile(file);
                resetFileUploadArea();
            });
        }
    });
}

function initializeFileUpload() {
    hiddenFileInput = document.getElementById('hidden-file-input');

    $('#file-upload-area').on('click', function (e) {
        e.preventDefault();
        hiddenFileInput.click();
    });

    $(hiddenFileInput).on('change', function (e) {
        const file = e.target.files[0];
        if (file) {
            importDropzone.addFile(file);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Event Binding
|--------------------------------------------------------------------------
*/
function bindEvents() {
    $('#company_id').on('change', function () {
        loadPortfolios($(this).val());
    });

    $('#portfolio_id').on('change', function () {
        selectedPortfolioId = $(this).val();
        updateTemplateButtons();
    });

    $('#btn-preview-template').on('click', previewTemplate);
    $('#btn-download-template, #btn-download-template-modal').on('click', downloadTemplate);
    $('#btn-analyze-file').on('click', analyzeFile);
    $('#btn-proceed-import').on('click', proceedImport);

    // Global functions
    window.removeSelectedFile = removeSelectedFile;
    window.resetImport = resetImport;
    window.showQueuedErrorDetails = showQueuedErrorDetails;
    window.hideQueuedErrorDetails = hideQueuedErrorDetails;
    window.cancelCurrentImport = cancelCurrentImport;
}

/*
|--------------------------------------------------------------------------
| Portfolio Management
|--------------------------------------------------------------------------
*/
function loadPortfolios(companyId) {
    const portfolioSelect = $('#portfolio_id');
    portfolioSelect.val('').trigger('change').prop('disabled', true);
    selectedPortfolioId = null;
    updateTemplateButtons();

    if (!companyId) return;

    $.ajax({
        url: window.routes.getPortfoliosByCompany,
        type: 'GET',
        data: { company_id: companyId },
        success: function (response) {
            if (response.success) {
                portfolioSelect.empty().append('<option value="">اختر المحفظة</option>');
                response.portfolios.forEach(portfolio => {
                    portfolioSelect.append(`<option value="${portfolio.id}">${portfolio.name}</option>`);
                });
                portfolioSelect.prop('disabled', false);
            } else {
                toastr.error(response.message);
            }
        },
        error: function () {
            toastr.error('حدث خطأ أثناء جلب المحافظ');
        }
    });
}

function updateTemplateButtons() {
    const hasPortfolio = selectedPortfolioId !== null && selectedPortfolioId !== '';
    $('#btn-preview-template, #btn-download-template').prop('disabled', !hasPortfolio);

    if (hasPortfolio) {
        showStep(2);
    }
}

/*
|--------------------------------------------------------------------------
| File Upload Management
|--------------------------------------------------------------------------
*/
function updateFileUploadArea(fileName) {
    $('#file-upload-area').html(`
        <div class="d-flex align-items-center justify-content-between p-3" style="background: linear-gradient(135deg, #e8f5e8, #f1f8e9); border-radius: 8px;">
            <div class="d-flex align-items-center">
                <i class="ti ti-file-check me-2 text-success" style="font-size: 1.5rem;"></i>
                <div>
                    <h6 class="mb-0 text-success">${fileName}</h6>
                    <small class="text-muted">تم تحديد الملف بنجاح</small>
                </div>
            </div>
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removeSelectedFile()">
                <i class="ti ti-x"></i>
            </button>
        </div>
    `);
}

function resetFileUploadArea() {
    $('#file-upload-area').html(`
        <div id="import-dropzone" class="w-100 h-100">
            <div class="d-flex flex-column align-items-center">
                <i class="ti ti-cloud-upload mb-3" style="font-size: 3rem; color: #6c757d;"></i>
                <h5 class="mb-2 text-primary">اسحب الملف هنا أو انقر للاختيار</h5>
                <p class="text-muted mb-0">ملفات Excel فقط (.xlsx, .xls) - الحد الأقصى 50 ميجا</p>
            </div>
        </div>
    `);

    $('#file-upload-area').off('click').on('click', function (e) {
        e.preventDefault();
        hiddenFileInput.click();
    });

    hiddenFileInput.value = '';
}

function removeSelectedFile() {
    importDropzone.removeAllFiles();
    resetFileUploadArea();
}

/*
|--------------------------------------------------------------------------
| Template Management
|--------------------------------------------------------------------------
*/
function previewTemplate() {
    if (!selectedPortfolioId) {
        toastr.warning('يرجى اختيار المحفظة أولاً');
        return;
    }

    $.ajax({
        url: window.routes.previewTemplate,
        type: 'GET',
        data: { portfolio_id: selectedPortfolioId },
        success: function (response) {
            if (response.success) {
                showTemplatePreview(response.data);
            }
        },
        error: function () {
            toastr.error('حدث خطأ أثناء معاينة النموذج');
        }
    });
}

function showTemplatePreview(data) {
    let content = `
        <div class="mb-4">
            <div class="border rounded p-4 bg-white">
                <div class="row">
                    <div class="col-md-8">
                        <h6 class="mb-2 fw-bold text-dark">معلومات المحفظة</h6>
                        <p class="mb-1"><strong>الشركة:</strong> ${data.company_name}</p>
                        <p class="mb-0"><strong>المحفظة:</strong> ${data.portfolio_name}</p>
                        ${data.has_split_names ? '<p class="mb-0 text-info"><small>📝 تدعم الأسماء المنفصلة</small></p>' : ''}
                    </div>
                    <div class="col-md-4 text-md-end">
                        <div class="text-center">
                            <h4 class="mb-0 text-dark">${data.total_fields}</h4>
                            <small class="text-muted">إجمالي الحقول</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;

    content += `
        <div class="mb-4 border rounded p-4 bg-light">
            <h6 class="mb-3 text-dark fw-bold">ملاحظات مهمة:</h6>
            <ul class="mb-0 text-dark">
                <li class="mb-2">يجب أن يحتوي الصف الأول من ملف Excel على أسماء الحقول كما هو موضح أعلاه</li>
                <li class="mb-2">يمكنك إضافة أعمدة جديدة وسيتم إنشاؤها تلقائياً</li>
                <li class="mb-0">تأكد من حفظ الملف بصيغة .xlsx أو .xls</li>
            </ul>
        </div>
    `;


    //  الحقول الإجبارية بلون أحمر
    if (data.required_headers && data.required_headers.length > 0) {
        content += `
            <div class="mb-4">
                <h6 class="mb-3 text-danger fw-bold">
                    الحقول الإجبارية (${data.required_headers.length})
                </h6>
                <div class="border rounded p-3" style="background-color: #fff5f5; border: 2px solid #dc3545;">
                    <div class="row g-2">`;

        data.required_headers.forEach((field, index) => {
            content += `
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-2 border-2 border-danger rounded" style="background-color: #ffbcbc;">
                                 <span class="badge bg-danger text-white me-2 small">${index + 1}</span>
                                <span class="text-danger fw-bold flex-grow-1">${field}</span>
                                <span class="badge bg-danger small">إجباري</span>
                            </div>
                        </div>`;
        });

        content += `</div></div></div>`;
    }

    //  الحقول الاختيارية
    if (data.optional_headers && data.optional_headers.length > 0) {
        content += `
            <div class="mb-4">
                <h6 class="mb-3 text-primary fw-bold">
                    الحقول الاختيارية (${data.optional_headers.length})
                </h6>
                <div class="border rounded p-3 bg-white">
                    <div class="row g-2">`;

        data.optional_headers.forEach((field, index) => {
            content += `
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-2 border rounded bg-light">
                                 <span class="badge bg-secondary text-white me-2 small">${index + 1}</span>
                                <span class="text-truncate flex-grow-1">${field}</span>
                            </div>
                        </div>`;
        });

        content += `</div></div></div>`;
    }

    if (data.dynamic_fields.length > 0) {

        content += `
        <div class="mb-4">
            <h6 class="mb-3 text-dark fw-bold">الحقول الإضافية للمحفظة (${data.dynamic_fields.length})</h6>
            <div class="border rounded p-3 bg-white">
                <div class="row g-2">`;

        data.dynamic_fields.forEach((field, index) => {
            content += `
                    <div class="col-md-6">
                        <div class="d-flex align-items-center p-2 border rounded bg-light">
                            <span class="badge bg-secondary text-white me-2 small">${index + 1}</span>
                            <span class="text-truncate fw-medium" title="${field}">${field}</span>
                        </div>
                    </div>`;
        });

        content += `</div></div></div>`;
    }



    $('#template-preview-content').html(content);
    $('#templatePreviewModal').modal('show');
}

function downloadTemplate() {
    if (!selectedPortfolioId) {
        toastr.warning('يرجى اختيار المحفظة أولاً');
        return;
    }

    window.open(`${window.routes.downloadTemplate}?portfolio_id=${selectedPortfolioId}`, '_blank');
    toastr.success('جاري تحميل النموذج...');
    $('#templatePreviewModal').modal('hide');
}

/*
|--------------------------------------------------------------------------
| File Analysis
|--------------------------------------------------------------------------
*/
function analyzeFile() {
    const files = importDropzone.getAcceptedFiles();

    if (files.length === 0) {
        toastr.warning('يرجى اختيار ملف أولاً');
        return;
    }

    const formData = new FormData();
    formData.append('import_file', files[0]);
    formData.append('portfolio_id', selectedPortfolioId);
    formData.append('_token', window.csrfToken);

    $('#btn-analyze-file').prop('disabled', true).html('<i class="spinner-border spinner-border-sm me-1"></i>جاري التحليل...');

    $.ajax({
        url: window.routes.uploadAndAnalyze,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 300000,
        success: function (response) {
            if (response.success) {
                analysisData = response.data;
                tempFilePath = response.data.temp_path;
                showAnalysisResults(analysisData);
            } else {
                toastr.error(response.message);
            }
        },
        error: function (xhr) {
            let message = 'حدث خطأ أثناء تحليل الملف';
            if (xhr.responseJSON?.message) {
                message = xhr.responseJSON.message;
            }
            toastr.error(message);
        },
        complete: function () {
            $('#btn-analyze-file').prop('disabled', false).html('<i class="ti ti-search me-1"></i>تحليل الملف');
        }
    });
}

function showAnalysisResults(data) {
    $('#general-stats').html(`
        <div class="row g-2 mb-4">
            <div class="col-6">
                <div class="text-center p-3 bg-light rounded">
                    <h4 class="mb-0 text-primary">${data.total_rows.toLocaleString()}</h4>
                    <small class="text-muted fw-medium">إجمالي الصفوف</small>
                </div>
            </div>
            <div class="col-6">
                <div class="text-center p-3 bg-light rounded">
                    <h4 class="mb-0 text-primary">${data.total_columns}</h4>
                    <small class="text-muted fw-medium">إجمالي الأعمدة</small>
                </div>
            </div>
        </div>
    `);

    let fieldsHtml = '';

    // ✅ عرض تحذير إذا كانت حقول إجبارية مفقودة
    if (data.missing_required && data.missing_required.length > 0) {
        fieldsHtml += `
            <div class="alert alert-danger mb-4" role="alert">
                <div class="d-flex align-items-start">
                    <i class="ti ti-alert-circle me-2 mt-1" style="font-size: 1.25rem;"></i>
                    <div>
                        <h6 class="alert-heading mb-2">⚠️ لا يمكن رفع الملف</h6>
                        <p class="mb-2">الحقول الإجبارية التالية غير موجودة في الملف:</p>
                        <div class="row g-2">`;

        data.missing_required.forEach(field => {
            fieldsHtml += `
                <div class="col-auto">
                    <span class="badge bg-danger">${field}</span>
                </div>`;
        });

        fieldsHtml += `
                        </div>
                    </div>
                </div>
            </div>`;
    }

    let fieldMapping = [];
    if (data.header_analysis && data.header_analysis.field_mapping) {
        if (Array.isArray(data.header_analysis.field_mapping)) {
            fieldMapping = data.header_analysis.field_mapping;
        } else {
            fieldMapping = Object.values(data.header_analysis.field_mapping);
        }
    }

    // ========== الحقول الأساسية الإجبارية ==========
    if (data.required_headers && data.required_headers.length > 0) {
        fieldsHtml += `
            <div class="mb-4">
                <h6 class="mb-3 text-danger fw-bold">
                    <i class="ti ti-alert-circle me-2"></i>الحقول الإجبارية (${data.required_headers.length})
                </h6>
                <div class="row g-2">`;

        data.required_headers.forEach(requiredField => {
            const isFound = fieldMapping.some(f =>
                (f.category === 'core' || f.type === 'static') && f.display_name === requiredField
            );

            const borderClass = isFound ? '' : 'border-danger border-2';
            const bgClass = isFound ? 'bg-light' : 'bg-danger bg-opacity-10';

            fieldsHtml += `
                <div class="col-md-6">
                    <div class="d-flex align-items-center justify-content-between p-2 rounded ${borderClass} ${bgClass}">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="ti ti-alert-circle me-2 ${isFound ? 'text-success' : 'text-danger'}"></i>
                            <span class="text-truncate ${isFound ? 'text-dark' : 'text-danger fw-bold'}" title="${requiredField}">
                                ${requiredField}
                            </span>
                        </div>
                        <span class="badge ${isFound ? 'bg-success' : 'bg-danger'} ms-2">${isFound ? 'موجود' : 'غير موجود'}</span>
                    </div>
                </div>`;
        });

        fieldsHtml += '</div></div>';
    }

    // ========== الحقول الأساسية الاختيارية ==========
    if (data.optional_headers && data.optional_headers.length > 0) {
        fieldsHtml += `
            <div class="mb-4">
                <h6 class="mb-3 text-primary fw-bold">
                    <i class="ti ti-database me-2"></i>الحقول الاختيارية (${data.optional_headers.length})
                </h6>
                <div class="row g-2">`;

        data.optional_headers.forEach(optionalField => {
            const isFound = fieldMapping.some(f =>
                (f.category === 'core' || f.type === 'static') && f.display_name === optionalField
            );

            fieldsHtml += `
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="ti ti-database me-2 text-primary"></i>
                            <span class="text-truncate" title="${optionalField}">${optionalField}</span>
                        </div>
                        <span class="badge ${isFound ? 'bg-success' : 'bg-warning text-dark'} ms-2">${isFound ? 'موجود' : 'غير موجود'}</span>
                    </div>
                </div>`;
        });

        fieldsHtml += '</div></div>';
    }

    // ========== الحقول الديناميكية الموجودة ==========
    const existingDynamicFields = fieldMapping.filter(field => field.category === 'existing_dynamic');
    if (existingDynamicFields.length > 0) {
        fieldsHtml += `
            <div class="mb-4">
                <h6 class="mb-3 text-success fw-bold">
                    <i class="ti ti-folder me-2"></i>الحقول الإضافية الموجودة (${existingDynamicFields.length})
                </h6>
                <div class="row g-2">`;

        existingDynamicFields.forEach(field => {
            fieldsHtml += `
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="ti ti-folder me-2 text-success"></i>
                            <span class="text-truncate" title="${field.display_name}">${field.display_name}</span>
                        </div>
                        <span class="badge bg-success ms-2">موجود</span>
                    </div>
                </div>`;
        });

        fieldsHtml += '</div></div>';
    }

    // ========== الحقول الجديدة ==========
    const newDynamicFields = fieldMapping.filter(field => field.category === 'new_dynamic');
    if (newDynamicFields.length > 0) {
        fieldsHtml += `
            <div class="mb-4">
                <h6 class="mb-3 text-warning fw-bold">
                    <i class="ti ti-plus-circle me-2"></i>الحقول الجديدة (${newDynamicFields.length})
                </h6>
                <div class="row g-2">`;

        newDynamicFields.forEach(field => {
            fieldsHtml += `
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center justify-content-between p-2 border rounded" style="background: #fff3e0;">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="ti ti-plus me-2 text-warning"></i>
                            <span class="text-truncate" title="${field.display_name}">${field.display_name}</span>
                        </div>
                        <span class="badge bg-warning text-dark ms-2">جديد</span>
                    </div>
                </div>`;
        });

        fieldsHtml += `
                </div>
                <div class="mt-3 p-3 bg-light rounded border-start border-warning border-4">
                    <small class="text-muted fw-medium">
                        <i class="ti ti-info-circle me-1"></i>
                        سيتم إنشاء هذه الحقول تلقائياً في المحفظة عند بدء عملية الاستيراد
                    </small>
                </div>
            </div>`;
    }

    // ========== الهواتف الإضافية ==========
    const phoneFields = fieldMapping.filter(field => field.category === 'phone');
    if (phoneFields.length > 0) {
        fieldsHtml += `
            <div class="mb-4">
                <h6 class="mb-3 text-info fw-bold">
                    <i class="ti ti-phone me-2"></i>الهواتف الإضافية (${phoneFields.length})
                </h6>
                <div class="row g-2">`;

        phoneFields.forEach(field => {
            fieldsHtml += `
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center justify-content-between p-2 border rounded bg-light">
                        <div class="d-flex align-items-center flex-grow-1">
                            <i class="ti ti-phone me-2 text-info"></i>
                            <span class="text-truncate" title="${field.display_name}">${field.display_name}</span>
                        </div>
                        <span class="badge bg-info ms-2">هاتف</span>
                    </div>
                </div>`;
        });

        fieldsHtml += '</div></div>';
    }

    // ✅ عطّل زر البدء إذا كانت حقول إجبارية مفقودة
    if (data.missing_required && data.missing_required.length > 0) {
        $('#btn-proceed-import').prop('disabled', true).addClass('disabled');
    } else {
        $('#btn-proceed-import').prop('disabled', false).removeClass('disabled');
    }

    $('#fields-analysis').html(fieldsHtml);
    $('#analysis-results').show();
}
/*
|--------------------------------------------------------------------------
| Import Processing
|--------------------------------------------------------------------------
*/
function proceedImport() {
    if (!tempFilePath || !selectedPortfolioId) {
        toastr.error('بيانات غير مكتملة للاستيراد');
        return;
    }


    Swal.fire({
        title: 'تأكيد الاستيراد',
        html: `
            <p>هل أنت متأكد من متابعة عملية الاستيراد؟</p>
            <small class="text-muted">سيتم معالجة ${analysisData.total_rows.toLocaleString()} صف</small>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'نعم، متابعة',
        cancelButtonText: 'إلغاء',
        confirmButtonColor: '#28a745',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            showStep(3);

            processImport();
        } else {
            // ✅ إخفاء Step-3 إذا تم الإلغاء
            $('#step-3').hide();
        }
    });
}


function processImport() {
    // console.log('processImport: Function started.');
    // console.log('processImport: tempFilePath =', tempFilePath);
    // console.log('processImport: selectedPortfolioId =', selectedPortfolioId);

    window.queuedImportErrors = [];

    $('#import-progress').show();
    $('#import-progress .spinner-border').removeClass('d-none');
    $('.progress-bar').css('width', '0%').removeClass('bg-danger').addClass('bg-primary').text('جاري التحضير...');

    const formData = new FormData();
    formData.append('temp_path', tempFilePath);
    formData.append('portfolio_id', selectedPortfolioId);
    formData.append('_token', window.csrfToken);

    // console.log('processImport: FormData prepared.');

    $.ajax({
        url: window.routes.processImport,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        timeout: 30000,
        dataType: 'json',
        success: function (response) {
            // console.log('processImport: AJAX success. Raw response:', response);

            // تحسين الشرط للتأكد من وجود الهيكل الصحيح
            if (response && response.success === true) {
                if (response.data && response.data.session_id) {
                    // console.log('processImport: Polling started. Session ID:', response.data.session_id);
                    currentSessionId = response.data.session_id;
                    $('#import-progress .spinner-border').addClass('d-none');
                    $('.progress-bar').css('width', '0%').text('0% - جاري المعالجة...');
                    toastr.success('تم بدء عملية الاستيراد');
                    startProgressPolling();
                    addCancelButton();
                } else {
                    // console.error('processImport: Missing session_id in response.data');
                    $('#import-progress').hide();
                    toastr.error('استجابة غير مكتملة من الخادم - session_id مفقود');
                }
            } else {
                // console.warn('processImport: Operation failed.', response);
                $('#import-progress').hide();
                const errorMessage = response?.message || 'حدث خطأ أثناء بدء الاستيراد';
                toastr.error(errorMessage);
            }
        },
        error: function (jqXHR, textStatus, errorThrown) {
            // console.error('processImport: AJAX error.', {
            //     textStatus: textStatus,
            //     errorThrown: errorThrown,
            //     status: jqXHR.status,
            //     responseText: jqXHR.responseText ? jqXHR.responseText.substring(0, 500) : 'No response text'
            // });
            $('#import-progress').hide();

            let message = 'حدث خطأ أثناء الاستيراد';
            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                message = jqXHR.responseJSON.message;
            } else if (jqXHR.status === 0) {
                message = 'انقطع الاتصال بالخادم';
            } else if (jqXHR.status >= 500) {
                message = 'خطأ في الخادم';
            }
            toastr.error(message);
        }
    });
}

/*
|--------------------------------------------------------------------------
| Progress Polling
|--------------------------------------------------------------------------
*/
function startProgressPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
    }

    // بدء فوري ثم تكرار كل ثانيتين للمتابعة الأسرع
    checkImportProgress();
    pollingInterval = setInterval(checkImportProgress, 2000);
}

function checkImportProgress() {
    if (!currentSessionId) return;

    $.ajax({
        url: window.routes.checkImportStatus,
        type: 'GET',
        data: { session_id: currentSessionId },
        timeout: 10000,
        success: function (response) {
            if (response.success && response.data) {
                updateProgressUI(response.data);

                if (response.data.status === 'completed') {
                    stopProgressPolling();
                    showImportResults(response.data);
                } else if (response.data.status === 'failed') {
                    stopProgressPolling();
                    showImportError(response.data);
                } else if (response.data.status === 'cancelled') {
                    stopProgressPolling();
                    showImportCancelled();
                }
            }
        },
        error: function () {
            // لا تفعل شيئًا فقط استمر في المحاولة
        }
    });
}

function updateProgressUI(data) {
    const percentage = Math.max(0, Math.min(100, data.progress_percentage || 0));
    const processed = data.processed_rows || 0;
    const totalRows = data.total_rows || 0;

    // تحديث شريط التقدم
    const progressBar = $('.progress-bar');
    progressBar.css('width', percentage + '%');
    progressBar.attr('aria-valuenow', percentage);

    // تغيير اللون بناءً على الحالة
    progressBar.removeClass('bg-primary bg-success bg-warning bg-danger');
    if (data.status === 'completed') {
        progressBar.addClass('bg-success');
    } else if (data.status === 'failed') {
        progressBar.addClass('bg-danger');
    } else {
        progressBar.addClass('bg-primary');
    }

    // تحديث النص
    let progressText = `${Math.round(percentage)}%`;
    if (totalRows > 0) {
        progressText += ` - ${processed.toLocaleString()} من ${totalRows.toLocaleString()}`;
    }
    progressBar.text(progressText);
}

function stopProgressPolling() {
    if (pollingInterval) {
        clearInterval(pollingInterval);
        pollingInterval = null;
    }
}

/*
|--------------------------------------------------------------------------
| Results Display
|--------------------------------------------------------------------------
*/
function showImportResults(data) {
    $('#import-progress').hide();

    const successRate = data.total_rows > 0
        ? ((data.successful_rows / data.total_rows) * 100).toFixed(1)
        : 0;

    let resultsHtml = `
        <div class="result-summary text-center mb-4">
            <div class="mb-3">
                <i class="ti ti-check-circle text-success" style="font-size: 3rem;"></i>
            </div>
            <h5 class="text-success mb-2">اكتملت عملية الاستيراد!</h5>
            <p class="mb-0 fw-medium">
                تم معالجة <span class="text-primary fw-bold">${data.total_rows?.toLocaleString() || 0}</span> صف
                <br>
                <span class="text-success fw-bold">${data.successful_rows?.toLocaleString() || 0}</span> عميل تم إنشاؤه بنجاح
                <small class="d-block text-muted mt-1">(نسبة النجاح: ${successRate}%)</small>
            </p>
            <small class="text-muted d-block mt-2">
                وقت الانتهاء: ${new Date(data.completed_at).toLocaleString('en-US')}
            </small>
        </div>
    `;

    if (data.failed_rows > 0) {
        resultsHtml += `
            <div class="result-error text-center mb-4">
                <div class="alert alert-warning" role="alert">
                    <h6 class="alert-heading mb-2">
                        <i class="ti ti-alert-triangle me-2"></i>
                        يوجد ${data.failed_rows.toLocaleString()} صف لم يتم معالجته
                    </h6>
                    <button type="button" class="btn btn-outline-warning btn-sm" onclick="showQueuedErrorDetails()">
                        <i class="ti ti-eye me-1"></i>عرض تفاصيل الأخطاء
                    </button>
                    <a href="${window.routes.downloadErrorReport}?session_id=${currentSessionId}"
                       class="btn btn-outline-warning btn-sm ms-2">
                        <i class="ti ti-download me-1"></i>تحميل تقرير الأخطاء
                    </a>
                </div>
            </div>
        `;
    }

    resultsHtml += `
        <div class="d-flex gap-3 justify-content-center mt-4 flex-wrap">
            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetImport()">
                <i class="ti ti-refresh me-1"></i>استيراد جديد
            </button>

        </div>
    `;

    $('#results-content').html(resultsHtml);
    $('#results-summary').show();

    // تخزين الأخطاء للاستخدام لاحقًا
    window.queuedImportErrors = data.errors || [];
}

function showQueuedErrorDetails() {
    if (!window.queuedImportErrors || window.queuedImportErrors.length === 0) {
        toastr.warning('لا توجد أخطاء للعرض');
        return;
    }

    let errorDetailsHtml = `
        <div class="card border-warning mb-3 mt-3">
            <div class="card-header bg-warning bg-opacity-10 border-0">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="text-warning mb-0">
                        <i class="ti ti-alert-triangle me-2"></i>
                        تفاصيل الأخطاء (${window.queuedImportErrors.length} خطأ)
                    </h6>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 400px;">
                    <table class="table table-sm mb-0">
                        <thead class="table-light sticky-top">
                            <tr>
                                <th style="width: 80px;" class="text-center">#الصف</th>
                                <th>تفاصيل الخطأ</th>
                            </tr>
                        </thead>
                        <tbody>`;

    const displayErrors = window.queuedImportErrors.slice(0, 100);
    displayErrors.forEach(error => {
        errorDetailsHtml += `
            <tr>
                <td class="text-center">
                    <span class="badge bg-warning">${error.row}</span>
                </td>
                <td class="text-start">
                    <small>${error.errors.join(' | ')}</small>
                </td>
            </tr>
        `;
    });

    if (window.queuedImportErrors.length > 100) {
        errorDetailsHtml += `
            <tr>
                <td colspan="2" class="text-center bg-light">
                    <small class="text-muted">
                        <i class="ti ti-dots me-1"></i>
                        و ${window.queuedImportErrors.length - 100} خطأ آخر...
                    </small>
                </td>
            </tr>
        `;
    }

    errorDetailsHtml += `
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    `;

    $('.result-error').after(errorDetailsHtml);

    $('button[onclick="showQueuedErrorDetails()"]')
        .attr('onclick', 'hideQueuedErrorDetails()')
        .html('<i class="ti ti-eye-off me-1"></i>إخفاء تفاصيل الأخطاء');
}

function hideQueuedErrorDetails() {
    $('.card.border-warning').remove();
    $('button[onclick="hideQueuedErrorDetails()"]')
        .attr('onclick', 'showQueuedErrorDetails()')
        .html('<i class="ti ti-eye me-1"></i>عرض تفاصيل الأخطاء');
}

function showImportError(data) {
    $('#import-progress').hide();

    //  اقرأ من errors الموجودة!
    let errorMessage = 'حدث خطأ أثناء عملية الاستيراد';

    if (data.errors && data.errors.length > 0) {
        // اول خطأ
        errorMessage = data.errors[0].errors?.[0] || errorMessage;
    }

    const errorHtml = `
        <div class="result-error text-center">
            <div class="mb-3">
                <i class="ti ti-x-circle text-danger" style="font-size: 3rem;"></i>
            </div>
            <h5 class="text-danger mb-3">فشلت العملية</h5>

            <!--  الخطأ من errors -->
            <div class="alert alert-danger mb-3">
                <p class="mb-0">${errorMessage}</p>
            </div>

            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetImport()">
                <i class="ti ti-refresh me-1"></i>محاولة جديدة
            </button>
        </div>
    `;

    $('#results-content').html(errorHtml);
    $('#results-summary').show();
}

function showImportCancelled() {
    $('#import-progress').hide();

    const cancelledHtml = `
        <div class="result-warning text-center">
            <div class="mb-3">
                <i class="ti ti-ban text-warning" style="font-size: 3rem;"></i>
            </div>
            <h5 class="text-warning mb-2">تم إلغاء العملية</h5>
            <p class="mb-3 fw-medium">تم إلغاء عملية الاستيراد بناءً على طلبك</p>
            <div class="d-flex gap-3 justify-content-center">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="resetImport()">
                    <i class="ti ti-refresh me-1"></i>بدء عملية جديدة
                </button>
            </div>
        </div>
    `;

    $('#results-content').html(cancelledHtml);
    $('#results-summary').show();
}

/*
|--------------------------------------------------------------------------
| Import Control
|--------------------------------------------------------------------------
*/
function addCancelButton() {
    if ($('#btn-cancel-import').length === 0) {
        const cancelButton = `
            <div class="d-flex justify-content-center mt-3">
                <button type="button" class="btn btn-outline-danger btn-sm" id="btn-cancel-import" onclick="cancelCurrentImport()">
                    <i class="ti ti-x me-1"></i>إلغاء العملية
                </button>
            </div>
        `;
        $('#import-progress').append(cancelButton);
    }
}

function cancelCurrentImport() {
    if (!currentSessionId) {
        toastr.warning('لا توجد عملية استيراد جارية');
        return;
    }

    Swal.fire({
        title: 'تأكيد الإلغاء',
        text: 'هل أنت متأكد من إلغاء عملية الاستيراد؟',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'نعم، إلغاء',
        cancelButtonText: 'استمرار',
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d'
    }).then((result) => {
        if (result.isConfirmed) {
            performCancelImport();
        }
    });
}

function performCancelImport() {
    $.ajax({
        url: window.routes.cancelImport,
        type: 'POST',
        data: {
            session_id: currentSessionId,
            _token: window.csrfToken
        },
        success: function (response) {
            if (response.success) {
                stopProgressPolling();
                showImportCancelled();
                toastr.success('تم إلغاء العملية بنجاح');
            } else {
                toastr.error(response.message || 'فشل في إلغاء العملية');
            }
        },
        error: function () {
            toastr.error('حدث خطأ أثناء إلغاء العملية');
        }
    });
}

/*
|--------------------------------------------------------------------------
| Utility Functions
|--------------------------------------------------------------------------
*/
function showStep(stepNumber) {
    $(`#step-${stepNumber}`).show();
}

function resetAnalysisResults() {
    $('#analysis-results').hide();
    analysisData = null;
    tempFilePath = null;
    $('#step-3').hide();
}

function resetImport() {
    stopProgressPolling();

    currentSessionId = null;
    currentStep = 1;
    selectedPortfolioId = null;
    analysisData = null;
    tempFilePath = null;
    window.queuedImportErrors = [];


    $('#company_id, #portfolio_id').val('').trigger('change');
    $('#portfolio_id').prop('disabled', true);
    $('#btn-preview-template, #btn-download-template, #btn-analyze-file').prop('disabled', true);

    if (importDropzone) {
        importDropzone.removeAllFiles();
    }

    resetFileUploadArea();

    $('#step-2, #step-3').hide();
    $('#analysis-results, #results-summary').hide();
    $('#import-progress').hide();
    $('#btn-cancel-import').remove();

    toastr.info('تم إعادة البدء');
}


/**
 * ✅ حذف جميع النتائج والبيانات القديمة
 */
function resetAllOldResults() {
    // ✅ إخفاء جميع النتائج والخطوات
    $('#analysis-results').hide();
    $('#results-summary').hide();
    $('#import-progress').hide();

    // ✅ تنظيف محتوى النتائج
    $('#results-content').html('');
    $('#fields-analysis').html('');
    $('#general-stats').html('');

    // ✅ إخفاء خطوة الاستيراد
    $('#step-3').hide();

    // ✅ حذف زر الإلغاء القديم
    $('#btn-cancel-import').remove();

    // ✅ تنظيف البيانات
    analysisData = null;
    tempFilePath = null;
    currentSessionId = null;
    window.queuedImportErrors = [];

    // ✅ إيقاف الـ polling
    stopProgressPolling();
}
