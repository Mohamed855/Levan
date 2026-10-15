'use strict';
Dropzone.autoDiscover = false;

document.addEventListener('DOMContentLoaded', function () {
    const BulkUpdateManager = {
        elements: {
            wizard: document.querySelector('#bulk-update-wizard'),
            companySelect: $('#company_id'),
            portfolioSelect: $('#portfolio_id'),
            matchKeySelect: $('#match_key'),
            updateFieldsSelect: $('#update_fields'),
            exclusionKeySelect: $('#exclusion_key'),
            btnNext: $('.btn-next'),
            btnPrev: $('.btn-prev'),
            startProcessBtn: $('#start-process-btn'),
            startExclusionBtn: $('#start-exclusion-btn'),
            downloadTemplateBtn: $('#download-template-btn'),
            tempFilePathInput: $('#temp_file_path'),
            fileUploadCard: $('#file-upload-card'),
            fieldMappingContainer: $('#field-mapping-container'),
            exclusionConfirmationContainer: $('#exclusion-confirmation-container'),
            fieldMappings: $('#field-mappings'),
            progressSection: $('#progress-section'),
            resultsContainer: $('#results-container'),
            processSummaryCard: $('#process-summary-card'),
            newOperationBtn: $('#new-operation-btn'),
            fileAnalysisProgress: $('#file-analysis-progress'),
            updateFieldsContainer: $('#update-fields-container'),
            exclusionFieldsContainer: $('#exclusion-fields-container')
        },
        state: {
            wizard: null,
            selectedFile: null,
            hiddenFileInput: null,
            availableFields: {},
            fileHeaders: [],
            fieldMappings: {},
            jobId: null,
            progressInterval: null,
            fileUploaded: false,
            isAnalyzing: false,
            templateReady: false,
            operationType: 'update',
            isExclusion: false
        },

        init() {
            this.initWizard();
            this.initSelect2();
            this.bindEvents();
            this.bindOperationTypeEvents();
            this.validateStep1();
        },

        initWizard() {
            if (!this.elements.wizard) return;
            this.state.wizard = new Stepper(this.elements.wizard, { linear: true });
        },

        initSelect2() {
            const config = { dir: 'rtl', allowClear: true };
            this.elements.companySelect.select2({ ...config, placeholder: 'اختر الشركة' });
            this.elements.portfolioSelect.select2({ ...config, placeholder: 'اختر المحفظة' });
            this.elements.matchKeySelect.select2({ ...config, placeholder: 'اختر مفتاح المطابقة', minimumResultsForSearch: Infinity });
            this.elements.updateFieldsSelect.select2({ ...config, placeholder: 'اختر الحقول' });
            this.elements.exclusionKeySelect.select2({ ...config, placeholder: 'اختر مفتاح التعريف', minimumResultsForSearch: Infinity });
        },

        bindEvents() {
            this.elements.companySelect.on('change', () => this.handleCompanyChange());
            this.elements.portfolioSelect.on('change', () => this.handlePortfolioChange());
            this.elements.matchKeySelect.on('change', () => this.handleMatchKeyChange());
            this.elements.updateFieldsSelect.on('change', () => this.handleFieldsChange());
            this.elements.exclusionKeySelect.on('change', () => this.handleExclusionKeyChange());
            this.elements.btnNext.on('click', () => this.handleNext());
            this.elements.btnPrev.on('click', () => this.handlePrev());
            this.elements.startProcessBtn.on('click', () => this.startBulkUpdate());
            this.elements.startExclusionBtn.on('click', () => this.startBulkExclusion());
            this.elements.downloadTemplateBtn.on('click', () => this.downloadTemplate());
            this.elements.newOperationBtn.on('click', () => this.resetToStart());
        },

        bindOperationTypeEvents() {
            $('input[name="operation_type"]').on('change', (e) => {
                this.handleOperationTypeChange(e.target.value);
            });
        },

        handleOperationTypeChange(type) {
            this.state.operationType = type;
            
            if (type === 'update') {
                // إظهار حقول التحديث
                this.elements.updateFieldsContainer.show();
                this.elements.exclusionFieldsContainer.hide();
                
                // تحديث العناوين
                $('#step2-subtitle').text('ربط الحقول والمعالجة');
                $('#template-icon').css('color', '#4CAF50');
                $('#template-description').text('قم بتحميل نموذج Excel محضر مسبقاً بالحقول المحددة');
                $('#template-btn-text').text('تحميل النموذج');
                
            } else if (type === 'exclusion') {
                // إظهار حقول الاستبعاد
                this.elements.updateFieldsContainer.hide();
                this.elements.exclusionFieldsContainer.show();
                
                // تحديث العناوين
                $('#step2-subtitle').text('تأكيد ومعالجة الاستبعاد');
                $('#template-icon').css('color', '#DC3545');
                $('#template-description').text('قم بتحميل نموذج استبعاد العملاء');
                $('#template-btn-text').text('تحميل نموذج الاستبعاد');
            }
            
            // إعادة تعيين الحقول
            this.resetUpdateFields();
            this.elements.fileUploadCard.hide();
            this.state.fileUploaded = false;
            this.state.templateReady = false;
            this.performFileRemoval();
            this.validateStep1();
        },

        handleMatchKeyChange() {
            this.validateStep1();
            this.updateTemplateAvailability();
        },

        handleFieldsChange() {
            this.validateStep1();
            this.updateTemplateAvailability();
        },

        handleExclusionKeyChange() {
            this.validateStep1();
            this.updateTemplateAvailability();
        },

        updateTemplateAvailability() {
            const portfolioSelected = !!this.elements.portfolioSelect.val();
            
            if (this.state.operationType === 'exclusion') {
                const exclusionKeySelected = !!this.elements.exclusionKeySelect.val();
                this.state.templateReady = portfolioSelected && exclusionKeySelected;
            } else {
                const matchKeySelected = !!this.elements.matchKeySelect.val();
                const fieldsSelected = this.elements.updateFieldsSelect.val()?.length > 0;
                this.state.templateReady = portfolioSelected && matchKeySelected && fieldsSelected;
            }
            
            this.elements.downloadTemplateBtn.prop('disabled', !this.state.templateReady);
        },

        // ========== File Upload Functions ==========
        initFileUploadPreparation() {
            setTimeout(() => {
                this.initFileUpload();
            }, 200);
        },

        resetFileUploadArea() {
            const container = document.querySelector('#file-upload-dropzone');
            if (!container) return;

            container.innerHTML = `
                <div class="file-upload-area border-2 border-dashed rounded p-4 text-center" 
                     style="cursor: pointer; transition: all 0.3s ease; border-color: #dee2e6;">
                    <i class="ti ti-cloud-upload mb-3 upload-icon" style="font-size: 3rem; color: #6c757d; transition: color 0.3s ease;"></i>
                    <h5 class="mb-2 text-primary upload-title">اسحب الملف هنا أو انقر للاختيار</h5>
                    <p class="text-muted mb-0 upload-subtitle">ملفات Excel فقط (.xlsx, .xls) - الحد الأقصى 10 ميجابايت</p>
                </div>
            `;
        },

        createHiddenInput() {
            if (this.state.hiddenFileInput) {
                this.state.hiddenFileInput.remove();
            }

            this.state.hiddenFileInput = document.createElement('input');
            this.state.hiddenFileInput.type = 'file';
            this.state.hiddenFileInput.accept = '.xlsx,.xls';
            this.state.hiddenFileInput.style.display = 'none';
            this.state.hiddenFileInput.multiple = false;
            document.body.appendChild(this.state.hiddenFileInput);

            this.state.hiddenFileInput.addEventListener('change', (e) => {
                const files = e.target.files;
                if (files && files.length > 0) {
                    this.validateAndHandleFile(files[0]);
                }
                e.target.value = '';
            });
        },

        bindFileUploadEvents() {
            const uploadArea = document.querySelector('#file-upload-dropzone .file-upload-area');
            if (!uploadArea) return;

            const newUploadArea = uploadArea.cloneNode(true);
            uploadArea.parentNode.replaceChild(newUploadArea, uploadArea);

            newUploadArea.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!this.state.isAnalyzing) {
                    this.triggerFileSelection();
                }
            });

            newUploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!this.state.isAnalyzing) {
                    newUploadArea.style.borderColor = '#007bff';
                    newUploadArea.style.backgroundColor = '#f8f9fa';
                }
            });

            newUploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!newUploadArea.contains(e.relatedTarget)) {
                    newUploadArea.style.borderColor = '#dee2e6';
                    newUploadArea.style.backgroundColor = '';
                }
            });

            newUploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                if (!this.state.isAnalyzing) {
                    newUploadArea.style.borderColor = '#dee2e6';
                    newUploadArea.style.backgroundColor = '';
                    const files = e.dataTransfer.files;
                    if (files && files.length > 0) {
                        this.validateAndHandleFile(files[0]);
                    }
                }
            });

            newUploadArea.addEventListener('mouseenter', () => {
                if (!this.state.isAnalyzing) {
                    newUploadArea.style.borderColor = '#007bff';
                    newUploadArea.style.backgroundColor = '#f8f9fa';
                    const icon = newUploadArea.querySelector('.upload-icon');
                    if (icon) {
                        icon.style.color = '#007bff';
                        icon.style.transform = 'scale(1.1)';
                    }
                }
            });

            newUploadArea.addEventListener('mouseleave', () => {
                if (!this.state.isAnalyzing) {
                    newUploadArea.style.borderColor = '#dee2e6';
                    newUploadArea.style.backgroundColor = '';
                    const icon = newUploadArea.querySelector('.upload-icon');
                    if (icon) {
                        icon.style.color = '#6c757d';
                        icon.style.transform = 'scale(1)';
                    }
                }
            });
        },

        triggerFileSelection() {
            if (this.state.hiddenFileInput) {
                this.state.hiddenFileInput.click();
            } else {
                this.createHiddenInput();
                setTimeout(() => {
                    if (this.state.hiddenFileInput) {
                        this.state.hiddenFileInput.click();
                    }
                }, 100);
            }
        },

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
                toastr.error('نوع الملف غير مدعوم. يرجى اختيار ملف Excel (.xlsx أو .xls)');
                return;
            }

            const maxSize = 10 * 1024 * 1024;
            if (file.size > maxSize) {
                toastr.error('حجم الملف كبير جداً. الحد الأقصى المسموح 10 ميجابايت');
                return;
            }

            this.handleFileSelected(file);
        },

        initFileUpload() {
            const uploadContainer = document.querySelector('#file-upload-dropzone');
            if (!uploadContainer) return;

            this.resetFileUploadArea();
            this.createHiddenInput();
            this.bindFileUploadEvents();
        },

        // ========== Event Handlers ==========
        handleCompanyChange() {
            const companyId = this.elements.companySelect.val();
            this.resetPortfolioAndFields();
            if (companyId) {
                this.loadPortfolios(companyId);
            }
        },

        handlePortfolioChange() {
            const portfolioId = this.elements.portfolioSelect.val();
            this.resetUpdateFields();
            this.validateStep1();
            this.updateTemplateAvailability();
            if (portfolioId && this.state.operationType === 'update') {
                this.loadUpdatableFields(portfolioId);
            } else if (portfolioId && this.state.operationType === 'exclusion') {
                this.elements.fileUploadCard.show();
                setTimeout(() => {
                    this.initFileUploadPreparation();
                }, 100);
            }
        },

        handleNext() {
            if (this.isStep1Valid() && this.state.fileUploaded) {
                this.state.wizard.next();
                setTimeout(() => {
                    if (this.state.operationType === 'update') {
                        this.generateFieldMappingUI();
                        this.elements.fieldMappingContainer.show();
                        this.elements.exclusionConfirmationContainer.hide();
                    } else {
                        this.elements.fieldMappingContainer.hide();
                        this.elements.exclusionConfirmationContainer.show();
                    }
                }, 300);
            } else if (!this.state.fileUploaded) {
                toastr.warning('يرجى رفع ملف Excel أولاً');
            } else {
                toastr.warning('يرجى إكمال جميع الحقول المطلوبة');
            }
        },

        handlePrev() {
            this.state.wizard.previous();
            this.resetProcessStep();
            $('#download-error-report-btn').hide();
        },

        // ========== Data Loading Functions ==========
        loadPortfolios(companyId) {
            this.elements.portfolioSelect.prop('disabled', true).empty().append('<option>جاري التحميل...</option>');

            $.ajax({
                url: window.bulkUpdateRoutes.getPortfolios,
                data: { company_id: companyId },
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

        loadUpdatableFields(portfolioId) {
            this.elements.updateFieldsSelect.prop('disabled', true).empty().append('<option>جاري التحميل...</option>');

            $.ajax({
                url: window.bulkUpdateRoutes.getUpdatableFields,
                data: { portfolio_id: portfolioId },
                success: (response) => {
                    this.elements.updateFieldsSelect.empty();
                    if (response.success && response.fields) {
                        this.state.availableFields = response.fields;
                        this.populateFields(response.fields);
                        this.elements.updateFieldsSelect.prop('disabled', false);
                        this.elements.fileUploadCard.show();

                        setTimeout(() => {
                            this.initFileUploadPreparation();
                        }, 100);
                    }
                },
                error: () => toastr.error('فشل في تحميل الحقول')
            });
        },

        populateFields(fields) {
            if (fields.base_fields && Object.keys(fields.base_fields).length > 0) {
                const baseGroup = $('<optgroup label="الحقول الأساسية"></optgroup>');
                $.each(fields.base_fields, (key, value) => {
                    baseGroup.append(new Option(value, key));
                });
                this.elements.updateFieldsSelect.append(baseGroup);
            }

            if (fields.dynamic_fields && Object.keys(fields.dynamic_fields).length > 0) {
                const dynamicGroup = $('<optgroup label="الحقول الديناميكية"></optgroup>');
                $.each(fields.dynamic_fields, (key, value) => {
                    dynamicGroup.append(new Option(value, key));
                });
                this.elements.updateFieldsSelect.append(dynamicGroup);
            }
        },

        // ========== File Processing Functions ==========
        handleFileSelected(file) {
            if (this.state.isAnalyzing) {
                toastr.warning('يرجى انتظار انتهاء تحليل الملف الحالي');
                return;
            }

            this.state.selectedFile = file;
            this.state.isAnalyzing = true;
            this.showFileProcessingState();
            
            if (this.state.operationType === 'exclusion') {
                // للاستبعاد لا نحتاج تحليل headers
                this.storeTemporaryFile(file);
            } else {
                this.analyzeFileHeaders(file);
            }
        },

        showFileProcessingState() {
            this.elements.fileAnalysisProgress.hide();

            const container = document.querySelector('#file-upload-dropzone');
            if (container) {
                const message = this.state.operationType === 'exclusion' ? 
                    'جاري معالجة الملف...' : 
                    'جاري تحليل الملف...';
                    
                container.innerHTML = `
                    <div class="d-flex align-items-center justify-content-center p-4">
                        <div class="spinner-border spinner-border-sm me-3 text-primary" role="status"></div>
                        <div class="text-center">
                            <h6 class="mb-1 text-primary">${message}</h6>
                            <small class="text-muted">يرجى الانتظار</small>
                        </div>
                    </div>
                `;
            }
        },

        updateFileDisplay(fileName, fileSize) {
            const formattedSize = this.formatFileSize(fileSize);
            const container = document.querySelector('#file-upload-dropzone');
            if (container) {
                container.innerHTML = `
                    <div class="file-success-display p-3">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="file-icon-container me-3">
                                    <i class="ti ti-file-spreadsheet text-success" style="font-size: 2.5rem;"></i>
                                </div>
                                <div class="file-details flex-grow-1">
                                    <h6 class="mb-1 text-success fw-bold">${this.escapeHtml(fileName)}</h6>
                                    <div class="d-flex align-items-center">
                                        <small class="text-muted me-3">
                                            <i class="ti ti-file-size me-1"></i>
                                            ${formattedSize}
                                        </small>
                                        <span class="badge bg-success-subtle text-success">
                                            <i class="ti ti-check me-1"></i>
                                            تم الرفع بنجاح
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="BulkUpdateManager.removeFile()" title="إزالة الملف">
                                <i class="ti ti-x"></i>
                            </button>
                        </div>
                    </div>
                `;

                if (!document.head.querySelector('#file-display-styles')) {
                    const style = document.createElement('style');
                    style.id = 'file-display-styles';
                    style.textContent = `
                        .file-success-display {
                            border: 2px solid #28a745;
                            border-radius: 0.5rem;
                            background: linear-gradient(135deg, #f8fff9 0%, #e8f5e8 100%);
                            transition: all 0.3s ease;
                        }
                        .file-success-display:hover {
                            box-shadow: 0 0.25rem 0.75rem rgba(40, 167, 69, 0.15);
                        }
                    `;
                    document.head.appendChild(style);
                }
            }
        },

        analyzeFileHeaders(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: window.bulkUpdateRoutes.analyzeHeaders,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                timeout: 30000,
                success: (response) => {
                    this.state.isAnalyzing = false;
                    if (response.success && response.headers) {
                        this.state.fileHeaders = response.headers;
                        this.updateFileDisplay(file.name, file.size);
                        this.showAnalysisSuccess(response.headers.length);
                        this.storeTemporaryFile(file);
                    } else {
                        this.showAnalysisError('فشل في تحليل رؤوس الملف');
                    }
                },
                error: (xhr) => {
                    this.state.isAnalyzing = false;
                    const errorMessage = xhr.responseJSON?.message || 'حدث خطأ أثناء تحليل الملف';
                    this.showAnalysisError(errorMessage);
                }
            });
        },

        showAnalysisSuccess(headersCount) {
            // toastr.success(`تم تحليل الملف بنجاح - تم العثور على ${headersCount} عمود`);
        },

        showAnalysisError(message) {
            toastr.error(message);
            this.resetFileUploadArea();
            this.bindFileUploadEvents();
            this.state.selectedFile = null;
            this.state.fileHeaders = [];
            this.state.fileUploaded = false;
            this.validateStep1();
        },

        storeTemporaryFile(file) {
            const formData = new FormData();
            formData.append('file', file);
            formData.append('_token', $('meta[name="csrf-token"]').attr('content'));

            $.ajax({
                url: window.bulkUpdateRoutes.storeTemporaryFile,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: (response) => {
                    this.state.isAnalyzing = false;
                    if (response.success) {
                        this.elements.tempFilePathInput.val(response.temp_file_path);
                        this.state.fileUploaded = true;
                        
                        // إذا كانت عملية استبعاد، نحدث العرض هنا
                        if (this.state.operationType === 'exclusion') {
                            this.updateFileDisplay(file.name, file.size);
                        }
                        
                        this.validateStep1();
                    }
                },
                error: (xhr) => {
                    this.state.isAnalyzing = false;
                    const errorMessage = xhr.responseJSON?.message || 'فشل في رفع الملف';
                    toastr.error(errorMessage);
                    this.state.fileUploaded = false;
                    this.validateStep1();
                    this.showAnalysisError(errorMessage);
                }
            });
        },

        // ========== Field Mapping UI ==========
        generateFieldMappingUI() {
            const selectedFields = this.elements.updateFieldsSelect.val() || [];
            const matchKeyValue = this.elements.matchKeySelect.val();
            const matchKeyLabel = matchKeyValue === 'account_number' ? 'رقم الحساب' : 'رقم الهوية';

            let html = `
                <div class="col-md-6 mb-3">
                    <label class="form-label fw-bold">مفتاح المطابقة - ${matchKeyLabel}</label>
                    <select class="form-control select2" data-field="match_key" required>
                        <option value="">اختر العمود المقابل لـ ${matchKeyLabel}</option>
                        ${this.state.fileHeaders.map(header =>
                            `<option value="${this.escapeHtml(header)}">${this.escapeHtml(header)}</option>`
                        ).join('')}
                    </select>
                </div>
            `;

            selectedFields.forEach(fieldKey => {
                const fieldName = this.getFieldDisplayName(fieldKey);
                const isCustomerType = fieldKey === 'customer_type';

                html += `
                    <div class="col-md-6 mb-3">
                        <label class="form-label fw-bold">${fieldName}</label>
                        <select class="form-control select2" data-field="${fieldKey}" required>
                            <option value="">اختر العمود</option>
                            ${this.state.fileHeaders.map(header =>
                                `<option value="${this.escapeHtml(header)}">${this.escapeHtml(header)}</option>`
                            ).join('')}
                        </select>
                        ${isCustomerType ? this.generateCustomerTypeMapping() : ''}
                    </div>
                `;
            });

            this.elements.fieldMappings.html(html);
            this.elements.fieldMappingContainer.show();

            $('.select2', this.elements.fieldMappings).select2({
                dir: 'rtl',
                allowClear: true,
                placeholder: 'اختر العمود'
            });

            this.bindMappingEvents();
        },

        generateCustomerTypeMapping() {
            return `
                <div class="mt-2 p-2 bg-light rounded">
                    <small class="text-muted d-block mb-2">ربط القيم (اختياري):</small>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm" 
                                   data-type="individual" placeholder="قيمة الفرد">
                            <small class="text-muted">مثال: فردي، فرد</small>
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control form-control-sm" 
                                   data-type="company" placeholder="قيمة الشركة">
                            <small class="text-muted">مثال: شركة، مؤسسة</small>
                        </div>
                    </div>
                </div>
            `;
        },

        bindMappingEvents() {
            $('select[data-field]', this.elements.fieldMappings).on('change', () => {
                this.validateMappingStep();
            });
            $('input[data-type]', this.elements.fieldMappings).on('input', () => {
                this.validateMappingStep();
            });
        },

        validateMappingStep() {
            const requiredMappings = ['match_key'];
            const selectedFields = this.elements.updateFieldsSelect.val() || [];
            requiredMappings.push(...selectedFields);

            let isValid = true;
            this.state.fieldMappings = {};

            requiredMappings.forEach(field => {
                const select = $(`select[data-field="${field}"]`);
                const column = select.val();

                if (!column || column.trim() === '') {
                    isValid = false;
                    return;
                }

                if (!this.state.fileHeaders.includes(column)) {
                    isValid = false;
                    return;
                }

                this.state.fieldMappings[field] = { column: column.trim() };

                if (field === 'customer_type') {
                    const individualValue = $(`input[data-type="individual"]`).val();
                    const companyValue = $(`input[data-type="company"]`).val();
                    this.state.fieldMappings[field].type_mapping = {};
                    if (individualValue) {
                        this.state.fieldMappings[field].type_mapping[individualValue.toLowerCase().trim()] = 'individual';
                    }
                    if (companyValue) {
                        this.state.fieldMappings[field].type_mapping[companyValue.toLowerCase().trim()] = 'company';
                    }
                }
            });

            this.elements.startProcessBtn.prop('disabled', !isValid);
        },

        prepareSummary() {
            const portfolioName = this.elements.portfolioSelect.find('option:selected').text();
            const matchKeyLabel = this.elements.matchKeySelect.val() === 'account_number' ? 'رقم الحساب' : 'رقم الهوية';
            const selectedFields = this.elements.updateFieldsSelect.val() || [];

            $('#summary-portfolio').text(portfolioName);
            $('#summary-match-key').text(matchKeyLabel);
            $('#summary-fields-count').text(selectedFields.length);

            this.elements.processSummaryCard.show();
        },

        // ========== Bulk Update Process ==========
        startBulkUpdate() {
            if (!this.validateFinalData()) {
                return;
            }

            const data = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                temp_file_path: this.elements.tempFilePathInput.val(),
                portfolio_id: this.elements.portfolioSelect.val(),
                match_key: this.elements.matchKeySelect.val(),
                field_mappings: this.state.fieldMappings
            };

            this.elements.startProcessBtn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>جاري البدء...');

            $.ajax({
                url: window.bulkUpdateRoutes.process,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.state.jobId = response.job_id;
                        this.state.isExclusion = false;
                        this.showProgressSection();
                        this.startProgressMonitoring();
                        toastr.success('تم بدء عملية التحديث المجمع');
                    } else {
                        toastr.error(response.message || 'فشل في بدء العملية');
                        this.resetStartButton();
                    }
                },
                error: (xhr) => {
                    console.error('Ajax error:', xhr);
                    toastr.error(xhr.responseJSON?.message || 'حدث خطأ أثناء بدء العملية');
                    this.resetStartButton();
                }
            });
        },

        validateFinalData() {
            if (!this.elements.portfolioSelect.val()) {
                toastr.error('يرجى اختيار المحفظة');
                return false;
            }

            if (!this.elements.matchKeySelect.val()) {
                toastr.error('يرجى اختيار مفتاح المطابقة');
                return false;
            }

            if (!this.elements.tempFilePathInput.val()) {
                toastr.error('يرجى رفع ملف Excel');
                return false;
            }

            const requiredFields = ['match_key'];
            const selectedFields = this.elements.updateFieldsSelect.val() || [];
            requiredFields.push(...selectedFields);

            for (let field of requiredFields) {
                if (!this.state.fieldMappings[field] || !this.state.fieldMappings[field].column) {
                    toastr.error(`يرجى ربط الحقل: ${this.getFieldDisplayName(field)}`);
                    return false;
                }
            }

            return true;
        },

        // ========== Bulk Exclusion Process ==========
        startBulkExclusion() {
            Swal.fire({
                title: 'تأكيد الاستبعاد',
                text: 'هل أنت متأكد من رغبتك في استبعاد هؤلاء العملاء؟ هذا الإجراء لا يمكن التراجع عنه.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#DC3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'نعم، استبعد العملاء',
                cancelButtonText: 'إلغاء',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    this.processExclusion();
                }
            });
        },

        processExclusion() {
            const data = {
                _token: $('meta[name="csrf-token"]').attr('content'),
                temp_file_path: this.elements.tempFilePathInput.val(),
                portfolio_id: this.elements.portfolioSelect.val(),
                exclusion_key: this.elements.exclusionKeySelect.val()
            };

            this.elements.startExclusionBtn.prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm me-2"></span>جاري البدء...');

            $.ajax({
                url: window.bulkUpdateRoutes.processExclusion,
                type: 'POST',
                data: data,
                success: (response) => {
                    if (response.success) {
                        this.state.jobId = response.job_id;
                        this.state.isExclusion = true;
                        this.showProgressSection();
                        this.startProgressMonitoring();
                        toastr.success('تم بدء عملية الاستبعاد المجمع');
                    } else {
                        toastr.error(response.message || 'فشل في بدء العملية');
                        this.resetExclusionButton();
                    }
                },
                error: (xhr) => {
                    toastr.error(xhr.responseJSON?.message || 'حدث خطأ أثناء بدء العملية');
                    this.resetExclusionButton();
                }
            });
        },

        resetExclusionButton() {
            this.elements.startExclusionBtn
                .prop('disabled', false)
                .html('<i class="ti ti-user-minus me-2"></i>تأكيد الاستبعاد');
        },

        // ========== Progress Monitoring ==========
        showProgressSection() {
            this.elements.progressSection.show();
            $('#progress-status-message').show();
            
            const statusText = this.state.isExclusion ? 
                'جاري بدء عملية الاستبعاد...' : 
                'جاري بدء عملية التحديث...';
            $('#progress-status-text').text(statusText);
            
            $('#progress-overlay').show();

            if (this.state.isExclusion) {
                this.elements.startExclusionBtn.hide();
            } else {
                this.elements.startProcessBtn
                    .prop('disabled', true)
                    .html('<i class="ti ti-play me-2"></i>بدء التحديث');
            }

            this.elements.btnPrev.prop('disabled', true);
        },

        startProgressMonitoring() {
            this.checkProgress();
            this.state.progressInterval = setInterval(() => {
                this.checkProgress();
            }, 2000);
        },

        checkProgress() {
            if (!this.state.jobId) return;

            $.ajax({
                url: window.bulkUpdateRoutes.getProgress,
                data: { job_id: this.state.jobId },
                success: (response) => {
                    if (response.success) {
                        this.updateProgressDisplay(response.progress);
                    }
                },
                error: () => {
                    console.error('Failed to check progress');
                    clearInterval(this.state.progressInterval);
                }
            });
        },

        updateProgressDisplay(progress) {
            const percentage = progress.total > 0
                ? Math.min(100, Math.max(0, (progress.processed / progress.total) * 100))
                : 0;

            const progressBar = $('#progress-bar');
            const processed = progress.processed || 0;
            const totalRows = progress.total || 0;

            if (processed > 0) {
                const statusText = this.state.isExclusion ? 
                    'جاري تنفيذ عملية الاستبعاد...' : 
                    'جاري تنفيذ عملية التحديث...';
                $('#progress-status-text').text(statusText);
                $('#progress-status-message .spinner-border').hide();
            }

            progressBar.css('width', `${percentage.toFixed(2)}%`);
            progressBar.attr('aria-valuenow', Math.round(percentage));

            let progressText = `${Math.round(percentage)}%`;
            if (totalRows > 0) {
                progressText += ` - ${processed.toLocaleString('en-US')} من ${totalRows.toLocaleString('en-US')}`;
            }
            progressBar.text(progressText);

            progressBar.removeClass('bg-success bg-danger bg-warning').addClass('bg-primary');

            if (progress.status === 'completed' || progress.status === 'failed') {
                clearInterval(this.state.progressInterval);

                $('#progress-status-message').hide();
                $('#progress-overlay').hide();

                this.elements.startProcessBtn.hide();
                this.elements.startExclusionBtn.hide();

                this.elements.btnPrev.prop('disabled', false);

                this.showResults(progress);
            }
        },

        showResults(progress) {
            let html = '';

            if (progress.status === 'completed') {
                const isExclusion = this.state.isExclusion || false;
                const successCount = isExclusion ? progress.excluded : progress.updated;
                const successLabel = isExclusion ? 'تم استبعاده' : 'تم تحديثه';
                const successRate = progress.total > 0 ? ((successCount / progress.total) * 100).toFixed(1) : 0;

                const hasActualErrors = (
                    progress.failed > 0 &&
                    progress.error_report_path &&
                    progress.error_report_path.trim() !== ''
                );

                const isFullSuccess = (
                    progress.total > 0 &&
                    successCount === progress.total &&
                    progress.failed === 0
                );

                let statusMessage = '';
                if (isFullSuccess) {
                    statusMessage = isExclusion ? 
                        'تم إكمال عملية الاستبعاد بنجاح بنسبة 100%!' : 
                        'تم إكمال عملية التحديث بنجاح بنسبة 100%!';
                } else if (hasActualErrors) {
                    statusMessage = 'تم إكمال العملية مع بعض الأخطاء';
                } else {
                    statusMessage = 'تم إكمال العملية بنجاح!';
                }

                html = `
                    <div class="mb-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ti ti-check-circle me-2 fs-4" style="color: #000000;"></i>
                            <h6 class="mb-0" style="color: #000000;">${statusMessage}</h6>
                        </div>
                    </div>

                    <div class="row text-center g-3">
                        <div class="col-md-3">
                            <div class="card border" style="background: #ffffff;">
                                <div class="card-body p-3">
                                    <div class="h4 mb-1" style="color: #000000;">${successCount.toLocaleString('en-US')}</div>
                                    <small style="color: #000000;">${successLabel}</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border" style="background: #ffffff;">
                                <div class="card-body p-3">
                                    <div class="h4 mb-1" style="color: #000000;">${progress.failed.toLocaleString('en-US')}</div>
                                    <small style="color: #000000;">فشل</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border" style="background: #ffffff;">
                                <div class="card-body p-3">
                                    <div class="h4 mb-1" style="color: #000000;">${progress.total.toLocaleString('en-US')}</div>
                                    <small style="color: #000000;">إجمالي البيانات</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card border" style="background: #ffffff;">
                                <div class="card-body p-3">
                                    <div class="h4 mb-1" style="color: #000000;">${successRate}%</div>
                                    <small style="color: #000000;">معدل النجاح</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    ${hasActualErrors ? '<div class="mt-3 text-center"><small style="color: #6c757d;"></small></div>' : ''}
                `;

                const errorButton = $('#download-error-report-btn');
                if (hasActualErrors && progress.error_report_path) {
                    errorButton.attr('href',
                        `${window.bulkUpdateRoutes.downloadErrorReport}?path=${encodeURIComponent(progress.error_report_path)}`
                    ).show();
                } else {
                    errorButton.hide();
                }

            } else {
                html = `
                    <div class="card border" style="background: #ffffff;">
                        <div class="card-body p-4">
                            <div class="d-flex align-items-center mb-2">
                                <i class="ti ti-x-circle me-2 fs-4" style="color: #000000;"></i>
                                <h6 class="mb-0" style="color: #000000;">فشلت العملية</h6>
                            </div>
                            <div style="color: #6c757d;">${progress.error || 'حدث خطأ غير متوقع أثناء المعالجة'}</div>
                            <div class="mt-2">
                                <small style="color: #6c757d;">يرجى التحقق من صحة البيانات والمحاولة مرة أخرى</small>
                            </div>
                        </div>
                    </div>
                `;
                $('#download-error-report-btn').hide();
            }

            $('#results-content').html(html);
            this.elements.resultsContainer.show();
            this.elements.newOperationBtn.show();
        },

        // ========== Template Download ==========
        downloadTemplate() {
            if (!this.state.templateReady) {
                toastr.warning('يرجى إكمال اختيار البيانات المطلوبة');
                return;
            }

            let formData, url;
            
            if (this.state.operationType === 'exclusion') {
                formData = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    exclusion_key: this.elements.exclusionKeySelect.val()
                };
                url = window.bulkUpdateRoutes.downloadExclusionTemplate;
            } else {
                formData = {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    portfolio_id: this.elements.portfolioSelect.val(),
                    match_key: this.elements.matchKeySelect.val(),
                    update_fields: this.elements.updateFieldsSelect.val()
                };
                url = window.bulkUpdateRoutes.downloadTemplate;
            }

            const form = $('<form method="POST" action="' + url + '"></form>');
            $.each(formData, (key, value) => {
                if (Array.isArray(value)) {
                    value.forEach(v => form.append($('<input type="hidden" name="' + key + '[]">').val(v)));
                } else {
                    form.append($('<input type="hidden" name="' + key + '">').val(value));
                }
            });

            $('body').append(form);
            form.submit();
            form.remove();

            toastr.info('جاري تحميل النموذج...');
        },

        // ========== File Removal ==========
        removeFile() {
            if (confirm('هل أنت متأكد من إزالة الملف؟')) {
                this.performFileRemoval();
            }
        },

        performFileRemoval() {
            this.state.selectedFile = null;
            this.state.fileHeaders = [];
            this.state.fieldMappings = {};
            this.state.fileUploaded = false;
            this.elements.fieldMappingContainer.hide();
            this.elements.tempFilePathInput.val('');
            this.elements.startProcessBtn.prop('disabled', true);
            this.resetFileUploadArea();
            this.bindFileUploadEvents();
            this.validateStep1();
            // toastr.info('تم إزالة الملف بنجاح');
        },

        resetStartButton() {
            this.elements.startProcessBtn
                .prop('disabled', false)
                .html('<i class="ti ti-play me-2"></i>بدء التحديث')
                .show();
        },

        // ========== Reset Functions ==========
        resetToStart() {
            Swal.fire({
                title: 'هل أنت متأكد؟',
                text: 'سيتم إنهاء العملية الحالية والبدء بعملية جديدة',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#4cc1bb',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم، ابدأ من جديد',
                cancelButtonText: 'إلغاء',
                reverseButtons: false  
            }).then((result) => {
                if (result.isConfirmed) {
                    $('#download-error-report-btn').hide();
                    location.reload();
                }
            });
        },

        resetProcessStep() {
            this.elements.fieldMappingContainer.hide();
            this.elements.exclusionConfirmationContainer.hide();
            this.elements.progressSection.hide();
            this.elements.resultsContainer.hide();
            this.elements.processSummaryCard.hide();
            this.elements.tempFilePathInput.val('');
            this.elements.startProcessBtn.prop('disabled', true).html('<i class="ti ti-play me-2"></i>بدء التحديث').show();
            this.elements.startExclusionBtn.prop('disabled', false).html('<i class="ti ti-user-minus me-2"></i>تأكيد الاستبعاد').show();
            this.elements.newOperationBtn.hide();

            $('#download-error-report-btn').hide();
            $('#progress-status-message').hide();
            $('#progress-overlay').hide();

            this.elements.btnPrev.prop('disabled', false);

            this.state.selectedFile = null;
            this.state.fileHeaders = [];
            this.state.fieldMappings = {};
            this.state.fileUploaded = false;
            this.state.isAnalyzing = false;

            if (this.state.progressInterval) {
                clearInterval(this.state.progressInterval);
                this.state.progressInterval = null;
            }

            this.resetFileUploadArea();
            this.bindFileUploadEvents();
        },

        resetPortfolioAndFields() {
            this.elements.portfolioSelect.val(null).trigger('change').prop('disabled', true)
                .empty().append('<option value="">اختر المحفظة</option>');
            this.resetUpdateFields();
            this.elements.fileUploadCard.hide();
            this.state.fileUploaded = false;
            this.state.templateReady = false;
        },

        resetUpdateFields() {
            this.elements.updateFieldsSelect.val(null).trigger('change').prop('disabled', true)
                .empty().append('<option value="">اختر الحقول</option>');
            this.updateTemplateAvailability();
        },

        // ========== Validation Functions ==========
        validateStep1() {
            let isValid = false;
            
            if (this.state.operationType === 'exclusion') {
                isValid = this.elements.companySelect.val() &&
                         this.elements.portfolioSelect.val() &&
                         this.elements.exclusionKeySelect.val() &&
                         this.state.fileUploaded;
            } else {
                isValid = this.elements.companySelect.val() &&
                         this.elements.portfolioSelect.val() &&
                         this.elements.matchKeySelect.val() &&
                         this.elements.updateFieldsSelect.val()?.length > 0 &&
                         this.state.fileUploaded;
            }

            this.elements.btnNext.prop('disabled', !isValid);
            return isValid;
        },

        isStep1Valid() {
            return this.validateStep1();
        },

        // ========== Utility Functions ==========
        getFieldDisplayName(fieldKey) {
            const allFields = { ...this.state.availableFields.base_fields, ...this.state.availableFields.dynamic_fields };
            return allFields[fieldKey] || fieldKey;
        },

        formatFileSize(bytes) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        },

        escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    };

    BulkUpdateManager.init();
    window.BulkUpdateManager = BulkUpdateManager;
});