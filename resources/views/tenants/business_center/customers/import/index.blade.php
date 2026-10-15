@extends('tenants.layouts.layoutMaster')

@section('title', 'استيراد العملاء')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/tenants/business-center/customers/customer-import.css', 'resources/assets/css/tenants/business-center/customers/help-modal.css'])

@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/cdn/sweetalert2.js'])

    <script>
        window.routes = {
            getPortfoliosByCompany: "{{ tenant_route_name('customers.import.portfolios-by-company') }}",
            downloadTemplate: "{{ tenant_route_name('customers.import.download-template') }}",
            previewTemplate: "{{ tenant_route_name('customers.import.preview-template') }}",
            uploadAndAnalyze: "{{ tenant_route_name('customers.import.upload-analyze') }}",
            processImport: "{{ tenant_route_name('customers.import.process') }}",
            checkImportStatus: "{{ tenant_route_name('customers.import.check-status') }}",
            cancelImport: "{{ tenant_route_name('customers.import.cancel') }}",
            downloadErrorReport: "{{ tenant_route_name('customers.import.download-error-report') }}",

            getMetadata: "{{ tenant_route_name('customers.import.metadata') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>

    <script></script>

    @vite(['resources/assets/js/tenants/business-center/customers/import/index.js', 'resources/assets/js/tenants/business-center/customers/import/help-modal.js'])
@endsection

@section('content')
    <!--  قسم المساعدة والمعرفات -->
    <button type="button" class="help-btn" data-bs-toggle="modal" data-bs-target="#importHelpModal" title="معلومات الاستيراد">
        <i class="ti ti-help"></i>
    </button>

    <!-- Step 1: Portfolio Selection -->
    <div class="card mb-4" id="step-1">
        <div class="py-5 d-flex justify-content-between card-header">
            <div class="d-flex align-items-center">
                <h6 class="mb-0">اختيار الشركة والمحفظة</h6>

            </div>
        </div>
        <div class="mb-2 border-dashed border-1 border-light"></div>

        <div class="card-body">
            <div class="row g-4">
                <div class="col-md-6 mb-3">
                    <label for="company_id" class="form-label">الشركة <span class="text-danger">*</span></label>
                    <select id="company_id" class="form-select select2" data-placeholder="اختر الشركة">
                        <option value=""></option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6 mb-3">
                    <label for="portfolio_id" class="form-label">المحفظة <span class="text-danger">*</span></label>
                    <select id="portfolio_id" class="form-select select2" data-placeholder="اختر المحفظة" disabled>
                        <option value=""></option>
                    </select>
                </div>
            </div>
            <div class="text-end">
                <button type="button" class="btn btn-outline-primary btn-sm" id="btn-preview-template" disabled>
                    معاينة للحقول
                </button>
                <button type="button" class="btn btn-primary btn-sm" id="btn-download-template" disabled>
                    تحميل القالب
                </button>
            </div>
        </div>
    </div>

    <!-- Step 2: File Upload -->
    <div class="card mb-4" id="step-2" style="display: none;">
        <div class="py-5 d-flex justify-content-between card-header">
            <div class="d-flex align-items-center">
                <h6 class="mb-0">رفع ملف البيانات</h6>

            </div>
        </div>
        <div class="mb-2 border-dashed border-1 border-light"></div>

        <div class="card-body">
            <div class="mb-3">
                <div class="alert alert-info"
                    style="background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); border: 2px solid #2196F3; border-radius: 0.75rem; margin-bottom: 25px;">
                    <div style="display: flex; gap: 15px; align-items: flex-start;">
                        <div style="font-size: 1.8rem; flex-shrink: 0;">📋</div>
                        <div style="flex: 1;">
                            <h6 style="color: #1565c0; margin: 0 0 0.5rem 0; font-weight: 700;">تعليمات مهمة:</h6>
                            <p style="color: #333; margin: 0 0 0.5rem 0; font-size: 14px; line-height: 1.6;">
                                <strong>1. حمّل النموذج أولاً:</strong> انقر على "تحميل القالب" و احفظ الملف الذي يحتوي على
                                أسماء جميع الأعمدة المطلوبة.
                            </p>
                            <p style="color: #333; margin: 0 0 0.5rem 0; font-size: 14px; line-height: 1.6;">
                                <strong>2. انسخ بيانات العملاء:</strong> افتح ملف بيانات العملاء الخاصة بك وانسخ البيانات
                                إلى الأعمدة المناسبة في النموذج.
                            </p>
                            <p style="color: #333; margin: 0 0 0.5rem 0; font-size: 14px; line-height: 1.6;">
                                <strong>3. حافظ على أسماء الأعمدة:</strong> <span
                                    style="color: #d32f2f; font-weight: 600;">لا تغيّر أسماء الأعمدة</span> - يجب أن تبقى
                                بنفس الأسماء تماماً.
                            </p>
                            <p style="color: #d32f2f; margin: 0; font-size: 13px; font-weight: 600;">
                                ⚠️ إذا غيّرت اسم العمود قد يفشل الاستيراد!
                            </p>
                        </div>
                    </div>
                </div>
                <div id="file-upload-area"
                    style="cursor: pointer; border: 2px dashed #e0e0e0; border-radius: 0.5rem; padding: 2.5rem; text-align: center; background: #fafafa;">
                    <div id="import-dropzone" class="w-100 h-100">
                        <div class="d-flex flex-column align-items-center">
                            <i class="ti ti-cloud-upload mb-3" style="font-size: 3rem; color: #6c757d;"></i>
                            <h5 class="mb-2 text-primary">اسحب الملف هنا أو انقر للاختيار</h5>
                            <p class="text-muted mb-0">ملفات Excel فقط (.xlsx, .xls) - الحد الأقصى 50 ميجا</p>
                        </div>
                    </div>
                </div>
                <input type="file" id="hidden-file-input" accept=".xlsx,.xls" style="display: none;">
            </div>

            <div class="text-end">
                <button type="button" class="btn btn-primary btn-sm" id="btn-analyze-file" disabled>
                    تحليل الملف
                </button>
            </div>

            <!-- Analysis Results -->
            <div id="analysis-results" style="display: none;" class="mt-4">
                <div class="border-0">
                    <div id="general-stats" class="fw-medium"></div>
                </div>

                <div class="row g-4">
                    <div class="col-lg-12">
                        <div class="card h-100 border-0 shadow-sm">
                            <div class="card-header bg-transparent border-bottom-0 pb-2">
                                <div class="d-flex align-items-center">
                                    <h6 class="mb-0">تحليل الحقول</h6>
                                </div>
                            </div>
                            <div class="card-body pt-2">
                                <div id="fields-analysis" style="line-height: 1.8;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex gap-2 justify-content-end mt-4">
                    <button type="button" class="btn btn-primary btn-sm" id="btn-proceed-import">
                        بدء الاستيراد
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Step 3: Import Execution -->
    <div class="card mb-4 shadow-sm" id="step-3" style="display: none;">
        <div class="py-4 d-flex justify-content-between align-items-center card-header">
            <div class="d-flex align-items-center">
                <h6 class="mb-0">تنفيذ الاستيراد</h6>
            </div>
        </div>
        <div class="mb-2 border-dashed border-1 border-light"></div>

        <div class="card-body">
            <!-- Progress Bar -->
            <div id="import-progress" style="display: none;">
                <div class="d-flex align-items-center justify-content-center mb-3">
                    <div class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></div>
                    <div>
                        <div class="fw-semibold">جاري تنفيذ عملية الاستيراد...</div>
                        <small class="text-muted">الرجاء الانتظار حتى اكتمال العملية</small>
                    </div>
                </div>
                <div class="progress" style="height: 18px; border-radius: 10px; overflow: hidden;">
                    <div id="import-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated"
                        role="progressbar" style="width: 0%">0%</div>
                </div>
            </div>

            <!-- Results Summary -->
            <div id="results-summary" style="display: none;" class="mt-3">
                <h6 class="mb-3">نتائج الاستيراد</h6>
                <div id="results-content" class="list-group">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">لا توجد نتائج بعد</span>
                        <span class="badge bg-secondary">0</span>
                    </div>
                </div>
                <div class="mt-3 text-end">
                    <a href="{{ tenant_route_name('customers.index') }}" class="btn btn-sm btn-primary ms-2">العودة
                        لقائمة العملاء</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Template Preview Modal -->
    <div class="modal fade" id="templatePreviewModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="py-5 d-flex justify-content-between modal-header">
                    <div class="d-flex align-items-center">
                        <h6 class="mb-0">معاينة الحقول</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>


                    </div>
                </div>
                <div class="mb-2 border-dashed border-1 border-light"></div>

                <div class="modal-body" id="template-preview-content"></div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">إغلاق</button>
                    <button type="button" class="btn btn-sm btn-primary" id="btn-download-template-modal">
                        تحميل القالب
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!--  موديل معلومات الاستيراد -->
    <!--  موديل معلومات الاستيراد الاحترافي -->
    <div class="modal fade" id="importHelpModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <!-- Header -->
                <div class="modal-header modal-header-gradient border-0">
                    <div>
                        <h5 class="mb-0"> معلومات الاستيراد المهمة</h5>
                        <small class="text-white-50 d-block mt-1">اقرأ بعناية قبل رفع الملف</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>

                <!-- Body -->
                <div class="modal-body pt-4">
                    <!--  تحذير بارز -->
                    <div class="alert-emphasis">
                        <div class="alert-emphasis-title">
                            <i class="ti ti-alert-triangle" style="font-size: 1.25rem;"></i>
                            تحذير مهم: استخدم الأرقام فقط وليس الأسماء!
                        </div>
                        <div class="alert-emphasis-content">
                            <p style="margin-bottom: 0.75rem;">
                                عند ملء حقول <strong>الجنسية</strong> و <strong>حالة العميل</strong> و <strong>مسؤول
                                    العلاقة</strong>،
                                يجب أن <strong style="color: #dc3545;">تكتب الرقم فقط</strong> وليس الاسم. كتابة الاسم
                                سيؤدي إلى خطأ في الاستيراد.
                            </p>
                        </div>
                    </div>

                    <!--  أمثلة التصحيح -->
                    <div class="example-box">
                        <div class="example-box-title"> ❌ مثال خاطئ (سيؤدي إلى فشل الاستيراد):</div>
                        <div class="example-wrong">
                            <i class="ti ti-x"></i>
                            الجنسية: "السعودية" (اسم)
                        </div>
                    </div>

                    <div class="example-box" style="border-left-color: #28a745; background: #f0fff4;">
                        <div class="example-box-title" style="color: #28a745;"> مثال صحيح (سينجح الاستيراد):</div>
                        <div class="example-correct">
                            <i class="ti ti-check"></i>
                            الجنسية: <span class="number-badge">1</span>
                        </div>
                    </div>

                    <!--  Tabs Navigation -->
                    <div class="tab-nav">
                        <button type="button" class="tab-btn active js-switch-tab-btn" data-tab="tab-customer-type">
                            نوع العميل
                        </button>
                        <button type="button" class="tab-btn js-switch-tab-btn" data-tab="tab-nationalities">
                            الجنسيات
                        </button>
                        <button type="button" class="tab-btn js-switch-tab-btn" data-tab="tab-statuses">
                            حالات العملاء
                        </button>
                        <button type="button" class="tab-btn js-switch-tab-btn" data-tab="tab-managers">
                            مسؤولو العلاقة
                        </button>
                    </div>

                    <!--  Customer Type Tab (جديد) -->
                    <div id="tab-customer-type" class="tab-content">
                        <div class="info-section">
                            <div class="info-title">
                                <i class="ti ti-info-circle"></i>
                                نوع العميل (Customer Type)
                            </div>
                            <ul class="info-list">
                                <li>اختر نوع العميل من القائمة أدناه</li>
                                <li>يمكنك كتابة أي من الكلمات المقبولة للنوع (على سبيل المثال: "فردي" أو "individual")</li>
                                <li>النوع غير حساس لحالة الأحرف (يمكن "فردي" أو "فرد")</li>
                                <li>إذا تركت الحقل فارغاً، سيتم جعله افتراضياً <span class="number-badge">"فرد"</span>
                                </li>
                            </ul>
                        </div>

                        <div class="example-box"
                            style="border-left-color: #2196F3; background: #e3f2fd; margin-top: 1.25rem;">
                            <div class="example-box-title" style="color: #1976D2;">💡 أمثلة مقبولة:</div>
                            <div class="example-correct" style="background: #e3f2fd; color: #1976D2; border-left: none;">
                                <i class="ti ti-check"></i>
                                "فردي" أو "فرد" أو "شخص" أو "individual"
                            </div>
                            <div class="example-correct" style="background: #e3f2fd; color: #1976D2; border-left: none;">
                                <i class="ti ti-check"></i>
                                "شركة" أو "مؤسسة" أو "company"
                            </div>
                        </div>

                    </div>

                    <!--  Nationalities Tab -->
                    <div id="tab-nationalities" class="tab-content" style="display: none;">
                        <div class="info-section">
                            <div class="info-title">
                                <i class="ti ti-info-circle"></i>
                                كيفية الاستخدام
                            </div>
                            <ul class="info-list">
                                <li>انسخ الرقم من العمود الأول (الرقم )</li>
                                <li>الصقه مباشرة في ملف Excel في عمود الجنسية</li>
                                <li>لا تكتب الاسم بأي حال من الأحوال</li>
                                <li>إذا تركت الحقل فارغاً، سيتم قبول ذلك (اختياري)</li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <strong class="d-block mb-3 text-muted">الجنسيات المتاحة:</strong>
                            <div class="metadata-list" id="nationalities-list">
                                <div class="empty-state">
                                    <div class="empty-state-icon">⏳</div>
                                    <p>جاري تحميل البيانات...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--  Customer Statuses Tab -->
                    <div id="tab-statuses" class="tab-content" style="display: none;">
                        <div class="info-section">
                            <div class="info-title">
                                <i class="ti ti-info-circle"></i>
                                كيفية الاستخدام
                            </div>
                            <ul class="info-list">
                                <li>انسخ رقم الحالة من العمود الأول (الرقم )</li>
                                <li>الصقه في عمود حالة العميل في الملف</li>
                                <li>لا تكتب كلمات مثل "نشط" أو "معلق"</li>
                                <li>إذا تركت الحقل فارغاً، سيتم قبول ذلك (اختياري)</li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <strong class="d-block mb-3 text-muted">حالات العملاء المتاحة:</strong>
                            <div class="metadata-list" id="statuses-list">
                                <div class="empty-state">
                                    <div class="empty-state-icon">⏳</div>
                                    <p>جاري تحميل البيانات...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!--  Managers Tab -->
                    <div id="tab-managers" class="tab-content" style="display: none;">
                        <div class="info-section">
                            <div class="info-title">
                                <i class="ti ti-info-circle"></i>
                                كيفية الاستخدام
                            </div>
                            <ul class="info-list">
                                <li>ابحث عن اسم المسؤول في القائمة أدناه</li>
                                <li>انسخ الرقم بجانب اسمه</li>
                                <li>الصقه في عمود مسؤول العلاقة في الملف</li>
                                <li>إذا تركت الحقل فارغاً، سيتم قبول ذلك (اختياري)</li>
                            </ul>
                        </div>
                        <div class="mt-4">
                            <strong class="d-block mb-3 text-muted">مسؤولو العلاقة المتاحين:</strong>
                            <div class="metadata-list" id="managers-list">
                                <div class="empty-state">
                                    <div class="empty-state-icon">⏳</div>
                                    <p>جاري تحميل البيانات...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>




@endsection
