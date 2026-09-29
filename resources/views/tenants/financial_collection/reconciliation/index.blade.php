@extends('tenants.layouts.layoutMaster')

@section('title', 'مطابقة السدادات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/cdn/sweetalert2.js'])
    <script>
        window.reconciliationRoutes = {
            getPortfolios: '{{ tenant_route_name('financial-collections.reconciliation.get-portfolios') }}',
            analyzeHeaders: '{{ tenant_route_name('financial-collections.reconciliation.analyze-headers') }}',
            storeTemporaryFile: '{{ tenant_route_name('financial-collections.reconciliation.store-temp-file') }}',
            process: '{{ tenant_route_name('financial-collections.reconciliation.process') }}',
            getProgress: '{{ tenant_route_name('financial-collections.reconciliation.get-progress') }}',

            // ✅ إضافة Routes التقارير
            reports: {
                summary: '{{ tenant_route_name('financial-collections.reconciliation.reports.summary', ['reconciliation' => ':id']) }}',
                unmatched: '{{ tenant_route_name('financial-collections.reconciliation.reports.unmatched', ['reconciliation' => ':id']) }}',
                discrepancies: '{{ tenant_route_name('financial-collections.reconciliation.reports.discrepancies', ['reconciliation' => ':id']) }}',
                systemOnly: '{{ tenant_route_name('financial-collections.reconciliation.reports.system-only', ['reconciliation' => ':id']) }}',
                employeePerformance: '{{ tenant_route_name('financial-collections.reconciliation.reports.employee-performance', ['reconciliation' => ':id']) }}',
                errors: '{{ tenant_route_name('financial-collections.reconciliation.reports.errors', ['reconciliation' => ':id']) }}'
            }
        };
    </script>
    @vite(['resources/assets/js/tenants/financial_collection/reconciliation/payment-reconciliation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="reconciliation-wizard" class="bs-stepper mt-2">
                {{-- ════════════════════════════════════════════════════════════ --}}
                {{-- Stepper Header --}}
                {{-- ════════════════════════════════════════════════════════════ --}}
                <div class="bs-stepper-header">
                    <div class="step" data-target="#setup-step">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">إعداد البيانات</span>
                                <span class="bs-stepper-subtitle">اختيار الشركة والمحفظة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>
                    <div class="step" data-target="#mapping-step">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">ربط الحقول</span>
                                <span class="bs-stepper-subtitle">مطابقة الأعمدة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>
                    <div class="step" data-target="#process-step">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">المعالجة</span>
                                <span class="bs-stepper-subtitle">التنفيذ والنتائج</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    {{-- ════════════════════════════════════════════════════════════ --}}
                    {{-- STEP 1: Setup --}}
                    {{-- ════════════════════════════════════════════════════════════ --}}
                    <div id="setup-step" class="content">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">البيانات الأساسية</h5>
                                    </div>
                                    <div class="card-body">
                                        <form id="setup-form" onsubmit="return false">
                                            <div class="row g-4">
                                                {{-- الشركة --}}
                                                <div class="col-md-6">
                                                    <label class="form-label" for="company_id">
                                                        الشركة <span class="text-danger">*</span>
                                                    </label>
                                                    <select name="company_id" id="company_id" class="form-control select2">
                                                        <option value=""></option>
                                                        @foreach ($companies as $company)
                                                            <option value="{{ $company->id }}">{{ $company->name }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                {{-- المحفظة --}}
                                                <div class="col-md-6">
                                                    <label class="form-label" for="portfolio_id">
                                                        المحفظة <span class="text-danger">*</span>
                                                    </label>
                                                    <select name="portfolio_id" id="portfolio_id"
                                                        class="form-control select2" disabled>
                                                        <option value=""></option>
                                                    </select>
                                                </div>

                                                {{-- شهر المطابقة --}}
                                                <div class="col-md-6">
                                                    <label class="form-label" for="reconciliation_month">
                                                        شهر المطابقة <span class="text-danger">*</span>
                                                    </label>
                                                    <input type="month" name="reconciliation_month"
                                                        id="reconciliation_month" class="form-control">
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                {{-- File Upload Area --}}
                                <div class="card" id="file-upload-card" style="display: none;">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">رفع ملف Excel</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="file-upload-dropzone"
                                            class="border border-2 border-dashed rounded p-4 text-center bg-light">
                                            <i class="ti ti-cloud-upload mb-3" style="font-size: 3rem; color: #6c757d;"></i>
                                            <h5 class="mb-2 text-primary">اسحب الملف هنا أو انقر للاختيار</h5>
                                            <p class="text-muted mb-0">ملفات Excel فقط (.xlsx, .xls) - الحد الأقصى 50
                                                ميجابايت</p>
                                        </div>
                                        <input type="hidden" id="temp_file_path" name="temp_file_path">

                                        {{-- File Analysis Progress --}}
                                        <div id="file-analysis-progress" style="display: none;" class="mt-4">
                                            <div class="d-flex align-items-center justify-content-center">
                                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                                    <span class="visually-hidden">جاري التحميل...</span>
                                                </div>
                                                <span id="analysis-status-text">جاري تحليل الملف...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- معلومات إضافية --}}
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="ti ti-info-circle me-2"></i>
                                            نظرة عامة
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <h6 class="text-muted mb-2">ماذا ستفعل هذه الأداة؟</h6>
                                            <ul class="small">
                                                <li>مطابقة السدادات من الملف مع النظام</li>
                                                <li>اكتشاف السدادات غير المسجلة</li>
                                                <li>إضافة السدادات الجديدة تلقائياً</li>
                                                <li>إنشاء تقارير مفصلة</li>
                                            </ul>
                                        </div>

                                        <div class="alert alert-warning">
                                            <i class="ti ti-alert-triangle me-1"></i>
                                            <small>تأكد من صحة البيانات في الملف قبل الرفع</small>
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

                    {{-- ════════════════════════════════════════════════════════════ --}}
                    {{-- STEP 2: Field Mapping --}}
                    {{-- ════════════════════════════════════════════════════════════ --}}
                    <div id="mapping-step" class="content">
                        <div class="mb-4">
                            <div class="card-body">
                                <div id="field-mappings" class="row g-3">
                                    {{-- سيتم ملؤها بواسطة JavaScript --}}
                                </div>
                            </div>
                        </div>

                        {{-- Navigation --}}
                        <div class="d-flex justify-content-between mt-4">
                            <button type="button" class="btn btn-outline-secondary btn-prev btn-sm">
                                <i class="ti ti-arrow-left me-2"></i>
                                السابق
                            </button>
                            <button type="button" class="btn btn-primary btn-next" disabled>
                                <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                <i class="ti ti-arrow-right ti-xs"></i>
                            </button>
                        </div>
                    </div>

                    {{-- ════════════════════════════════════════════════════════════ --}}
                    {{-- STEP 3: Process --}}
                    {{-- ════════════════════════════════════════════════════════════ --}}
                    <div id="process-step" class="content">
                        {{-- Start Button --}}
                        <div id="start-section" class="mb-4">
                            <div class="card-body text-center py-5">
                                <i class="ti ti-rocket mb-3" style="font-size: 4rem; color: #4CAF50;"></i>
                                <h5 class="mb-3">جاهز لبدء المطابقة</h5>
                                <p class="text-muted mb-4">سيتم معالجة الملف ومطابقة السدادات مع النظام</p>
                                <button id="start-process-btn" type="button" class="btn btn-primary btn-sm">
                                    بدء المطابقة
                                </button>
                            </div>
                        </div>

                        {{-- Progress Section --}}
                        <div id="progress-section" style="display: none;">
                            {{-- Status Message --}}
                            <div id="progress-status-message" class="d-flex align-items-center mb-3">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">جاري التحميل...</span>
                                </div>
                                <span id="progress-status-text" class="fw-medium">جاري بدء العملية...</span>
                            </div>

                            {{-- Progress Bar --}}
                            <div class="mb-4">
                                <div class="card-body">
                                    <div class="progress" style="height: 35px; border-radius: 8px;">
                                        <div id="progress-bar"
                                            class="progress-bar bg-primary progress-bar-striped progress-bar-animated d-flex align-items-center justify-content-center"
                                            role="progressbar" style="width: 0%; font-size: 16px; font-weight: 600;"
                                            aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">
                                            0%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Results --}}
                        <div id="results-container" style="display: none;">
                            <div class="card">
                                <div class="card-body">
                                    <div id="results-content">
                                        {{-- سيتم ملؤها بواسطة JavaScript --}}
                                    </div>

                                    {{-- التقارير --}}
                                    <div id="reports-section" class="mt-4" style="display: none;">
                                        <h6 class="mb-3">التقارير المتاحة:</h6>
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <a href="#" id="report-summary"
                                                    class="btn btn-outline-primary w-100">
                                                    <i class="ti ti-file-text me-2"></i>
                                                    التقرير الشامل
                                                </a>
                                            </div>
                                            <div class="col-md-4">
                                                <a href="#" id="report-unmatched"
                                                    class="btn btn-outline-warning w-100">
                                                    <i class="ti ti-alert-circle me-2"></i>
                                                    السدادات غير المسجلة
                                                </a>
                                            </div>
                                            <div class="col-md-4">
                                                <a href="#" id="report-discrepancies"
                                                    class="btn btn-outline-info w-100">
                                                    <i class="ti ti-info-circle me-2"></i>
                                                    الاختلافات
                                                </a>
                                            </div>
                                            <div class="col-md-4">
                                                <a href="#" id="report-system-only"
                                                    class="btn btn-outline-secondary w-100">
                                                    <i class="ti ti-database me-2"></i>
                                                    سدادات النظام فقط
                                                </a>
                                            </div>
                                            <div class="col-md-4">
                                                <a href="#" id="report-employee-performance"
                                                    class="btn btn-outline-success w-100">
                                                    <i class="ti ti-users me-2"></i>
                                                    أداء الموظفين
                                                </a>
                                            </div>
                                            <div class="col-md-4">
                                                <a href="#" id="report-errors"
                                                    class="btn btn-outline-danger w-100">
                                                    <i class="ti ti-x-circle me-2"></i>
                                                    تقرير الأخطاء
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Navigation --}}
                        <div class="d-flex justify-content-between mt-6">
                            <button type="button" class="btn btn-outline-secondary btn-prev btn-sm">
                                <i class="ti ti-arrow-left me-2"></i>
                                السابق
                            </button>
                            <button type="button" id="new-operation-btn" class="btn btn-primary btn-sm" style="display: none;">
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
