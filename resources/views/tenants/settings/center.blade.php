@extends('tenants.layouts.layoutMaster')

@section('title', 'مركز الإعدادات التفصيلية')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/css/tenants/tab.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/js/tenants/settings/tab.js', 'resources/assets/js/tenants/settings/system-settings.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    <div class="row g-4">
        <!-- طرق الدفع -->
        <div class="col-md-6">
            <div class="card h-100 border">
                <div class="card-header bg-transparent border-0 pb-2">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <span class="avatar-initial rounded" style="background-color: #28B78D;">
                                <i class="ti ti-credit-card text-white"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1 fw-semibold">طرق الدفع والتحصيل</h5>
                            <small class="text-muted">إدارة وسائل الدفع المعتمدة للمنظمة</small>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2">
                        <div class="col-12">
                            <a href="{{ tenant_route_name('general-settings.settings-payment-methods.index') }}"
                                class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initial rounded"
                                        style="background-color: rgba(40, 183, 141, 0.1); color: #28B78D;">
                                        <i class="ti ti-credit-card"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-body fw-medium">طرق الدفع</h6>
                                    <small class="text-muted">إدارة خيارات الدفع المقبولة</small>
                                </div>
                                <i class="ti ti-chevron-left text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الإعدادات الجغرافية والفروع -->
        <div class="col-md-6">
            <div class="card h-100 border">
                <div class="card-header bg-transparent border-0 pb-2">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <span class="avatar-initial rounded" style="background-color: #FFA76C;">
                                <i class="ti ti-map-pin text-white"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1 fw-semibold">المواقع والفروع</h5>
                            <small class="text-muted">إدارة الفروع وقوائم الدول</small>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="{{ tenant_route_name('branches.index') }}"
                                class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initial rounded"
                                        style="background-color: rgba(255, 167, 108, 0.1); color: #FFA76C;">
                                        <i class="ti ti-building"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-body fw-medium">الفروع</h6>
                                    <small class="text-muted">إدارة فروع الشركة</small>
                                </div>
                                <i class="ti ti-chevron-left text-muted"></i>
                            </a>
                        </div>

                        <div class="col-6">
                            <a href="{{ tenant_route_name('general-settings.settings-country.index') }}"
                                class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initial rounded"
                                        style="background-color: rgba(255, 167, 108, 0.1); color: #FFA76C;">
                                        <i class="ti ti-world"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-body fw-medium">قائمة الدول</h6>
                                    <small class="text-muted">إدارة الدول المدعومة</small>
                                </div>
                                <i class="ti ti-chevron-left text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- إعدادات التواصل والقوالب -->
        <div class="col-md-6">
            <div class="card h-100 border">
                <div class="card-header bg-transparent border-0 pb-2">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <span class="avatar-initial rounded" style="background-color: #6F42C1;">
                                <i class="ti ti-template text-white"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1 fw-semibold">قوالب الرسائل</h5>
                            <small class="text-muted">إدارة القوالب والنصوص التلقائية</small>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2">
                        <div class="col-12">
                            <a href="{{ tenant_route_name('general-settings.message-templates.index') }}"
                                class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initial rounded"
                                        style="background-color: rgba(111, 66, 193, 0.1); color: #6F42C1;">
                                        <i class="ti ti-template"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-body fw-medium">قوالب الرسائل (SMS & Email)</h6>
                                    <small class="text-muted">إدارة نصوص الإشعارات والرسائل</small>
                                </div>
                                <i class="ti ti-chevron-left text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- التكامل والـ API -->
        <div class="col-md-6">
            <div class="card h-100 border">
                <div class="card-header bg-transparent border-0 pb-2">
                    <div class="d-flex align-items-center">
                        <div class="avatar avatar-md me-3">
                            <span class="avatar-initial rounded" style="background-color: #007bff;">
                                <i class="ti ti-plug-connected text-white"></i>
                            </span>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1 fw-semibold">التكامل والـ API</h5>
                            <small class="text-muted">ربط النظام بالأنظمة والتطبيقات الخارجية</small>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-0">
                    <div class="row g-2">
                        <div class="col-12">
                            <a href="{{ tenant_route_name('api-token-settings.index') }}"
                                class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                <div class="avatar avatar-sm me-3">
                                    <span class="avatar-initial rounded"
                                        style="background-color: rgba(0, 123, 255, 0.1); color: #007bff;">
                                        <i class="ti ti-key"></i>
                                    </span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0 text-body fw-medium">مفتاح API وتوكن الاستخدام</h6>
                                    <small class="text-muted">توليد التوكن ومراقبة الاستعلامات الخارجية</small>
                                </div>
                                <i class="ti ti-chevron-left text-muted"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
