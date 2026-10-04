@extends('tenants.layouts.layoutMaster')

@section('title', 'تحصيلات المستخدمين')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            adminCollectionsIndex: "{{ tenant_route_name('financial-collections.admin-collections.index') }}",
            adminCollectionsShow: "{{ tenant_route_name('financial-collections.admin-collections.show', ':id') }}",
            adminCollectionsApprove: "{{ tenant_route_name('financial-collections.admin-collections.approve', ':id') }}",
            adminCollectionsReject: "{{ tenant_route_name('financial-collections.admin-collections.reject', ':id') }}",
            adminCollectionsApproveSelected: "{{ tenant_route_name('financial-collections.admin-collections.approve-selected') }}",
            adminCollectionsRejectSelected: "{{ tenant_route_name('financial-collections.admin-collections.reject-selected') }}",
            adminCollectionsGetAllIds: "{{ tenant_route_name('financial-collections.admin-collections.get-all-ids') }}",
            collectionsDeleteSelected: "{{ tenant_route_name('financial-collections.admin-collections.delete-selected') }}",
            adminCollectionsQuickStats: "{{ tenant_route_name('financial-collections.admin-collections.quick-stats') }}",
            collectionsImport: "{{ tenant_route_name('financial-collections.import.show') }}",
            searchCustomers: "{{ tenant_route_name('financial-collections.admin-collections.search-customers') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/financial_collection/admin/index.js', 'resources/assets/js/tenants/financial_collection/admin/selection.js'])
@endsection

@section('content')
    {{-- رسائل الأخطاء --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- إحصائيات التحصيلات --}}
    <div class="row g-4 mb-4">
        {{-- إجمالي التحصيلات --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي التحصيلات</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalCollections }}</h4>
                            </div>
                            <small class="mb-0">جميع التحصيلات</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-primary">
                                <i class="ti ti-receipt-2 ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- في انتظار الاعتماد --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">في انتظار الاعتماد</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $pendingCollections }}</h4>
                            </div>
                            <small class="mb-0">تحتاج مراجعة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-clock ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- معتمدة --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">معتمدة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $approvedCollections }}</h4>
                            </div>
                            <small class="mb-0">{{ $totalAmount }} <span class="icon-saudi_riyal"></span></small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-success">
                                <i class="ti ti-check ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- مرفوضة --}}
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">مرفوضة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $rejectedCollections }}</h4>
                            </div>
                            <small class="mb-0">تم رفضها</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-danger">
                                <i class="ti ti-x ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <x-data-table.filters table="#admin-collections-table" reset-id="btn-reset-filters">
        <div class="col-md-2">
            <label for="filter-status" class="form-label">الحالة</label>
            <select id="filter-status" class="form-control select2" data-datatable-filter="true" data-filter-key="status" data-placeholder="اختر الحالة">
                <option value=""></option>
                <option value="pending">غير معتمد</option>
                <option value="approved">معتمد</option>
                <option value="rejected">مرفوض</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filter-added-by" class="form-label">المستخدم</label>
            <select id="filter-added-by" class="form-control select2" data-datatable-filter="true" data-filter-key="added_by" data-placeholder="اختر المستخدم">
                <option value=""></option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label for="filter-collection-type" class="form-label">نوع التحصيل</label>
            <select id="filter-collection-type" class="form-control select2" data-datatable-filter="true" data-filter-key="collection_type" data-placeholder="اختر النوع">
                <option value=""></option>
                <option value="cash">نقدي</option>
                <option value="scheduled">مجدول</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filter-payment-type" class="form-label">نوع السداد</label>
            <select id="filter-payment-type" class="form-control select2" data-datatable-filter="true" data-filter-key="payment_type" data-placeholder="اختر نوع السداد">
                <option value=""></option>
                <option value="settlement">سداد بتسوية</option>
                <option value="without_settlement">سداد بدون تسوية</option>
            </select>
        </div>

        <div class="col-md-2">
            <label for="filter-customer" class="form-label">العميل</label>
            <select id="filter-customer" class="form-control select2-ajax-filter" data-datatable-filter="true" data-filter-key="customer_id" data-placeholder="اختر العميل">
                <option value=""></option>
                @foreach ($customers as $customer)
                    <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <label for="filter-payment-method" class="form-label">طريقة التحصيل</label>
            <select id="filter-payment-method" class="form-control select2" data-datatable-filter="true" data-filter-key="payment_method_id" data-placeholder="اختر طريقة التحصيل">
                <option value=""></option>
                @foreach ($paymentMethods as $method)
                    <option value="{{ $method->id }}">{{ $method->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6">
            <label for="filter-date-from" class="form-label">من تاريخ</label>
            <input type="date" id="filter-date-from" class="form-control" data-datatable-filter="true" data-filter-key="date_from" placeholder="اختر التاريخ">
        </div>

        <div class="col-md-6">
            <label for="filter-date-to" class="form-label">إلى تاريخ</label>
            <input type="date" id="filter-date-to" class="form-control" data-datatable-filter="true" data-filter-key="date_to" placeholder="اختر التاريخ">
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'admin-collections-table'],
                false,
            ) !!}
        </div>
    </div>

    {{-- Modal للرفض الفردي --}}
    <div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">رفض التحصيل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="reject-section">
                        <div class="text-center mb-4">
                            <i class="ti ti-x-circle text-danger" style="font-size: 3rem;"></i>
                            <h4 class="mt-3">رفض التحصيل</h4>
                        </div>
                        <div class="mb-4">
                            <label for="rejection-reason" class="form-label">
                                سبب الرفض <span class="text-danger">*</span>
                            </label>
                            <textarea id="rejection-reason" class="form-control" rows="3" placeholder="اكتب سبب رفض التحصيل..."
                                maxlength="500"></textarea>
                            <div class="form-text">
                                <span id="char-count" class="text-muted">0/500</span>
                            </div>
                        </div>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" class="btn btn-danger" id="confirm-reject">
                                <i class="ti ti-x me-1"></i>رفض
                            </button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                إلغاء
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
