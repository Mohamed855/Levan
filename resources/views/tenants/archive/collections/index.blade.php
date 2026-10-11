@extends('tenants.layouts.layoutMaster')

@section('title', 'التحصيلات المحذوفة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/shepherd/shepherd.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/shepherd/shepherd.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    <script>
        // متغيرات JavaScript العامة
        window.routes = {
            archiveCollectionsIndex: "{{ tenant_route_name('archive.collections.index') }}",
            collectionsIndex: "{{ tenant_route_name('financial-collections.collections.index') }}",
            restoreCollection: "/archive/collections/:id/restore",
            forceDeleteCollection: "/archive/collections/:id/force-delete"
        };
        window.assets = {
            arabicDataTable: "{{ asset('static/i18n/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/archive/collections/index.js'])
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

    {{-- إحصائيات التحصيلات المحذوفة --}}
    <div id="top_card" class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">التحصيلات المحذوفة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalArchivedCollections }}</h4>
                            </div>
                            <small class="mb-0">إجمالي التحصيلات المحذوفة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-warning">
                                <i class="ti ti-archive ti-26px"></i>
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
                                <h4 class="mb-0 me-2">{{ $archivedPendingCollections }}</h4>
                            </div>
                            <small class="mb-0">تحصيلات محذوفة معلقة</small>
                        </div>
                        <div class="avatar">
                            <span class="avatar-initial rounded bg-label-info">
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
                                <h4 class="mb-0 me-2">{{ $archivedApprovedCollections }}</h4>
                            </div>
                            <small class="mb-0">تحصيلات محذوفة معتمدة</small>
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
                                <h4 class="mb-0 me-2">{{ $archivedRejectedCollections }}</h4>
                            </div>
                            <small class="mb-0">تحصيلات محذوفة مرفوضة</small>
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

    {{-- Filters Card --}}
    <x-data-table.filters table="#archived-collections-table">
        <div>
            <x-filter.select
                id="filter-status"
                filterKey="status"
                table="#archived-collections-table"
                name="status"
                label="الحالة"
                placeholder="اختر الحالة"
                :enum="\App\Enums\Shared\ApprovalStatus::class"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-collection-type"
                filterKey="collection_type"
                table="#archived-collections-table"
                name="collection_type"
                label="نوع التحصيل"
                placeholder="اختر نوع التحصيل"
                :enum="\App\Enums\Tenants\Collections\CollectionType::class"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-payment-type"
                filterKey="payment_type"
                table="#archived-collections-table"
                name="payment_type"
                label="نوع السداد"
                placeholder="اختر نوع السداد"
                :enum="\App\Enums\Tenants\Collections\PaymentType::class"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-customer"
                filterKey="customer_id"
                table="#archived-collections-table"
                name="customer_id"
                label="العميل"
                placeholder="اختر العميل"
                :options="$customers->pluck('name', 'id')"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-payment-method"
                filterKey="payment_method_id"
                table="#archived-collections-table"
                name="payment_method_id"
                label="طريقة التحصيل"
                placeholder="اختر طريقة التحصيل"
                :options="$paymentMethods->pluck('name', 'id')"
            />
        </div>

        <div>
            <x-filter.select
                id="filter-added-by"
                filterKey="added_by"
                table="#archived-collections-table"
                name="added_by"
                label="أُضيف بواسطة"
                placeholder="اختر المستخدم"
                :options="$users->pluck('name', 'id')"
            />
        </div>

        <div>
            <label for="filter-date-from" class="form-label">من تاريخ</label>
            <input type="text" id="filter-date-from" class="form-control flatpickr-date"
                placeholder="اختر التاريخ"
                data-filter-key="date_from"
                data-datatable-filter="true"
                data-datatable-target="#archived-collections-table">
        </div>

        <div>
            <label for="filter-date-to" class="form-label">إلى تاريخ</label>
            <input type="text" id="filter-date-to" class="form-control flatpickr-date"
                placeholder="اختر التاريخ"
                data-filter-key="date_to"
                data-datatable-filter="true"
                data-datatable-target="#archived-collections-table">
        </div>
    </x-data-table.filters>

    <div class="card" id="card-archived-collections">
        <div class="card-body">
            {{-- جدول البيانات --}}
            <table id="archived-collections-table" class="table table-striped table-bordered text-center">
                <thead>
                    <tr>
                        <th></th>
                        <th class="custom-checkbox">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th>العميل</th>
                        <th>المبلغ ورقم السند</th>
                        <th>تفاصيل التحصيل</th>
                        <th>طريقة التحصيل</th>
                        <th>تاريخ التحصيل</th>
                        <th>الحالة</th>
                        <th>أُضيف بواسطة</th>
                        <th>تاريخ الأرشفة</th>
                        <th>المرفق</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- سيتم تعبئة البيانات بواسطة DataTables --}}
                </tbody>
            </table>
        </div>
    </div>
@endsection
