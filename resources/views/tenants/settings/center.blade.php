@extends('tenants.layouts.layoutMaster')

@section('title', 'مركز الإعدادات التفصيلية')

<!-- Vendor Styles -->
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/css/tenants/tab.css'])
@endsection

<!-- Vendor Scripts -->
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/js/tenants/settings/tab.js', 'resources/assets/js/tenants/settings/system-settings.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    <div class="row g-2">
        @canany(['حالات العميل'])
            <div class="col-lg-6">
                <div class="card h-100 border">
                    <div class="card-header bg-transparent border-0 pb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initial rounded" style="background-color: #372632;">
                                    <i class="ti ti-users text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1 fw-semibold">إعدادات العملاء</h5>
                                <small class="text-muted">إدارة البيانات المتعلقة بالعملاء</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2">
                            @can('حالات العميل')
                                <div class="col-12">
                                    <a href="{{ tenant_route_name('general-settings.settings-customer-status.index') }}"
                                        class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded"
                                                style="background-color: rgba(31, 42, 68, 0.1); color: #372632;">
                                                <i class="ti ti-user-check"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-body fw-medium">حالات العميل</h6>
                                            <small class="text-muted">إدارة حالات العملاء</small>
                                        </div>
                                        <i class="ti ti-chevron-left text-muted"></i>
                                    </a>
                                </div>
                            @endcan

                            {{-- تم اضافتها في الكود - يجيب تشغيل السيدر الخاص بها فقط  --}}
                            {{-- @can('نتائج الاتصال')
                                <div class="col-6">
                                    <a href="{{ tenant_route_name('general-settings.contact-results.index') }}"
                                        class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded"
                                                style="background-color: rgba(31, 42, 68, 0.1); color: #372632;">
                                                <i class="ti ti-phone-call"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-body fw-medium">نتائج الاتصال</h6>
                                            <small class="text-muted">إدارة نتائج المكالمات</small>
                                        </div>
                                        <i class="ti ti-chevron-left text-muted"></i>
                                    </a>
                                </div>
                            @endcan --}}
                        </div>
                    </div>
                </div>
            </div>
        @endcanany


        @canany(['طرق التحصيل', 'أنواع المديونية'])
            <div class="col-lg-6">
                <div class="card h-100 border">
                    <div class="card-header bg-transparent border-0 pb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initial rounded" style="background-color: #28B78D;">
                                    <i class="ti ti-currency-dollar text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1 fw-semibold">إعدادات التحصيل</h5>
                                <small class="text-muted">إدارة طرق التحصيل والديون</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2">
                            @can('طرق التحصيل')
                                <div class="col-6">
                                    <a href="{{ tenant_route_name('general-settings.settings-payment-methods.index') }}"
                                        class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded"
                                                style="background-color: rgba(40, 183, 141, 0.1); color: #28B78D;">
                                                <i class="ti ti-credit-card"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-body fw-medium">طرق التحصيل</h6>
                                            <small class="text-muted">إدارة وسائل الدفع</small>
                                        </div>
                                        <i class="ti ti-chevron-left text-muted"></i>
                                    </a>
                                </div>
                            @endcan

                            @can('أنواع المديونية')
                                <div class="col-6">
                                    <a href="{{ tenant_route_name('general-settings.settings-debt-types.index') }}"
                                        class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded"
                                                style="background-color: rgba(40, 183, 141, 0.1); color: #28B78D;">
                                                <i class="ti ti-file-invoice"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-body fw-medium">أنواع المديونية</h6>
                                            <small class="text-muted">تصنيف أنواع الديون</small>
                                        </div>
                                        <i class="ti ti-chevron-left text-muted"></i>
                                    </a>
                                </div>
                            @endcan

                        </div>
                    </div>
                </div>
            </div>
        @endcanany


        @canany(['قائمة الفروع', 'قائمة الدول'])
            <div class="col-lg-6">
                <div class="card h-100 border">
                    <div class="card-header bg-transparent border-0 pb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initial rounded" style="background-color: #FFA76C;">
                                    <i class="ti ti-map-pin text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1 fw-semibold">الإعدادات الجغرافية</h5>
                                <small class="text-muted">إدارة المواقع والفروع</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2">
                            @can('قائمة الفروع')
                                <div class="col-12">
                                    <a href="{{ tenant_route_name('branches.index') }}"
                                        class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded"
                                                style="background-color: rgba(255, 167, 108, 0.1); color: #FFA76C;">
                                                <i class="ti ti-building"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-body fw-medium">قائمة الفروع</h6>
                                            <small class="text-muted">إدارة فروع الشركة</small>
                                        </div>
                                        <i class="ti ti-chevron-left text-muted"></i>
                                    </a>
                                </div>
                            @endcan

                            @can('قائمة الدول')
                                <div class="col-12">
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
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endcanany

        @canany(['وسائل الاتصال بالعميل', 'نماذج الرسائل', 'أسباب طلب المستندات'])
            <div class="col-lg-6">
                <div class="card h-100 border">
                    <div class="card-header bg-transparent border-0 pb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initial rounded" style="background-color: #A8B0B8;">
                                    <i class="ti ti-message-circle text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1 fw-semibold">إعدادات التواصل</h5>
                                <small class="text-muted">إدارة الرسائل ووسائل الاتصال</small>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-0">
                        <div class="row g-2">
                            @can('نماذج الرسائل')
                                <div class="col-6">
                                    <a href="{{ tenant_route_name('general-settings.message-templates.index') }}"
                                        class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded"
                                                style="background-color: rgba(168, 176, 184, 0.1); color: #A8B0B8;">
                                                <i class="ti ti-template"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-body fw-medium">قوالب الرسائل</h6>
                                            <small class="text-muted">إدارة قوالب الرسائل</small>
                                        </div>
                                        <i class="ti ti-chevron-left text-muted"></i>
                                    </a>
                                </div>
                            @endcan

                            @can('وسائل الاتصال بالعميل')
                                <div class="col-6">
                                    <a href="{{ tenant_route_name('general-settings.settings-contact-methods.index') }}"
                                        class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded"
                                                style="background-color: rgba(168, 176, 184, 0.1); color: #A8B0B8;">
                                                <i class="ti ti-phone"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-body fw-medium">وسائل الاتصال</h6>
                                            <small class="text-muted">إدارة طرق التواصل</small>
                                        </div>
                                        <i class="ti ti-chevron-left text-muted"></i>
                                    </a>
                                </div>
                            @endcan

                            {{-- @can('أسباب طلب المستندات')
                                <div class="col-12">
                                    <a href="{{ tenant_route_name('general-settings.settings-document-request-reasons.index') }}"
                                        class="d-flex align-items-center p-3 border rounded text-decoration-none">
                                        <div class="avatar avatar-sm me-3">
                                            <span class="avatar-initial rounded"
                                                style="background-color: rgba(255, 87, 51, 0.1); color: #FF5733;">
                                                <i class="ti ti-file-description"></i>
                                            </span>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-0 text-body fw-medium">أسباب طلب المستندات</h6>
                                            <small class="text-muted">إدارة أسباب طلب المستندات</small>
                                        </div>
                                        <i class="ti ti-chevron-left text-muted"></i>
                                    </a>
                                </div>
                            @endcan --}}
                        </div>
                    </div>
                </div>
            </div>
        @endcanany

        @if (auth()->user()->hasRole('جديد لم يكتمل بعد'))
            <div class="col-lg-6">
                <div class="card h-100 border">
                    <div class="card-header bg-transparent border-0 pb-2">
                        <div class="d-flex align-items-center">
                            <div class="avatar avatar-md me-3">
                                <span class="avatar-initial rounded" style="background-color: #6F42C1;">
                                    <i class="ti ti-plug-connected text-white"></i>
                                </span>
                            </div>
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1 fw-semibold">التكامل والـ API</h5>
                                <small class="text-muted">ربط النظام بالأنظمة الخارجية</small>
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
                                            style="background-color: rgba(111, 66, 193, 0.1); color: #6F42C1;">
                                            <i class="ti ti-key"></i>
                                        </span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 text-body fw-medium">مفتاح API وسجل الاستخدام</h6>
                                        <small class="text-muted">توليد التوكن ومراقبة الاستعلامات الخارجية</small>
                                    </div>
                                    <i class="ti ti-chevron-left text-muted"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection
