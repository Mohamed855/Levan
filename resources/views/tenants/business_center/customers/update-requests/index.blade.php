@extends('tenants.layouts.layoutMaster')

@section('title', 'طلبات تحديث العملاء')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/shepherd/shepherd.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js',
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js',
'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
@vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
<script>
    window.routes = {
            approveRequest: "{{ tenant_route_name('customers.update-requests.approve', ':id') }}",
            rejectRequest: "{{ tenant_route_name('customers.update-requests.reject', ':id') }}",
            bulkApprove: "{{ tenant_route_name('customers.update-requests.bulk-approve') }}",
            bulkReject: "{{ tenant_route_name('customers.update-requests.bulk-reject') }}",
            viewRequest: "{{ tenant_route_name('customers.update-requests.show', ':id') }}",
            pendingCount: "{{ tenant_route_name('customers.update-requests.pending-count') }}",
            exportFields: "{{ tenant_route_name('customers.update-requests.export-fields') }}",
            exportExcel: "{{ tenant_route_name('customers.update-requests.export-excel') }}",
        };
        window.csrfToken = "{{ csrf_token() }}";
</script>
@vite(['resources/assets/js/tenants/business-center/customers/update-requests/index.js'])
{!! $dataTable->scripts() !!}
@endsection

@section('page-style')
@vite(['resources/css/dataTable.css'])
@endsection


@section('content')
<!-- الكروت الإحصائية -->
<div id="top_card" class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">إجمالي الطلبات</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $totalRequests }}</h4>
                        </div>
                        <small class="mb-0">جميع الطلبات</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-file-description ti-26px"></i>
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
                        <span class="text-heading">الطلبات المعلقة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $pendingRequests }}</h4>
                        </div>
                        <small class="mb-0">في انتظار الموافقة</small>
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
                        <span class="text-heading">الطلبات المعتمدة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $approvedRequests }}</h4>
                        </div>
                        <small class="mb-0">تمت الموافقة عليها</small>
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
                        <span class="text-heading">الطلبات المرفوضة</span>
                        <div class="d-flex align-items-center my-1">
                            <h4 class="mb-0 me-2">{{ $rejectedRequests }}</h4>
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
<x-data-table.filters table="#customer-update-requests-table">
    <div>
        <x-filter.select
            id="filter-status"
            filterKey="status"
            table="#customer-update-requests-table"
            name="status"
            label="الحالة"
            placeholder="اختر الحالة"
            :options="[
                'pending' => 'معلق',
                'approved' => 'موافق عليه',
                'rejected' => 'مرفوض',
            ]"
        />
    </div>
    <div>
        <label for="filter-requested-by" class="form-label">مقدم الطلب</label>
        <select id="filter-requested-by" class="form-control select2" data-placeholder="اختر مقدم الطلب" data-filter-key="requested_by" data-datatable-filter="true" data-datatable-target="#customer-update-requests-table">
            <option value=""></option>
        </select>
    </div>
</x-data-table.filters>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            {!! $dataTable->table([
            'class' => 'table table-striped table-bordered w-100',
            'id' => 'customer-update-requests-table',
            ]) !!}
        </div>
    </div>
</div>

<!-- Modal for viewing request details -->
<div class="modal fade" id="viewRequestModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">تفاصيل طلب التحديث</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="requestDetailsContent">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for rejection reason -->
<div class="modal fade" id="rejectionReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">سبب الرفض</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="rejectionForm">
                    <div class="mb-3">
                        <label for="rejectionReason" class="form-label">سبب الرفض</label>
                        <textarea class="form-control" id="rejectionReason" rows="3"
                            placeholder="أدخل سبب الرفض..."></textarea>
                    </div>
                    <input type="hidden" id="rejectionRequestId">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmRejection">تأكيد الرفض</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal for bulk rejection reason -->
<div class="modal fade" id="bulkRejectionReasonModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">سبب الرفض الجماعي</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="bulkRejectionForm">
                    <div class="mb-3">
                        <label for="bulkRejectionReason" class="form-label">سبب الرفض</label>
                        <textarea class="form-control" id="bulkRejectionReason" rows="3"
                            placeholder="أدخل سبب الرفض..."></textarea>
                    </div>
                    <input type="hidden" id="bulkRejectionRequestIds">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="button" class="btn btn-danger" id="confirmBulkRejection">تأكيد الرفض</button>
            </div>
        </div>
    </div>
</div>
@endsection