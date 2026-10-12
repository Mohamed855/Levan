@extends('tenants.layouts.layoutMaster')
@section('title', 'استيراد التحصيلات')
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/dropzone/dropzone.scss'])
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/dropzone/dropzone.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/tenants/financial_collection/collection-import.css'])

@endsection
@section('page-script')
    @vite(['resources/assets/js/shared/cdn/sweetalert2.js'])

    <script>
        window.routes = {
            downloadTemplate: "{{ tenant_route_name('financial-collections.import.download-template') }}",
            uploadAndAnalyze: "{{ tenant_route_name('financial-collections.import.upload-analyze') }}",
            processImport: "{{ tenant_route_name('financial-collections.import.process') }}",
            getProgress: "{{ tenant_route_name('financial-collections.import.get-progress') }}",
            downloadErrorReport: "{{ tenant_route_name('financial-collections.import.download-error-report') }}",
            cancelImport: "{{ tenant_route_name('financial-collections.import.cancel') }}",
            collectionsIndex: "{{ tenant_route_name('financial-collections.admin-collections.index') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
        window.systemOptions = @json($systemOptions);
        window.currentUser = @json(auth('tenant')->user());
    </script>
    @vite(['resources/assets/js/tenants/financial_collection/import/import.js'])
@endsection
@section('content')
    <div class="card mb-4" id="step-2">
        <div class="card-header">
            <h5 class="mb-0">رفع الملف وتكوين الاستيراد</h5>
        </div>
        <div class="card-body">
            <div class="mb-4">
                <label class="form-label">ملف Excel للاستيراد <span class="text-danger">*</span></label>
                <div id="file-upload-area" class="dropzone-area">
                    <div id="import-dropzone" class="w-100 h-100">
                        <div class="dz-message">
                            <i class="ti ti-cloud-upload" style="font-size: 3rem;"></i>
                            <h5 class="mb-2">اسحب الملف هنا أو انقر للاختيار</h5>
                            <p class="text-muted mb-0">.xlsx, .xls فقط - الحد الأقصى 10MB</p>
                        </div>
                    </div>
                </div>
            </div>
            <div id="configuration-form" style="display: none;">
                <form id="import-form" novalidate>
                    <input type="hidden" id="temp_path" name="temp_path">
                    <!-- قسم ربط الحقول -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="form-label">حقل رقم حساب العميل <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="account_column" required
                                        data-placeholder="اختر حقل رقم حساب العميل">
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">حقل مبلغ السداد <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="amount_column" required
                                        data-placeholder="اختر حقل مبلغ السداد">
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">حقل تاريخ السداد <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="date_column" required
                                        data-placeholder="اختر حقل تاريخ السداد">
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">تنسيق التاريخ <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="date_format" required
                                        data-placeholder="اختر تنسيق التاريخ">
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- قسم إعدادات التحصيل -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <label class="form-label">طريقة التحصيل <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="payment_method_id" required
                                        data-placeholder="اختر طريقة التحصيل">
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">نوع التحصيل <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="collection_type" required
                                        data-placeholder="اختر نوع التحصيل">
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">نوع السداد <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="payment_type" required
                                        data-placeholder="اختر نوع السداد">
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">تخصيص للمستخدم (اختياري)</label>
                                    <select class="form-select select2" name="assigned_user_id"
                                        data-placeholder="اختر مستخدم">
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="text-end">
                        <button type="button" class="btn btn-secondary me-2" onclick="resetImport()">إعادة</button>
                        <button type="submit" class="btn btn-primary" disabled>
                            إستيراد
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="card mb-4" id="step-3" style="display: none;">
        <div class="card-header">
            <h5 class="mb-0">تنفيذ الاستيراد</h5>
        </div>
        <div class="card-body">
            <div id="import-progress" style="display: none;">
                <div class="d-flex align-items-center mb-3">
                    <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                    <span id="progress-message">جاري تنفيذ عملية الاستيراد...</span>
                </div>
                <div class="progress mb-3">
                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar"
                        style="width: 0%">
                        <span class="progress-text" id="progress-text">0%</span>
                    </div>
                </div>
                <div class="text-center">
                    <small class="text-muted" id="progress-details"></small>
                </div>
            </div>
            <div id="results-summary" style="display: none;"></div>
        </div>
    </div>
@endsection
