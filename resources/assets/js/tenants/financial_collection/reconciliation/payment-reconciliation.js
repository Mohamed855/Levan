'use strict';

document.addEventListener('DOMContentLoaded', function () {
    const ReconciliationManager = {
        /*
        |--------------------------------------------------------------------------
        | Elements
        |--------------------------------------------------------------------------
        */
        elements: {
            wizard: document.querySelector('#reconciliation-wizard'),
            companySelect: $('#company_id'),
            portfolioSelect: $('#portfolio_id'),
            reconciliationMonth: $('#reconciliation_month'),
            btnNext: $('.btn-next'),
            btnPrev: $('.btn-prev'),
            startProcessBtn: $('#start-process-btn'),
            tempFilePathInput: $('#temp_file_path'),
            fileUploadCard: $('#file-upload-card'),
            fieldMappings: $('#field-mappings'),
            progressSection: $('#progress-section'),
            resultsContainer: $('#results-container'),
            newOperationBtn: $('#new-operation-btn'),
            fileAnalysisProgress: $('#file-analysis-progress'),
            startSection: $('#start-section'),
            reportsSection: $('#reports-section'),
        },

        /*
        |--------------------------------------------------------------------------
        | State
        |--------------------------------------------------------------------------
        */
        state: {
            wizard: null,
            selectedFile: null,
            hiddenFileInput: null,
            fileHeaders: [],
            fieldMappings: {},
            jobId: null,
            progressInterval: null,
            fileUploaded: false,
            isAnalyzing: false,
            reconciliationId: null,
            isCheckingProgress: false,
        },

        /*
        |--------------------------------------------------------------------------
        | Initialize
        |--------------------------------------------------------------------------
        */
        init() {
            this.initWizard();
            this.initSelect2();
            this.bindEvents();
            this.validateStep1();
        },

        /*
        |--------------------------------------------------------------------------
        | Initialize Wizard
        |--------------------------------------------------------------------------
        */
        initWizard() {
            if (!this.elements.wizard) return;
            this.state.wizard = new Stepper(this.elements.wizard, { linear: true });
        },

        /*
        |--------------------------------------------------------------------------
        | Initialize Select2
        |--------------------------------------------------------------------------
        */
        initSelect2() {
            const config = { dir: 'rtl', allowClear: true };
            this.elements.companySelect.select2({ ...config, placeholder: 'اختر الشركة' });
            this.elements.portfolioSelect.select2({ ...config, placeholder: 'اختر المحفظة' });
        },

        /*
        |--------------------------------------------------------------------------
        | Bind Events
        |--------------------------------------------------------------------------
        */
        bindEvents() {
            this.elements.companySelect.on('change', () => this.handleCompanyChange());
            this.elements.portfolioSelect.on('change', () => this.handlePortfolioChange());
            this.elements.reconciliationMonth.on('change', () => this.validateStep1());
            this.elements.btnNext.on('click', () => this.handleNext());
            this.elements.btnPrev.on('click', () => this.handlePrev());
            this.elements.startProcessBtn.on('click', () => this.startReconciliation());
            this.elements.newOperationBtn.on('click', () => this.resetToStart());
        },

        /*
        |--------------------------------------------------------------------------
        | Handle Company Change
        |--------------------------------------------------------------------------
        */
        handleCompanyChange() {
            const companyId = this.elements.companySelect.val();
            this.resetPortfolioAndFields();
            if (companyId) {
                this.loadPortfolios(companyId);
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Load Portfolios
        |--------------------------------------------------------------------------
        */
        loadPortfolios(companyId) {
            this.elements.portfolioSelect.prop('disabled', true).empty().append('<option>جاري التحميل...</option>');

            $.ajax({
                url: window.reconciliationRoutes.getPortfolios,
                type: 'POST',
                data: {
                    company_id: companyId,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: (response) => {
                    this.elements.portfolioSelect.empty().append('<option value=""></option>');
                    if (response.success && response.portfolios?.length > 0) {
                        response.portfolios.forEach(portfolio => {
                            this.elements.portfolioSelect.append(new Option(portfolio.name, portfolio.id));
                        });
                        this.elements.portfolioSelect.prop('disabled', false);
                    } else {
                        toastr.info('لا توجد محافظ متاحة');
                    }
                },
                error: () => toastr.error('فشل في تحميل المحافظ')
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Handle Portfolio Change
        |--------------------------------------------------------------------------
        */
        handlePortfolioChange() {
            this.validateStep1();
            const portfolioId = this.elements.portfolioSelect.val();
            if (portfolioId) {
                this.elements.fileUploadCard.show();
                setTimeout(() => {
                    this.initFileUpload();
                }, 100);
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Initialize File Upload
        |--------------------------------------------------------------------------
        */
        initFileUpload() {
            this.resetFileUploadArea();
            this.createHiddenInput();
            this.bindFileUploadEvents();
        },

        /*
        |--------------------------------------------------------------------------
        | Reset File Upload Area
        |--------------------------------------------------------------------------
        */
        resetFileUploadArea() {
            const container = document.querySelector('#file-upload-dropzone');
            if (!container) return;

            container.innerHTML = `
                <div class="file-upload-area border-2 border-dashed rounded p-4 text-center" 
                     style="cursor: pointer; transition: all 0.3s ease; border-color: #dee2e6;">
                    <i class="ti ti-cloud-upload mb-3 upload-icon" style="font-size: 3rem; color: #6c757d;"></i>
                    <h5 class="mb-2 text-primary upload-title">اسحب الملف هنا أو انقر للاختيار</h5>
                    <p class="text-muted mb-0 upload-subtitle">ملفات Excel فقط (.xlsx, .xls)</p>
                </div>
            `;
        },

        /*
        |--------------------------------------------------------------------------
        | Create Hidden Input
        |--------------------------------------------------------------------------
        */
        createHiddenInput() {
            if (this.state.hiddenFileInput) {
                this.state.hiddenFileInput.remove();
            }

            this.state.hiddenFileInput = document.createElement('input');
            this.state.hiddenFileInput.type = 'file';
            this.state.hiddenFileInput.accept = '.xlsx,.xls';
            this.state.hiddenFileInput.style.display = 'none';
            document.body.appendChild(this.state.hiddenFileInput);

            this.state.hiddenFileInput.addEventListener('change', (e) => {
                const files = e.target.files;
                if (files && files.length > 0) {
                    this.validateAndHandleFile(files[0]);
                }
                e.target.value = '';
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Bind File Upload Events
        |--------------------------------------------------------------------------
        */
        bindFileUploadEvents() {
            const uploadArea = document.querySelector('#file-upload-dropzone .file-upload-area');
            if (!uploadArea) return;

            const newUploadArea = uploadArea.cloneNode(true);
            uploadArea.parentNode.replaceChild(newUploadArea, uploadArea);

            newUploadArea.addEventListener('click', (e) => {
                e.preventDefault();
                if (!this.state.isAnalyzing) {
                    this.triggerFileSelection();
                }
            });

            newUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                if (!this.state.isAnalyzing) {
                    newUploadArea.style.borderColor = '#007bff';
                    newUploadArea.style.backgroundColor = '#f8f9fa';
                }
            });

            newUploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                if (!newUploadArea.contains(e.relatedTarget)) {
                    newUploadArea.style.borderColor = '#dee2e6';
                    newUploadArea.style.backgroundColor = '';
                }
            });

            newUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                if (!this.state.isAnalyzing) {
                    newUploadArea.style.borderColor = '#dee2e6';
                    newUploadArea.style.backgroundColor = '';
                    const files = e.dataTransfer.files;
                    if (files && files.length > 0) {
                        this.validateAndHandleFile(files[0]);
                    }
                }
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Trigger File Selection
        |--------------------------------------------------------------------------
        */
        triggerFileSelection() {
            if (this.state.hiddenFileInput) {
                this.state.hiddenFileInput.click();
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Validate And Handle File
        |--------------------------------------------------------------------------
        */
        validateAndHandleFile(file) {
            if (this.state.isAnalyzing) {
                toastr.warning('يرجى انتظار انتهاء تحليل الملف الحالي');
                return;
            }

            const allowedTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'application/vnd.ms-excel'
            ];
            const allowedExtensions = ['.xlsx', '.xls'];
            const fileExtension = '.' + file.name.split('.').pop().toLowerCase();

            if (!allowedTypes.includes(file.type) && !allowedExtensions.includes(fileExtension)) {
                toastr.error('نوع الملف غير مدعوم. يرجى اختيار ملف Excel');
                return;
            }

            const maxSize = 50 * 1024 * 1024;
            if (file.size > maxSize) {
                toastr.error('حجم الملف كبير جداً. الحد الأقصى 50 ميجابايت');
                return;
            }

            this.handleFileSelected(file);
        },

        /*
        |--------------------------------------------------------------------------
        | Handle File Selected
        |--------------------------------------------------------------------------
        */
        handleFileSelected(file) {
            this.state.selectedFile = file;
            this.state.isAnalyzing = true;
            this.showFileProcessingState();
            this.analyzeFileHeaders(file);
        },

        /*
        |--------------------------------------------------------------------------
        | Show File Processing State
        |--------------------------------------------------------------------------
        */
        showFileProcessingState() {
            const container = document.querySelector('#file-upload-dropzone');
            if (container) {
                container.innerHTML = `
            <div class="d-flex align-items-center justify-content-center p-4">
                <div class="spinner-border spinner-border-sm me-3 text-primary" role="status">
                    <span class="visually-hidden">جاري التحميل...</span>
                </div>
                <div class="text-center">
                    <h6 class="mb-1 text-primary" id="file-processing-message">جاري تحليل الملف...</h6>
                    <small class="text-muted">يرجى الانتظار</small>
                </div>
            </div>
        `;
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Analyze File Headers
        |--------------------------------------------------------------------------
        */
        analyzeFileHeaders(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: window.reconciliationRoutes.analyzeHeaders,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    if (response.success && response.headers) {
                        this.state.fileHeaders = response.headers;

                        // ✅ تحديث الرسالة قبل رفع الملف
                        this.updateProcessingMessage('جاري رفع الملف...');

                        // الانتقال لرفع الملف
                        this.storeTemporaryFile(file);
                    } else {
                        this.state.isAnalyzing = false;
                        this.showAnalysisError('فشل في تحليل الملف');
                    }
                },
                error: (xhr) => {
                    this.state.isAnalyzing = false;
                    const errorMessage = xhr.responseJSON?.message || 'حدث خطأ أثناء تحليل الملف';
                    this.showAnalysisError(errorMessage);
                }
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Store Temporary File
        |--------------------------------------------------------------------------
        */
        storeTemporaryFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: window.reconciliationRoutes.storeTemporaryFile,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    // ✅ إيقاف حالة التحليل
                    this.state.isAnalyzing = false;

                    if (response.success) {
                        this.elements.tempFilePathInput.val(response.temp_file_path);
                        this.state.fileUploaded = true;

                        // ✅ عرض الملف بعد نجاح الرفع
                        this.updateFileDisplay(file.name, file.size);

                        this.validateStep1();
                        toastr.success('تم رفع الملف بنجاح');
                    }
                },
                error: (xhr) => {
                    // ✅ إيقاف حالة التحليل عند الخطأ
                    this.state.isAnalyzing = false;

                    const errorMessage = xhr.responseJSON?.message || 'فشل في رفع الملف';
                    toastr.error(errorMessage);
                    this.showAnalysisError(errorMessage);
                }
            });
        },


        /*
        |--------------------------------------------------------------------------
        | Update Processing Message
        |--------------------------------------------------------------------------
        */
        updateProcessingMessage(message) {
            const messageElement = document.getElementById('file-processing-message');
            if (messageElement) {
                messageElement.textContent = message;
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Update File Display
        |--------------------------------------------------------------------------
        */
        updateFileDisplay(fileName, fileSize) {
            const formattedSize = this.formatFileSize(fileSize);
            const container = document.querySelector('#file-upload-dropzone');
            if (container) {
                container.innerHTML = `
                    <div class="file-success-display p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center flex-grow-1">
                                <i class="ti ti-file-spreadsheet text-success me-3" style="font-size: 2.5rem;"></i>
                                <div class="file-details flex-grow-1">
                                    <h6 class="mb-1 text-success fw-bold">${this.escapeHtml(fileName)}</h6>
                                    <small class="text-muted">
                                        <i class="ti ti-file-size me-1"></i>
                                        ${formattedSize}
                                    </small>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="ReconciliationManager.removeFile()" title="إزالة الملف">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                `;
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Show Analysis Error
        |--------------------------------------------------------------------------
        */
        showAnalysisError(message) {
            toastr.error(message);
            this.resetFileUploadArea();
            this.bindFileUploadEvents();
            this.state.selectedFile = null;
            this.state.fileHeaders = [];
            this.state.fileUploaded = false;
            this.validateStep1();
        },

        /*
        |--------------------------------------------------------------------------
        | Remove File
        |--------------------------------------------------------------------------
        */
        removeFile() {
            if (confirm('هل أنت متأكد من إزالة الملف؟')) {
                this.state.selectedFile = null;
                this.state.fileHeaders = [];
                this.state.fieldMappings = {};
                this.state.fileUploaded = false;
                this.elements.tempFilePathInput.val('');
                this.resetFileUploadArea();
                this.bindFileUploadEvents();
                this.validateStep1();
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Handle Next
        |--------------------------------------------------------------------------
        */
        handleNext() {
            if (this.isStep1Valid() && this.state.fileUploaded) {
                this.state.wizard.next();
                setTimeout(() => {
                    this.generateFieldMappingUI();
                }, 300);
            } else if (!this.state.fileUploaded) {
                toastr.warning('يرجى رفع ملف Excel أولاً');
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Handle Previous
        |--------------------------------------------------------------------------
        */
        handlePrev() {
            this.state.wizard.previous();
            this.resetProcessStep();
        },

        /*
        |--------------------------------------------------------------------------
        | Generate Field Mapping UI
        |--------------------------------------------------------------------------
        */
        generateFieldMappingUI() {
            const requiredFields = [
                { key: 'account_number', label: 'رقم الحساب' },
                { key: 'collected_amount', label: 'المبلغ المحصل' },
                { key: 'collection_date', label: 'تاريخ التحصيل' }
            ];

            let html = '';
            requiredFields.forEach(field => {
                html += `
                    <div class="col-md-4 mb-3">
                        <label class="form-label fw-bold">${field.label} <span class="text-danger">*</span></label>
                        <select class="form-control select2" data-field="${field.key}" required>
                            <option value="">اختر العمود</option>
                            ${this.state.fileHeaders.map(header =>
                    `<option value="${this.escapeHtml(header)}">${this.escapeHtml(header)}</option>`
                ).join('')}
                        </select>
                    </div>
                `;
            });

            this.elements.fieldMappings.html(html);

            $('.select2', this.elements.fieldMappings).select2({
                dir: 'rtl',
                allowClear: true,
                placeholder: 'اختر العمود'
            });

            this.bindMappingEvents();
        },

        /*
        |--------------------------------------------------------------------------
        | Bind Mapping Events
        |--------------------------------------------------------------------------
        */
        bindMappingEvents() {
            $('select[data-field]', this.elements.fieldMappings).on('change', () => {
                this.validateMappingStep();
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Validate Mapping Step
        |--------------------------------------------------------------------------
        */
        validateMappingStep() {
            const requiredFields = ['account_number', 'collected_amount', 'collection_date'];
            let isValid = true;
            this.state.fieldMappings = {};

            requiredFields.forEach(field => {
                const select = $(`select[data-field="${field}"]`);
                const column = select.val();

                if (!column || column.trim() === '') {
                    isValid = false;
                    return;
                }

                this.state.fieldMappings[field] = { column: column.trim() };
            });

            this.elements.btnNext.prop('disabled', !isValid);
        },

        /*
        |--------------------------------------------------------------------------
        | Start Reconciliation
        |--------------------------------------------------------------------------
        */
        startReconciliation() {
            if (!this.validateFinalData()) {
                return;
            }

            const data = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                temp_file_path: this.elements.tempFilePathInput.val(),
                company_id: this.elements.companySelect.val(),
                portfolio_id: this.elements.portfolioSelect.val(),
                reconciliation_month: this.elements.reconciliationMonth.val(),
                field_mappings: this.state.fieldMappings
            };

            this.elements.startProcessBtn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>جاري البدء...');

            $.ajax({
                url: window.reconciliationRoutes.process,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.state.jobId = response.job_id;
                        this.showProgressSection();
                        this.startProgressMonitoring();
                        toastr.success('تم بدء عملية المطابقة');
                    }
                },
                error: (xhr) => {
                    toastr.error(xhr.responseJSON?.message || 'حدث خطأ أثناء بدء العملية');
                    this.resetStartButton();
                }
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Validate Final Data
        |--------------------------------------------------------------------------
        */
        validateFinalData() {
            if (!this.elements.portfolioSelect.val()) {
                toastr.error('يرجى اختيار المحفظة');
                return false;
            }

            if (!this.elements.reconciliationMonth.val()) {
                toastr.error('يرجى اختيار شهر المطابقة');
                return false;
            }

            return true;
        },

        /*
        |--------------------------------------------------------------------------
        | Show Progress Section
        |--------------------------------------------------------------------------
        */
        showProgressSection() {
            this.elements.startSection.hide();
            this.elements.progressSection.show();

            // ✅ إظهار رسالة البداية
            $('#progress-status-message').show();
            $('#progress-status-text').text('جاري بدء العملية...');

            this.elements.btnPrev.prop('disabled', true);
        },

        /*
        |--------------------------------------------------------------------------
        | Start Progress Monitoring
        |--------------------------------------------------------------------------
        */
        startProgressMonitoring() {
            this.checkProgress();
            this.state.progressInterval = setInterval(() => {
                this.checkProgress();
            }, 2000);
        },

        /*
        |--------------------------------------------------------------------------
        | Check Progress
        |--------------------------------------------------------------------------
        */
        checkProgress() {
            // ✅ منع الاستدعاء إذا كان هناك طلب جارٍ
            if (!this.state.jobId || this.state.isCheckingProgress) {
                return;
            }

            this.state.isCheckingProgress = true;

            $.ajax({
                url: window.reconciliationRoutes.getProgress,
                data: { job_id: this.state.jobId },
                success: (response) => {
                    if (response.success) {
                        this.updateProgressDisplay(response.progress);
                    }
                },
                error: () => {
                    // ✅ إيقاف عند الخطأ
                    if (this.state.progressInterval) {
                        clearInterval(this.state.progressInterval);
                        this.state.progressInterval = null;
                    }
                },
                complete: () => {
                    this.state.isCheckingProgress = false;
                }
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Update Progress Display
        |--------------------------------------------------------------------------
        */
        updateProgressDisplay(progress) {
            // حساب النسبة المئوية
            const percentage = progress.total > 0
                ? Math.min(100, Math.max(0, (progress.processed / progress.total) * 100))
                : 0;

            // تحديث Progress Bar
            const progressBar = $('#progress-bar');
            progressBar.css('width', `${percentage.toFixed(2)}%`);
            progressBar.attr('aria-valuenow', Math.round(percentage));

            let progressText = `${Math.round(percentage)}%`;
            if (progress.total > 0) {
                progressText += ` - ${progress.processed.toLocaleString('en-US')} من ${progress.total.toLocaleString('en-US')}`;
            }
            progressBar.text(progressText);

            // ✅ شرط الانتهاء - الأولوية للـ status
            const isCompleted =
                progress.status === 'completed' ||
                progress.status === 'failed' ||
                percentage >= 100;

            // ✅ تحديث الرسالة فقط إذا لم تنتهِ العملية
            if (!isCompleted && (progress.status === 'processing' || progress.status === 'queued')) {
                const statusText = progress.processed > 0
                    ? `جاري المعالجة... (${progress.processed.toLocaleString('en-US')} / ${progress.total.toLocaleString('en-US')})`
                    : 'جاري بدء المعالجة...';
                $('#progress-status-text').text(statusText);
            }

            // ✅ عند الانتهاء - إيقاف فوري
            if (isCompleted) {
                // ✅ إيقاف setInterval فوراً - الأولوية القصوى
                if (this.state.progressInterval) {
                    clearInterval(this.state.progressInterval);
                    this.state.progressInterval = null;
                }

                // ✅ إخفاء Spinner فوراً
                $('#progress-status-message').hide();

                // ✅ تفعيل زر الرجوع
                this.elements.btnPrev.prop('disabled', false);

                // ✅ عرض النتائج
                this.showResults(progress);
            }
        },

        /*
        |--------------------------------------------------------------------------
        | Show Results
        |--------------------------------------------------------------------------
        */
        showResults(progress) {
            // console.log('🎯 Showing results:', progress);

            let html = '';

            const isSuccess = progress.status === 'completed' ||
                (progress.total > 0 && progress.processed >= progress.total && progress.reconciliation_id);

            if (isSuccess) {
                this.state.reconciliationId = progress.reconciliation_id;

                // if (!this.state.reconciliationId) {
                //     console.error('❌ Reconciliation ID is missing!');
                // } else {
                //     console.log('✅ Reconciliation ID saved:', this.state.reconciliationId);
                // }

                html = `
            <div class="mb-4">
                <div class="d-flex align-items-center mb-3">
                    <i class="ti ti-check-circle me-2 fs-4 text-success"></i>
                    <h6 class="mb-0">تم إكمال عملية المطابقة بنجاح!</h6>
                </div>
            </div>

            <div class="row text-center g-3">
                <div class="col-md-3">
                    <div class="card border">
                        <div class="card-body p-3">
                            <div class="h4 mb-1 text-success">${(progress.matched || 0).toLocaleString('en-US')}</div>
                            <small>متطابق</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border">
                        <div class="card-body p-3">
                            <div class="h4 mb-1 text-warning">${(progress.unmatched || 0).toLocaleString('en-US')}</div>
                            <small>غير مسجل</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border">
                        <div class="card-body p-3">
                            <div class="h4 mb-1 text-info">${(progress.discrepancies || 0).toLocaleString('en-US')}</div>
                            <small>اختلافات</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border">
                        <div class="card-body p-3">
                            <div class="h4 mb-1 text-danger">${(progress.errors || 0).toLocaleString('en-US')}</div>
                            <small>أخطاء</small>
                        </div>
                    </div>
                </div>
            </div>
        `;

                // ✅ إعداد روابط التقارير
                if (this.state.reconciliationId) {
                    this.setupReportLinks();
                    this.elements.reportsSection.show();
                } else {
                    // console.error('❌ Cannot setup report links - Reconciliation ID is missing');
                }
            } else {
                // حالة الفشل
                html = `
            <div class="alert alert-danger">
                <i class="ti ti-x-circle me-2"></i>
                فشلت العملية: ${progress.error || 'حدث خطأ غير متوقع'}
            </div>
        `;
            }

            $('#results-content').html(html);
            this.elements.resultsContainer.show();
            this.elements.newOperationBtn.show();
        },

        /*
        |--------------------------------------------------------------------------
        | Setup Report Links
        |--------------------------------------------------------------------------
        */
        setupReportLinks() {
            const reconciliationId = this.state.reconciliationId;

            // if (!reconciliationId) {
            //     console.error('Reconciliation ID is missing');
            //     return;
            // }

            // ✅ استخدام Routes Names المعرفة في Blade - بدون أي URL يدوي
            const routes = window.reconciliationRoutes.reports;

            if (!routes) {
                // console.error('Report routes are not defined');
                return;
            }

            $('#report-summary').attr('href', routes.summary.replace(':id', reconciliationId));
            $('#report-unmatched').attr('href', routes.unmatched.replace(':id', reconciliationId));
            $('#report-discrepancies').attr('href', routes.discrepancies.replace(':id', reconciliationId));
            $('#report-system-only').attr('href', routes.systemOnly.replace(':id', reconciliationId));
            $('#report-employee-performance').attr('href', routes.employeePerformance.replace(':id', reconciliationId));
            $('#report-errors').attr('href', routes.errors.replace(':id', reconciliationId));
        },

        /*
        |--------------------------------------------------------------------------
        | Reset To Start
        |--------------------------------------------------------------------------
        */
        resetToStart() {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم إنهاء العملية الحالية والبدء بعملية جديدة',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#1D4F44',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم، ابدأ من جديد',
                cancelButtonText: 'إلغاء'
            }).then((result) => {
                if (result.isConfirmed) {
                    location.reload();
                }
            });
        },

        /*
        |--------------------------------------------------------------------------
        | Validate Step 1
        |--------------------------------------------------------------------------
        */
        validateStep1() {
            const isValid = this.elements.companySelect.val() &&
                this.elements.portfolioSelect.val() &&
                this.elements.reconciliationMonth.val() &&
                this.state.fileUploaded;

            this.elements.btnNext.prop('disabled', !isValid);
            return isValid;
        },

        /*
        |--------------------------------------------------------------------------
        | Is Step 1 Valid
        |--------------------------------------------------------------------------
        */
        isStep1Valid() {
            return this.validateStep1();
        },

        /*
        |--------------------------------------------------------------------------
        | Reset Portfolio And Fields
        |--------------------------------------------------------------------------
        */
        resetPortfolioAndFields() {
            this.elements.portfolioSelect.val(null).trigger('change').prop('disabled', true)
                .empty().append('<option value="">اختر المحفظة</option>');
            this.elements.fileUploadCard.hide();
            this.state.fileUploaded = false;
        },

        /*
        |--------------------------------------------------------------------------
        | Reset Process Step
        |--------------------------------------------------------------------------
        */
        resetProcessStep() {
            // ✅ إيقاف setInterval
            if (this.state.progressInterval) {
                clearInterval(this.state.progressInterval);
                this.state.progressInterval = null;
            }

            // ✅ إخفاء العناصر
            this.elements.progressSection.hide();
            this.elements.resultsContainer.hide();
            $('#progress-status-message').hide();
            this.elements.startSection.show();
            this.elements.reportsSection.hide();
            this.elements.newOperationBtn.hide();

            // ✅ تفعيل زر الرجوع
            this.elements.btnPrev.prop('disabled', false);

            // ✅ إعادة تعيين الحالة
            this.state.isCheckingProgress = false;
            this.state.jobId = null;
        },


        /*
        |--------------------------------------------------------------------------
        | Reset Start Button
        |--------------------------------------------------------------------------
        */
        resetStartButton() {
            this.elements.startProcessBtn
                .prop('disabled', false)
                .html('<i class="ti ti-play me-2"></i>بدء المطابقة');
        },

        /*
        |--------------------------------------------------------------------------
        | Format File Size
        |--------------------------------------------------------------------------
        */
        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        /*
        |--------------------------------------------------------------------------
        | Escape HTML
        |--------------------------------------------------------------------------
        */
        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    ReconciliationManager.init();
    window.ReconciliationManager = ReconciliationManager;
});