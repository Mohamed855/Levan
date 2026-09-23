/**
 * Customer Analysis JavaScript Module - Queue Version
 * نظام تحليل ملفات العملاء المحسن مع دعم الطوابير
 */

'use strict';

/*
|--------------------------------------------------------------------------
| Analysis Module Variables - متغيرات وحدة التحليل
|--------------------------------------------------------------------------
*/
const CustomerAnalysis = {
    // متغيرات الحالة
    currentSessionId: null,
    pollingInterval: null,
    lastUpdateTime: null,
    consecutiveFailures: 0,
    progressStartTime: null,

    // عناصر DOM
    elements: {
        analyzeButton: null,
        progressContainer: null,
        progressBar: null,
        resultsContainer: null,
        cancelButton: null
    },

    // إعدادات
    config: {
        pollingInterval: 2000, // 2 ثانية
        maxRetries: 5,
        timeout: 30000
    },

    /*
    |--------------------------------------------------------------------------
    | Initialization - التهيئة
    |--------------------------------------------------------------------------
    */
    init() {
        this.bindEvents();
        this.setupErrorHandling();
        console.log('Customer Analysis module initialized');
    },

    bindEvents() {
        // ربط الأحداث الأساسية
        $(document).ready(() => {
            this.elements.analyzeButton = $('#btn-analyze-file');
            this.elements.progressContainer = $('#analysis-progress');
            this.elements.progressBar = $('.analysis-progress-bar');
            this.elements.resultsContainer = $('#analysis-results');

            // ربط دالة التحليل
            if (this.elements.analyzeButton.length) {
                this.elements.analyzeButton.off('click').on('click', () => {
                    this.startAnalysis();
                });
            }
        });

        // دوال عامة
        window.cancelCurrentAnalysis = () => this.cancelAnalysis();
        window.retryAnalysisCheck = () => this.retryProgressCheck();
    },

    setupErrorHandling() {
        // معالجة أخطاء AJAX للتحليل
        $(document).ajaxError((event, xhr, settings) => {
            if (this.currentSessionId && settings.url.includes('check-analysis-status')) {
                this.consecutiveFailures++;

                if (this.consecutiveFailures >= this.config.maxRetries) {
                    console.error('Multiple analysis polling failures detected');
                    this.handlePollingFailure();
                }
            }
        });

        // معالجة انقطاع الاتصال
        window.addEventListener('offline', () => {
            if (this.currentSessionId) {
                toastr.warning('انقطع الاتصال بالإنترنت. سيتم استئناف متابعة التحليل عند عودة الاتصال.');
                this.stopProgressPolling();
            }
        });

        window.addEventListener('online', () => {
            if (this.currentSessionId) {
                toastr.success('تم استعادة الاتصال. استئناف متابعة التحليل...');
                this.startProgressPolling();
            }
        });
    },

    /*
    |--------------------------------------------------------------------------
    | Analysis Process - عملية التحليل
    |--------------------------------------------------------------------------
    */
    startAnalysis() {
        // الحصول على importDropzone من النطاق العام بدلاً من الوصول المباشر
        const dropzone = window.importDropzone;

        if (!dropzone) {
            toastr.error('نظام رفع الملفات غير متاح');
            return;
        }

        const files = dropzone.getAcceptedFiles();

        if (files.length === 0) {
            toastr.warning('يرجى اختيار ملف أولاً');
            return;
        }

        if (!window.selectedPortfolioId) {
            toastr.warning('يرجى اختيار المحفظة أولاً');
            return;
        }

        // تحضير البيانات
        const formData = new FormData();
        formData.append('import_file', files[0]);
        formData.append('portfolio_id', window.selectedPortfolioId);
        formData.append('_token', window.csrfToken);

        // تحديث واجهة المستخدم
        this.showAnalysisProgress();
        this.disableAnalyzeButton();

        console.log('🔍 Starting queued analysis process');

        // إرسال طلب بدء التحليل
        $.ajax({
            url: window.routes.startQueuedAnalysis,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            timeout: this.config.timeout,
            success: (response) => {
                if (response.success && response.use_polling) {
                    this.currentSessionId = response.session_id;
                    this.consecutiveFailures = 0;
                    this.progressStartTime = new Date();

                    console.log('✅ Analysis started successfully:', response.session_id);

                    // بدء متابعة التقدم
                    setTimeout(() => {
                        this.startProgressPolling();
                    }, 1000);

                    this.updateProgressUI({
                        progress_percentage: 0,
                        current_step: 'initializing'
                    });

                    toastr.success('تم بدء عملية التحليل بنجاح');

                    // إضافة زر الإلغاء
                    setTimeout(() => {
                        this.addCancelButton();
                    }, 3000);

                } else {
                    this.hideAnalysisProgress();
                    toastr.error(response.message || 'حدث خطأ أثناء بدء التحليل');
                    this.enableAnalyzeButton();
                }
            },
            error: (xhr) => {
                console.error('❌ Analysis start failed:', xhr);
                this.hideAnalysisProgress();
                this.enableAnalyzeButton();

                let message = 'حدث خطأ أثناء بدء التحليل';

                if (xhr.status === 413) {
                    message = 'الملف كبير جداً. يرجى تقليل حجم الملف';
                } else if (xhr.status === 422) {
                    message = xhr.responseJSON?.message || 'بيانات الملف غير صالحة';
                } else if (xhr.status === 0) {
                    message = 'انقطع الاتصال. يرجى التحقق من الإنترنت والمحاولة مرة أخرى';
                } else if (xhr.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }

                toastr.error(message);
            }
        });
    },
    /*
    |--------------------------------------------------------------------------
    | Progress Polling - متابعة التقدم
    |--------------------------------------------------------------------------
    */
    startProgressPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
        }

        console.log('🚀 Starting analysis progress polling for session:', this.currentSessionId);

        // فحص فوري ثم تكرار
        this.checkAnalysisProgress();

        this.pollingInterval = setInterval(() => {
            this.checkAnalysisProgress();
        }, this.config.pollingInterval);
    },

    checkAnalysisProgress() {
        if (!this.currentSessionId) {
            console.error('❌ No session ID for analysis progress check');
            return;
        }

        console.log('📊 Checking analysis progress for session:', this.currentSessionId);

        $.ajax({
            url: window.routes.checkAnalysisStatus,
            type: 'GET',
            data: { session_id: this.currentSessionId },
            dataType: 'json',
            timeout: 10000,
            success: (response) => {
                this.consecutiveFailures = 0;

                console.log('✅ Analysis progress response received:', response);

                if (response.success && response.data) {
                    const data = response.data;

                    console.log('📈 Analysis Progress Details:', {
                        status: data.status,
                        progress_percentage: data.progress_percentage,
                        current_step: data.current_step
                    });

                    this.updateProgressUI(data);

                    if (data.status === 'completed') {
                        console.log('🎉 Analysis completed!');
                        this.stopProgressPolling();
                        this.showAnalysisResults(data.analysis_result);
                    } else if (data.status === 'failed') {
                        console.log('❌ Analysis failed!');
                        this.stopProgressPolling();
                        this.showAnalysisError(data);
                    } else if (data.status === 'cancelled') {
                        console.log('⚠️ Analysis cancelled!');
                        this.stopProgressPolling();
                        this.showAnalysisCancelled();
                    } else {
                        console.log('⏳ Analysis still processing...');
                    }
                } else {
                    console.warn('⚠️ Invalid analysis response structure:', response);
                }
            },
            error: (xhr) => {
                this.consecutiveFailures++;

                console.error('❌ Analysis progress check failed:', {
                    status: xhr.status,
                    statusText: xhr.statusText,
                    consecutiveFailures: this.consecutiveFailures
                });

                if (this.consecutiveFailures >= this.config.maxRetries) {
                    this.handlePollingFailure();
                } else {
                    this.updateProgressUI({
                        connection_issues: true,
                        retry_count: this.consecutiveFailures
                    });
                }
            }
        });
    },

    stopProgressPolling() {
        if (this.pollingInterval) {
            clearInterval(this.pollingInterval);
            this.pollingInterval = null;
            console.log('🛑 Analysis progress polling stopped');
        }
    },

    handlePollingFailure() {
        this.stopProgressPolling();

        this.elements.progressBar.addClass('bg-danger').removeClass('bg-primary bg-success bg-warning')
            .text('مشكلة في الاتصال - لا يمكن متابعة التحليل');

        toastr.error('فشل في متابعة حالة التحليل. العملية قد تكون مستمرة في الخلفية.');

        const retryButton = `
            <button type="button" class="btn btn-outline-primary btn-sm ms-2" id="btn-retry-analysis" onclick="CustomerAnalysis.retryProgressCheck()">
                <i class="ti ti-refresh me-1"></i>إعادة المحاولة
            </button>
        `;

        if ($('#btn-retry-analysis').length === 0) {
            this.elements.progressContainer.append(retryButton);
        }
    },

    retryProgressCheck() {
        this.consecutiveFailures = 0;
        $('#btn-retry-analysis').remove();
        this.elements.progressBar.removeClass('bg-danger bg-warning').addClass('bg-primary');
        toastr.info('إعادة محاولة متابعة التحليل...');
        this.startProgressPolling();
    },

    /*
    |--------------------------------------------------------------------------
    | UI Updates - تحديث واجهة المستخدم
    |--------------------------------------------------------------------------
    */
    showAnalysisProgress() {
        // إنشاء عنصر التقدم إذا لم يكن موجوداً
        if (this.elements.progressContainer.length === 0) {
            const progressHtml = `
                <div id="analysis-progress" class="mt-4" style="display: none;">
                    <div class="d-flex align-items-center mb-3">
                        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                        <span>جاري تحليل الملف...</span>
                    </div>
                    <div class="progress">
                        <div class="analysis-progress-bar progress-bar progress-bar-striped progress-bar-animated" 
                             role="progressbar" style="width: 0%">0% - جاري التحضير...</div>
                    </div>
                </div>
            `;

            $('#analysis-results').before(progressHtml);
            this.elements.progressContainer = $('#analysis-progress');
            this.elements.progressBar = $('.analysis-progress-bar');
        }

        this.elements.progressContainer.show();
        this.elements.resultsContainer.hide();
    },

    hideAnalysisProgress() {
        this.elements.progressContainer.hide();
    },

    updateProgressUI(data) {
        const percentage = Math.max(0, Math.min(100, data.progress_percentage || 0));

        console.log('🔄 Updating analysis progress UI:', {
            percentage: percentage,
            current_step: data.current_step
        });

        // تحديث شريط التقدم
        this.elements.progressBar.css('width', percentage + '%');
        this.elements.progressBar.attr('aria-valuenow', percentage);
        this.elements.progressBar.removeClass('bg-warning bg-danger bg-success').addClass('bg-primary');

        // تحديث النص
        let progressText = `${Math.round(percentage)}%`;

        if (data.current_step) {
            const stepLabels = {
                'initializing': 'جاري التحضير',
                'reading_file': 'قراءة الملف',
                'loading_data': 'تحميل البيانات',
                'selecting_best_sheet': 'اختيار الورقة المناسبة',
                'analyzing_structure': 'تحليل البنية',
                'processing_headers': 'معالجة العناوين',
                'analyzing_fields': 'تحليل الحقول',
                'analyzing_data_quality': 'تحليل جودة البيانات',
                'completed': 'اكتمل'
            };

            const stepLabel = stepLabels[data.current_step] || data.current_step;
            progressText += ` - ${stepLabel}`;
        }

        this.elements.progressBar.text(progressText);

        // تحديث التفاصيل
        this.updateProgressDetails(data);
    },

    updateProgressDetails(data) {
        this.lastUpdateTime = new Date();

        let detailsHtml = `
            <div class="mt-2 small text-muted text-center">
                آخر تحديث: ${this.lastUpdateTime.toLocaleTimeString('ar-SA')}
        `;

        if (data.connection_issues) {
            detailsHtml += ` | <span class="text-warning">مشاكل اتصال (${data.retry_count}/${this.config.maxRetries})</span>`;
        }

        detailsHtml += '</div>';

        this.elements.progressContainer.find('.analysis-details').remove();
        this.elements.progressContainer.find('.progress').after('<div class="analysis-details">' + detailsHtml + '</div>');
    },

    disableAnalyzeButton() {
        this.elements.analyzeButton.prop('disabled', true)
            .html('<i class="spinner-border spinner-border-sm me-1"></i>جاري التحليل...');
    },

    enableAnalyzeButton() {
        this.elements.analyzeButton.prop('disabled', false)
            .html('<i class="ti ti-search me-1"></i>تحليل الملف');
    },

    addCancelButton() {
        if ($('#btn-cancel-analysis').length === 0) {
            const cancelButton = `
                <button type="button" class="btn btn-outline-danger btn-sm ms-2" id="btn-cancel-analysis" onclick="CustomerAnalysis.cancelAnalysis()">
                    <i class="ti ti-x me-1"></i>إلغاء التحليل
                </button>
            `;
            this.elements.progressContainer.append(cancelButton);
        }
    },

    /*
    |--------------------------------------------------------------------------
    | Results Display - عرض النتائج
    |--------------------------------------------------------------------------
    */
    showAnalysisResults(analysisData) {
        this.hideAnalysisProgress();
        this.enableAnalyzeButton();

        console.log('📋 Displaying analysis results:', analysisData);

        // حفظ البيانات للاستخدام في الاستيراد
        window.analysisData = analysisData;
        window.tempFilePath = null;

        // عرض النتائج باستخدام نفس المنطق الموجود
        this.displayAnalysisResults(analysisData);

        // إظهار زر متابعة الاستيراد
        this.showProceedButton();

        // انتقال للخطوة التالية - استخدام window للوصول للدوال
        if (typeof window.showStep === 'function') {
            window.showStep(3);
        }
        if (typeof window.updateStepIndicator === 'function') {
            window.updateStepIndicator(2, 'completed');
            window.updateStepIndicator(3, 'active');
        }

        toastr.success('تم تحليل الملف بنجاح');
    },

    
    displayAnalysisResults(data) {
        // نفس منطق العرض الموجود في الكود الأصلي
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

        // الحقول المعروفة
        if (data.header_analysis.recognized.length > 0) {
            fieldsHtml += this.buildRecognizedFieldsHTML(data.header_analysis.recognized);
        }

        // حقول الهواتف
        if (data.header_analysis.phone_fields && data.header_analysis.phone_fields.length > 0) {
            fieldsHtml += this.buildPhoneFieldsHTML(data.header_analysis.phone_fields);
        }

        // الحقول الجديدة
        if (data.header_analysis.new_fields.length > 0) {
            fieldsHtml += this.buildNewFieldsHTML(data.header_analysis.new_fields);
        }

        if (!fieldsHtml) {
            fieldsHtml = `
                <div class="text-center text-muted py-5">
                    <i class="ti ti-info-circle me-2" style="font-size: 2rem;"></i>
                    <h6 class="mt-2">لا توجد حقول للعرض</h6>
                    <small>تأكد من أن الملف يحتوي على بيانات صحيحة</small>
                </div>`;
        }

        $('#fields-analysis').html(fieldsHtml);
        this.elements.resultsContainer.show();
    },

    buildRecognizedFieldsHTML(recognizedFields) {
        let html = `
            <div class="mb-4">
                <h6 class="mb-3 text-success fw-bold">
                    <i class="ti ti-check-circle me-2"></i>الحقول المعروفة (${recognizedFields.length})
                </h6>
                <div class="row g-2">`;

        recognizedFields.forEach((field) => {
            const isStatic = field.type === 'static';
            const badgeClass = isStatic ? 'bg-primary' : 'bg-success';
            const iconClass = isStatic ? 'ti ti-database' : 'ti ti-folder';

            html += `
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center p-2 border rounded bg-light">
                        <i class="${iconClass} me-2 text-muted"></i>
                        <span class="text-truncate flex-grow-1" title="${field.name}">${field.name}</span>
                        <span class="badge ${badgeClass} ms-2 small">${isStatic ? 'أساسي' : 'موجود'}</span>
                    </div>
                </div>`;
        });

        html += '</div></div>';
        return html;
    },

    buildPhoneFieldsHTML(phoneFields) {
        let html = `
            <div class="mb-4">
                <h6 class="mb-3 text-info fw-bold">
                    <i class="ti ti-phone me-2"></i>حقول الهواتف الإضافية (${phoneFields.length})
                </h6>
                <div class="row g-2">`;

        phoneFields.forEach(field => {
            html += `
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center p-2 border rounded bg-light">
                        <i class="ti ti-phone me-2 text-muted"></i>
                        <span class="text-truncate flex-grow-1" title="${field.name}">${field.name}</span>
                        <span class="badge bg-info ms-2 small">هاتف</span>
                    </div>
                </div>`;
        });

        html += '</div></div>';
        return html;
    },

    buildNewFieldsHTML(newFields) {
        let html = `
            <div class="mb-4">
                <h6 class="mb-3 text-warning fw-bold">
                    <i class="ti ti-plus-circle me-2"></i>الحقول الجديدة (${newFields.length})
                </h6>
                <div class="row g-2">`;

        newFields.forEach(field => {
            html += `
                <div class="col-md-4 col-sm-6">
                    <div class="d-flex align-items-center p-2 border rounded" style="background: #fff3e0;">
                        <i class="ti ti-plus me-2 text-warning"></i>
                        <span class="text-truncate flex-grow-1" title="${field.name}">${field.name}</span>
                        <span class="badge bg-warning text-dark ms-2 small">جديد</span>
                    </div>
                </div>`;
        });

        html += `
                </div>
                <div class="mt-3 p-3 bg-light rounded border-start border-warning border-4">
                    <small class="text-muted fw-medium">
                        <i class="ti ti-info-circle me-1"></i>
                        سيتم إنشاء هذه الحقول تلقائياً في المحفظة عند بدء عملية الاستيراد
                    </small>
                </div>
            </div>`;

        return html;
    },

    showProceedButton() {
        const proceedHtml = `
            <div class="d-flex gap-2 justify-content-end mt-4">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="CustomerAnalysis.resetAnalysis()">
                    إعادة البدء
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-proceed-import">
                    بدء الاستيراد
                </button>
            </div>
        `;

        if ($('#btn-proceed-import').length === 0) {
            this.elements.resultsContainer.append(proceedHtml);
        }
    },

    showAnalysisError(data) {
        this.hideAnalysisProgress();
        this.enableAnalyzeButton();

        const errorHtml = `
            <div class="analysis-error text-center">
                <div class="mb-3">
                    <i class="ti ti-x-circle text-danger" style="font-size: 3rem;"></i>
                </div>
                <h5 class="text-danger mb-2">فشل في تحليل الملف</h5>
                <p class="mb-3 fw-medium">${data.error_message || 'حدث خطأ أثناء تحليل الملف'}</p>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="CustomerAnalysis.resetAnalysis()">
                        <i class="ti ti-refresh me-1"></i>إعادة المحاولة
                    </button>
                </div>
            </div>
        `;

        this.elements.resultsContainer.html(errorHtml).show();
        toastr.error('فشل في تحليل الملف');
    },

    showAnalysisCancelled() {
        this.hideAnalysisProgress();
        this.enableAnalyzeButton();

        const cancelledHtml = `
            <div class="analysis-cancelled text-center">
                <div class="mb-3">
                    <i class="ti ti-ban text-warning" style="font-size: 3rem;"></i>
                </div>
                <h5 class="text-warning mb-2">تم إلغاء التحليل</h5>
                <p class="mb-3 fw-medium">تم إلغاء عملية تحليل الملف بناءً على طلبك</p>
                <div class="d-flex gap-3 justify-content-center">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="CustomerAnalysis.resetAnalysis()">
                        <i class="ti ti-refresh me-1"></i>بدء تحليل جديد
                    </button>
                </div>
            </div>
        `;

        this.elements.resultsContainer.html(cancelledHtml).show();
        toastr.info('تم إلغاء عملية التحليل');
    },

    /*
    |--------------------------------------------------------------------------
    | Control Functions - دوال التحكم
    |--------------------------------------------------------------------------
    */
    cancelAnalysis() {
        if (!this.currentSessionId) {
            toastr.warning('لا توجد عملية تحليل جارية');
            return;
        }

        Swal.fire({
            title: 'تأكيد الإلغاء',
            text: 'هل أنت متأكد من إلغاء عملية تحليل الملف؟',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، إلغاء',
            cancelButtonText: 'استمرار',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                this.performCancelAnalysis();
            }
        });
    },

    performCancelAnalysis() {
        $.ajax({
            url: window.routes.cancelQueuedAnalysis,
            type: 'POST',
            data: {
                session_id: this.currentSessionId,
                _token: window.csrfToken
            },
            success: (response) => {
                if (response.success) {
                    this.stopProgressPolling();
                    this.showAnalysisCancelled();
                    toastr.success('تم إلغاء العملية بنجاح');
                } else {
                    toastr.error(response.message || 'فشل في إلغاء العملية');
                }
            },
            error: (xhr) => {
                toastr.error('حدث خطأ أثناء إلغاء العملية');
                console.error('Cancel analysis error:', xhr);
            }
        });
    },

    resetAnalysis() {
        this.stopProgressPolling();

        // إعادة تعيين المتغيرات
        this.currentSessionId = null;
        this.lastUpdateTime = null;
        this.consecutiveFailures = 0;
        this.progressStartTime = null;

        // تنظيف واجهة المستخدم
        this.hideAnalysisProgress();
        this.enableAnalyzeButton();
        this.elements.resultsContainer.hide().empty();

        // إزالة الأزرار الإضافية
        $('#btn-cancel-analysis, #btn-retry-analysis, #btn-proceed-import').remove();

        // إعادة تعيين البيانات العامة
        window.analysisData = null;
        window.tempFilePath = null;

        // إزالة الملف المحدد
        if (typeof removeSelectedFile === 'function') {
            removeSelectedFile();
        }

        toastr.info('تم إعادة تعيين التحليل');
        console.log('🔄 Analysis reset completed');
    },

    /*
    |--------------------------------------------------------------------------
    | Utility Functions - الدوال المساعدة
    |--------------------------------------------------------------------------
    */
    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    },

    formatDuration(seconds) {
        const minutes = Math.floor(seconds / 60);
        const remainingSeconds = Math.floor(seconds % 60);
        return minutes > 0 ? `${minutes}د ${remainingSeconds}ث` : `${remainingSeconds}ث`;
    },

    getCurrentStepLabel(step) {
        const stepLabels = {
            'initializing': 'جاري التحضير',
            'reading_file': 'قراءة الملف',
            'loading_data': 'تحميل البيانات',
            'selecting_best_sheet': 'اختيار الورقة المناسبة',
            'analyzing_structure': 'تحليل البنية',
            'processing_headers': 'معالجة العناوين',
            'analyzing_fields': 'تحليل الحقول',
            'analyzing_data_quality': 'تحليل جودة البيانات',
            'completed': 'اكتمل'
        };

        return stepLabels[step] || step;
    }
};

// تصدير الوحدة للاستخدام العام
window.CustomerAnalysis = CustomerAnalysis;

// التهيئة التلقائية عند تحميل الصفحة
$(document).ready(() => {
    CustomerAnalysis.init();
});