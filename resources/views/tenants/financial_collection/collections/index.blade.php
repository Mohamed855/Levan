@extends('tenants.layouts.layoutMaster')

@section('title', 'التحصيلات المالية')

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
            collectionsIndex: "{{ tenant_route_name('financial-collections.collections.index') }}",
            collectionsCreate: "{{ tenant_route_name('financial-collections.collections.create') }}",
            collectionsShow: "{{ tenant_route_name('financial-collections.collections.show', ':id') }}",
            collectionsApprove: "{{ tenant_route_name('financial-collections.collections.approve', ':id') }}",
            collectionsReject: "{{ tenant_route_name('financial-collections.collections.reject', ':id') }}",
            collectionsDeleteSelected: "{{ tenant_route_name('financial-collections.collections.delete-selected') }}",
            recentCollections: "{{ tenant_route_name('financial-collections.collections.recent-collections') }}",
            generateReceiptNumber: "{{ tenant_route_name('financial-collections.collections.generate-receipt-number') }}",
            searchCustomers: "{{ tenant_route_name('financial-collections.collections.search-customers') }}",
            getCustomer: "{{ tenant_route_name('financial-collections.collections.get-customer', ':id') }}"
        };

        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/financial_collection/collections/index.js'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">إجمالي تحصيلاتي</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalCollections }}</h4>
                            </div>
                            <small class="mb-0">جميع تحصيلاتي</small>
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
                            <span class="avatar-initial rounded bg-label-info">
                                <i class="ti ti-calendar-event ti-26px"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Filters Card --}}
    <x-data-table.filters table="#collections-table">
        <div class="col-md-3">
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#collections-table"
                name="status"
                label="الحالة"
                placeholder="اختر الحالة"
                :options="[
                    'pending' => 'غير معتمد',
                    'approved' => 'معتمد',
                    'rejected' => 'مرفوض'
                ]"
            />
        </div>

        <div class="col-md-2">
            <x-filter.select
                id="filter-collection-type"
                filterKey="collection_type"
                table="#collections-table"
                name="collection_type"
                label="نوع التحصيل"
                placeholder="اختر النوع"
                :options="[
                    'cash' => 'نقدي',
                    'scheduled' => 'مجدول'
                ]"
            />
        </div>

        <div class="col-md-2">
            <x-filter.select
                id="filter-payment-type"
                filterKey="payment_type"
                table="#collections-table"
                name="payment_type"
                label="نوع السداد"
                placeholder="اختر نوع السداد"
                :options="[
                    'settlement' => 'سداد بتسوية',
                    'without_settlement' => 'سداد بدون تسوية'
                ]"
            />
        </div>

        <div class="col-md-2">
            <label for="filter-customer" class="form-label">العميل</label>
            <select id="filter-customer" class="form-control select2-ajax-filter"
                data-placeholder="اختر رقم حساب العميل"
                data-filter-key="customer_id"
                data-datatable-filter="true"
                data-datatable-target="#collections-table">
                <option value=""></option>
                @if (empty($initialCustomers))
                    <option value="" disabled>لا يوجد عملاء متاحين</option>
                @else
                    @foreach (array_slice($initialCustomers, 0, 10) as $customer)
                        <option value="{{ $customer['id'] }}">{{ $customer['text'] }}</option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="col-md-3">
            <x-filter.select
                id="filter-payment-method"
                filterKey="payment_method_id"
                table="#collections-table"
                name="payment_method_id"
                label="طريقة التحصيل"
                placeholder="اختر طريقة التحصيل"
                :options="$paymentMethods->pluck('name', 'id')"
            />
        </div>

        <div class="col-md-6">
            <label for="filter-date-from" class="form-label">من تاريخ</label>
            <input type="date" id="filter-date-from" class="form-control" placeholder="اختر التاريخ" data-filter-key="date_from" data-datatable-filter="true" data-datatable-target="#collections-table">
        </div>

        <div class="col-md-6">
            <label for="filter-date-to" class="form-label">إلى تاريخ</label>
            <input type="date" id="filter-date-to" class="form-control" placeholder="اختر التاريخ" data-filter-key="date_to" data-datatable-filter="true" data-datatable-target="#collections-table">
        </div>
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'collections-table'],
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
