@extends('tenants.layouts.layoutMaster')

@section('title', 'تحديث مجمع للعملاء')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/dropzone/dropzone.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/cdn/sweetalert2.js'])
    <script>
        window.bulkUpdateRoutes = {
            getPortfolios: '{{ tenant_route_name('customers.bulk-update.get-portfolios') }}',
            getUpdatableFields: '{{ tenant_route_name('customers.bulk-update.get-updatable-fields') }}',
            analyzeHeaders: '{{ tenant_route_name('customers.bulk-update.analyze-headers') }}',
            storeTemporaryFile: '{{ tenant_route_name('customers.bulk-update.store-temp-file') }}',
            process: '{{ tenant_route_name('customers.bulk-update.process') }}',
            processExclusion: '{{ tenant_route_name('customers.bulk-update.process-exclusion') }}',
            getProgress: '{{ tenant_route_name('customers.bulk-update.get-progress') }}',
            downloadTemplate: '{{ tenant_route_name('customers.bulk-update.download-template') }}',
            downloadExclusionTemplate: '{{ tenant_route_name('customers.bulk-update.download-exclusion-template') }}',
            downloadErrorReport: '{{ tenant_route_name('customers.bulk-update.download-error-report') }}'
        };
    </script>
    @vite(['resources/assets/js/tenants/business-center/customers/bulk-update/customer-bulk-update.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="bulk-update-wizard" class="bs-stepper mt-2">
                {{-- Stepper Header --}}
                <div class="bs-stepper-header">
                    <div class="step" data-target="#setup-step">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">إعداد البيانات</span>
                                <span class="bs-stepper-subtitle">اختيار نوع العملية والبيانات</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>
                    <div class="step" data-target="#process-step">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">التنفيذ</span>
                                <span class="bs-stepper-subtitle" id="step2-subtitle">ربط الحقول والمعالجة</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    <!-- STEP 1: SETUP -->
                    <div id="setup-step" class="content">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">البيانات الأساسية</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="setup-form" novalidate>
                                            <div class="row g-4">
                                                {{-- نوع العملية --}}
                                                <div class="col-12">
                                                    <div class="row g-4">
                                                        <div class="col-md-6">
                                                            <input type="radio" class="btn-check" name="operation_type"
                                                                id="update-type" value="update" checked>
                                                            <label
                                                                class="btn btn-outline-primary w-100 text-start py-2 px-3 fw-medium"
                                                                for="update-type">
                                                                <i class="ti ti-edit me-2"></i>
                                                                <span class="small">تحديث بيانات العملاء</span>
                                                            </label>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <input type="radio" class="btn-check" name="operation_type"
                                                                id="exclusion-type" value="exclusion">
                                                            <label
                                                                class="btn btn-outline-danger w-100 text-start py-2 px-3 fw-medium"
                                                                for="exclusion-type">
                                                                <i class="ti ti-user-minus me-2"></i>
                                                                <span class="small">استبعاد عملاء</span>
                                                            </label>
                                                        </div>
                                                    </div>
                                                </div>
                                                {{-- الحقول الأساسية --}}
                                                <div class="col-md-6">
                                                    <label class="form-label" for="company_id">الشركة <span
                                                            class="text-danger">*</span></label>
                                                    <select name="company_id" id="company_id" class="form-control select2">
                                                        <option value=""></option>
                                                        @foreach ($companies as $company)
                                                            <option value="{{ $company->id }}">{{ $company->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label" for="portfolio_id">المحفظة <span
                                                            class="text-danger">*</span></label>
                                                    <select name="portfolio_id" id="portfolio_id"
                                                        class="form-control select2" disabled>
                                                        <option value=""></option>
                                                    </select>
                                                </div>

                                                {{-- حقول التحديث --}}
                                                <div id="update-fields-container" class="col-12">
                                                    <div class="row g-4">
                                                        <div class="col-md-6">
                                                            <label class="form-label" for="match_key">مفتاح المطابقة <span
                                                                    class="text-danger">*</span></label>
                                                            <select name="match_key" id="match_key"
                                                                class="form-control select2">
                                                                <x-form.controls.enum-options :enum="\App\Enums\Tenants\Customers\CustomerMatchKey::class" />
                                                            </select>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <label class="form-label" for="update_fields">الحقول المراد
                                                                تحديثها <span class="text-danger">*</span></label>
                                                            <select name="update_fields[]" id="update_fields"
                                                                class="form-control select2" multiple disabled>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- حقول الاستبعاد --}}
                                                <div id="exclusion-fields-container" class="col-12" style="display: none;">
                                                    <div class="row g-4">
                                                        <div class="col-md-6">
                                                            <label class="form-label" for="exclusion_key">مفتاح المطابقة
                                                                <span class="text-danger">*</span></label>
                                                            <select name="exclusion_key" id="exclusion_key"
                                                                class="form-control select2">
                                                                <x-form.controls.enum-options :enum="\App\Enums\Tenants\Customers\CustomerMatchKey::class" />
                                                            </select>
                                                            <small class="text-muted d-block mt-2">
                                                                <i class="ti ti-info-circle me-1"></i>
                                                                سيتم البحث عن العملاء باستخدام هذا المفتاح لاستبعادهم
                                                            </small>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- File Upload Area -->
                                <div class="card" id="file-upload-card" style="display: none;">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">رفع ملف Excel</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="file-upload-dropzone"
                                            class="border border-2 border-dashed rounded p-4 text-center bg-light">
                                            <i class="ti ti-cloud-upload mb-3"
                                                style="font-size: 3rem; color: #6c757d;"></i>
                                            <h5 class="mb-2 text-primary">اسحب الملف هنا أو انقر للاختيار</h5>
                                            <p class="text-muted mb-0">ملفات Excel فقط (.xlsx, .xls)</p>
                                        </div>
                                        <input type="hidden" id="temp_file_path" name="temp_file_path">

                                        <!-- File Analysis Progress -->
                                        <div id="file-analysis-progress" style="display: none;" class="mt-4">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                                <span>جاري تحليل الملف...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Template Download -->
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">تحميل النموذج</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="text-center">
                                            <i class="ti ti-download mb-3" id="template-icon"
                                                style="font-size: 2rem; color: #4CAF50;"></i>
                                            <p class="text-muted mb-3" id="template-description">قم بتحميل نموذج Excel
                                                محضر
                                                مسبقاً بالحقول المحددة</p>
                                            <button id="download-template-btn" type="button"
                                                class="btn btn-primary btn-sm" disabled>
                                                <i class="ti ti-download me-2"></i>
                                                <span id="template-btn-text">تحميل النموذج</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 d-flex justify-content-end mt-4">
                            <button type="button" class="btn btn-primary btn-next" disabled>
                                <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                <i class="ti ti-arrow-right ti-xs"></i>
                            </button>
                        </div>
                    </div>

                    <!-- STEP 2: PROCESS -->
                    <div id="process-step" class="content">
                        <!-- Field Mapping (for update only) -->
                        <div id="field-mapping-container" style="display: none;">
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="ti ti-link me-2"></i>
                                        ربط الحقول
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div id="field-mappings" class="row g-4">
                                        <!-- سيتم ملؤها بواسطة JavaScript -->
                                    </div>
                                    <div class="d-flex justify-content-end mt-4">
                                        <button id="start-process-btn" type="button" class="btn btn-primary btn-sm"
                                            disabled>
                                            <i class="ti ti-play me-2"></i>
                                            <span id="process-btn-text">بدء التحديث</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Exclusion Confirmation (for exclusion only) -->
                        <div id="exclusion-confirmation-container" style="display: none;">
                            <div class="card border-danger mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0 text-white">
                                        <i class="ti ti-alert-triangle me-2"></i>
                                        تأكيد عملية الاستبعاد
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="alert alert-warning mb-4">
                                        <h6 class="alert-heading">
                                            <i class="ti ti-alert-triangle me-1"></i>
                                            تحذير هام:
                                        </h6>
                                        <hr>
                                        <p class="mb-2">سيتم تنفيذ الإجراءات التالية:</p>
                                        <ul class="mb-0">
                                            <li>حذف جميع العملاء الموجودين في الملف المرفوع</li>
                                            <li>الحذف سيكون من المحفظة المحددة فقط</li>
                                            <li>هذه العملية <strong>لا يمكن التراجع عنها</strong></li>
                                            {{-- <li>سيتم حفظ سجل بالعملية في Activity Log</li> --}}
                                        </ul>
                                    </div>

                                    <div class="d-flex justify-content-end gap-2 mt-4">
                                        {{-- <button type="button" class="btn btn-outline-secondary btn-sm btn-prev">
                                        <i class="ti ti-arrow-left me-2"></i>
                                        رجوع
                                    </button> --}}
                                        <button id="start-exclusion-btn" type="button" class="btn btn-danger btn-sm">
                                            <i class="ti ti-user-minus me-2"></i>
                                            تأكيد الاستبعاد
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Progress Section -->
                        <div id="progress-section" style="display: none;">
                            <!-- Status Message -->
                            <div id="progress-status-message" class="d-flex align-items-center mb-3"
                                style="display: none !important;">
                                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                                <span id="progress-status-text">جاري بدء العملية...</span>
                            </div>

                            <!-- Progress Bar with Frame -->
                            <div class="card mb-4">
                                <div class="card-body">
                                    <div class="position-relative">
                                        <div id="progress-overlay" class="position-absolute w-100 h-100"
                                            style="background: rgba(128, 128, 128, 0.1); z-index: 1; border-radius: 8px; display: none;">
                                        </div>
                                        <div class="progress"
                                            style="height: 35px; border-radius: 8px; position: relative; z-index: 2; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);">
                                            <div id="progress-bar"
                                                class="progress-bar bg-primary progress-bar-striped progress-bar-animated d-flex align-items-center justify-content-center"
                                                role="progressbar"
                                                style="width: 0%; font-size: 16px; font-weight: 600; color: #ffffff;"
                                                aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                                0%
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Results -->
                        <div id="results-container" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <div id="results-content">
                                        <!-- سيتم ملؤها بواسطة JavaScript -->
                                    </div>
                                    <div class="text-center mt-4">
                                        <a id="download-error-report-btn" href="#" class="btn btn-warning btn-sm"
                                            style="display: none;">
                                            <i class="ti ti-download me-2"></i>
                                            تحميل تقرير الأخطاء
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Navigation -->
                        <div class="d-flex justify-content-between mt-6">
                            <button type="button" class="btn btn-outline-secondary btn-prev btn-sm">
                                <i class="ti ti-arrow-left me-2"></i>
                                السابق
                            </button>
                            <button type="button" id="new-operation-btn" class="btn btn-primary btn-sm"
                                style="display: none;">
                                <i class="ti ti-refresh me-2"></i>
                                إنهاء والبدء بعملية جديدة
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
