@php
    $configData = App\Helpers\Helpers::appClasses();
    $isFront = true;
    $hideHeaderFooter = true;
@endphp

@extends('tenants.layouts.layoutFront')

@section('title', 'عملية التحصيل اليومية - لوت LOT')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/jquery/jquery.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/tenants/financial_collection/collection-process/collection-process.css'])

@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/cdn/sweetalert2.js'])


    <script>
        // تعريف المسارات والبيانات الأساسية
        window.routes = {
            contactResultFields: "{{ tenant_route_name('collection-process.customers.follows.contact-result-fields', ['customer' => 'CUSTOMER_ID']) }}",
            storeFollow: "{{ tenant_route_name('collection-process.customers.follows.store', ['customer' => 'CUSTOMER_ID']) }}",
            getCustomerDebt: "{{ tenant_route_name('collection-process.customers.follows.get-customer-debt', ['customer' => 'CUSTOMER_ID']) }}",
            exit: "{{ tenant_route_name('collection-process.exit') }}",
            dashboard: "{{ tenant_route_name('dashboard') }}",
            addPhone: "{{ tenant_route_name('collection-process.customers.add-phone', ['customer' => 'CUSTOMER_ID']) }}",
            setPrimaryPhone: "{{ tenant_route_name('collection-process.customers.set-primary-phone', ['customer' => 'CUSTOMER_ID']) }}",
            whatsappPreview: "{{ tenant_route_name('collection-process.customers.whatsapp-preview', ['customer' => ':customerId']) }}",
            getRelatedAccounts: "{{ tenant_route_name('collection-process.customers.get-related-accounts', ['customer' => 'CUSTOMER_ID']) }}",
            search: "{{ tenant_route_name('collection-process.search-customer') }}", // ✅ مرة واحدة فقط
            index: "{{ tenant_route_name('collection-process.index') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
        window.currentCustomer = @json($currentCustomer);
        window.formData = @json($formData ?? null);
    </script>

    {{-- استيراد ملفات JavaScript المنظمة --}}
    @vite(['resources/assets/js/tenants/financial_collection/collection-process/collection-exit.js'])
    @vite(['resources/assets/js/tenants/financial_collection/collection-process/follow-form.js'])
    @vite(['resources/assets/js/tenants/financial_collection/collection-process/customer-phone.js'])
    @vite(['resources/assets/js/tenants/financial_collection/collection-process/whatsapp-share.js'])
    @vite(['resources/assets/js/tenants/financial_collection/collection-process/follow-accounts-modal.js'])
    @vite(['resources/assets/js/tenants/financial_collection/collection-process/copy-note.js'])
    @vite(['resources/assets/js/tenants/financial_collection/collection-process/search.js'])
@endsection

@section('content')
    <div class="d-flex flex-column min-vh-100" style="background-color: #f8f9fa;">
        <div class="flex-grow-1">
            <div class="container-xxl py-4">
                <!-- Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="d-flex align-items-center gap-3">
                                        <div class="avatar-wrapper">
                                            <div class="avatar avatar-sm">
                                                <span class="avatar-initial rounded bg-primary">
                                                    <i class="ti ti-wallet ti-sm"></i>
                                                </span>
                                            </div>
                                        </div>
                                        <div>
                                            <h5 class="mb-0 fw-semibold">عملية التحصيل اليومية</h5>
                                        </div>
                                    </div>
                                    <button type="button" id="exitCollectionBtn" class="btn btn-danger btn-sm">
                                        <i class="ti ti-logout me-1"></i>
                                        إيقاف التحصيل
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="row g-4">
                    <div class="col-lg-8">
                        <div class="card border-0 h-100">

                            <div class="card-header bg-white pb-0">
                                <div class="row align-items-center g-2">
                                    <!-- العنوان -->
                                    <div class="col-md-4">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="ti ti-user-circle ti-sm text-primary"></i>
                                            <h5 class="mb-0 fw-semibold">تفاصيل العميل
                                                @if ($currentCustomer && $remainingCustomersCount > 0)
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <small class="text-muted">
                                                                متبقي لديك
                                                                {{ number_format($remainingCustomersCount) }} عملاء
                                                            </small>
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="mb-3">
                                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                                            <small class="text-muted">
                                                                ليس لديك عملاء للمتابعة حاليا
                                                            </small>
                                                        </div>
                                                    </div>
                                                @endif
                                            </h5>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <form action="{{ tenant_route_name('collection-process.index') }}" method="GET"
                                            class="position-relative">
                                            <div class="input-group input-group-merge">
                                                <input type="text" name="search"
                                                    class="form-control border-start-0 bg-light"
                                                    placeholder="ابحث برقم الهوية أو رقم الحساب أو باسم العميل"
                                                    id="customerSearch" autocomplete="off">
                                                <span class="input-group-text bg-transparent border-start-0">
                                                    <i class="ti ti-search text-muted"></i>
                                                </span>
                                            </div>
                                            <div id="searchResults" class="search-results-dropdown" style="display: none;">
                                            </div>
                                        </form>
                                    </div>

                                    <!-- الأزرار (اليمين) -->
                                    <div class="col-md-2 ms-auto">
                                        @if ($currentCustomer && $remainingCustomersCount > 0)
                                            <div class="btn-group d-flex justify-end" role="group">
                                                <a href="{{ tenant_route_name('collection-process.customers.prev', ['current_customer_id' => $currentCustomer->id]) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-chevron-right ti-xs"></i>
                                                    السابق
                                                </a>
                                                <a href="{{ tenant_route_name('collection-process.customers.next', ['current_customer_id' => $currentCustomer->id]) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    التالي
                                                    <i class="ti ti-chevron-left ti-xs"></i>
                                                </a>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="mb-2 border-dashed border-1 border-light"></div>

                            <div class="card-body p-0">
                                @if ($currentCustomer && isset($customerData))
                                    @include(
                                        'tenants.financial_collection.collection_process.partials.customer-details',
                                        [
                                            'customer' => $customerData['customer'],
                                            'customerData' => $customerData,
                                        ]
                                    )
                                @else
                                    <div class="text-center py-5">
                                        <i class="ti ti-user-off ti-xl text-muted mb-3"></i>
                                        <p class="text-muted mb-0">لا يوجد عملاء للمتابعة</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white pb-7">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="ti ti-file-plus ti-sm text-primary"></i>
                                    <h5 class="mb-0 fw-semibold">إضافة متابعة</h5>
                                </div>
                            </div>
                            <div class="mb-2 border-dashed border-1 border-light"></div>
                            <div class="card-body pt-0">
                                @if ($currentCustomer)
                                    @include(
                                        'tenants.financial_collection.collection_process.partials.follow-form',
                                        [
                                            'customer' => $currentCustomer,
                                            'formData' => $formData,
                                        ]
                                    )
                                @else
                                    <div class="text-center py-5">
                                        <i class="ti ti-file-off ti-xl text-muted mb-3"></i>
                                        <p class="text-muted mb-0">لا يوجد عميل محدد</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- تضمين المودالات --}}
                    @include('tenants.financial_collection.collection_process.partials.whatsapp')
                    @include('tenants.financial_collection.collection_process.partials.customer-phone')
                    @include('tenants.financial_collection.collection_process.partials.follow-accounts-modal')
                </div>
            </div>
        </div>

        @include('tenants.financial_collection.collection_process.partials.footer')
    </div>
@endsection
