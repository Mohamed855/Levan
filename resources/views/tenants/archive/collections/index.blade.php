@extends('tenants.layouts.layoutMaster')

@section('title', 'أرشيف التحصيلات')

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
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
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

    {{-- إحصائيات التحصيلات المؤرشفة --}}
    <div id="top_card" class="row g-4 mb-4">
        <div class="col-sm-6 col-xl-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex align-items-start justify-content-between">
                        <div class="content-left">
                            <span class="text-heading">التحصيلات المؤرشفة</span>
                            <div class="d-flex align-items-center my-1">
                                <h4 class="mb-0 me-2">{{ $totalArchivedCollections }}</h4>
                            </div>
                            <small class="mb-0">إجمالي التحصيلات المؤرشفة</small>
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
                            <small class="mb-0">تحصيلات مؤرشفة معلقة</small>
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
                            <small class="mb-0">تحصيلات مؤرشفة معتمدة</small>
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
                            <small class="mb-0">تحصيلات مؤرشفة مرفوضة</small>
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

    {{-- جدول التحصيلات المؤرشفة --}}
    <div class="card" id="card-archived-collections">
        <div class="card-body">
            {{-- فلاتر البحث --}}
            <div class="filters mb-4">
                {{-- الصف الأول --}}
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="filter-status" class="form-label">الحالة</label>
                        <select id="filter-status" class="form-control select2" data-placeholder="اختر الحالة">
                            <option value=""></option>
                            <option value="pending">غير معتمد</option>
                            <option value="approved">معتمد</option>
                            <option value="rejected">مرفوض</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter-collection-type" class="form-label">نوع التحصيل</label>
                        <select id="filter-collection-type" class="form-control select2" data-placeholder="اختر النوع">
                            <option value=""></option>
                            <option value="cash">نقدي</option>
                            <option value="scheduled">مجدول</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter-payment-type" class="form-label">نوع السداد</label>
                        <select id="filter-payment-type" class="form-control select2" data-placeholder="اختر نوع السداد">
                            <option value=""></option>
                            <option value="settlement">سداد بتسوية</option>
                            <option value="without_settlement">سداد بدون تسوية</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter-customer" class="form-label">العميل</label>
                        <select id="filter-customer" class="form-control select2" data-placeholder="اختر العميل">
                            <option value=""></option>
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                {{-- الصف الثاني --}}
                <div class="row">
                    <div class="col-md-3">
                        <label for="filter-payment-method" class="form-label">طريقة التحصيل</label>
                        <select id="filter-payment-method" class="form-control select2"
                            data-placeholder="اختر طريقة التحصيل">
                            <option value=""></option>
                            @foreach ($paymentMethods as $method)
                                <option value="{{ $method->id }}">{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter-added-by" class="form-label">أُضيف بواسطة</label>
                        <select id="filter-added-by" class="form-control select2" data-placeholder="اختر المستخدم">
                            <option value=""></option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter-date-from" class="form-label">من تاريخ</label>
                        <input type="text" id="filter-date-from" class="form-control flatpickr-date"
                            placeholder="اختر التاريخ">
                    </div>

                    <div class="col-md-3">
                        <label for="filter-date-to" class="form-label">إلى تاريخ</label>
                        <input type="text" id="filter-date-to" class="form-control flatpickr-date"
                            placeholder="اختر التاريخ">
                    </div>
                </div>
            </div>

            <hr>

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
