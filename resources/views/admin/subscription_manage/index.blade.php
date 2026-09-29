@extends('admin.layouts.layoutMaster')

@section('title', $pageTitle ?? 'إدارة الشركات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection
@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}",
            acceptSubscription: "{{ route('admin.subscriptionManage.accept', ':id') }}",
            rejectSubscription: "{{ route('admin.subscriptionManage.reject', ':id') }}",
            massAccept: "{{ route('admin.subscriptionManage.mass-accept') }}",
            massReject: "{{ route('admin.subscriptionManage.mass-reject') }}"
        };
    </script>
    @if (!($isPendingPage ?? false) && !($isExpiringPage ?? false) && !($isExpiredPage ?? false))
        @vite(['resources/assets/js/admin/subscriptions/filter.js'])
    @endif
    @vite(['resources/assets/js/admin/subscriptions/index.js'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (!($isExpiringPage ?? false) && !($isPendingPage ?? false) && !($isExpiredPage ?? false))
        <div class="row g-4 mb-4">
            <!-- إجمالي الشركات -->
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading">إجمالي الشركات</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{ $totalSubscriptions }}</h4>
                                </div>
                                <small class="mb-0">عدد جميع الشركات</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-primary">
                                    <i class="ti ti-file-invoice ti-26px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الشركات الفعالة -->
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading">الشركات الفعالة</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{ $activeSubscriptions }}</h4>
                                </div>
                                <small class="mb-0">شركات فعالة حاليًا</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-success">
                                    <i class="ti ti-bolt ti-26px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الشركات المنتهية -->
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading">الشركات المنتهية</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{ $expiredSubscriptions }}</h4>
                                </div>
                                <small class="mb-0">انتهت صلاحيتها </small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-warning">
                                    <i class="ti ti-alert-circle ti-26px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- الشركات المعلّقة -->
            <div class="col-sm-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-start justify-content-between">
                            <div class="content-left">
                                <span class="text-heading">الشركات المعلقة</span>
                                <div class="d-flex align-items-center my-1">
                                    <h4 class="mb-0 me-2">{{ $suspendedSubscriptions }}</h4>
                                </div>
                                <small class="mb-0">لم يتم تفعيلها بعد</small>
                            </div>
                            <div class="avatar">
                                <span class="avatar-initial rounded bg-label-danger">
                                    <i class="ti ti-clock-pause ti-26px"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="card">
        <div class="card-body">
            @if (!($isPendingPage ?? false) && !($isExpiringPage ?? false) && !($isExpiredPage ?? false))
                <div class="filters row g-3">
                    <div class="col-md-6">
                        <label for="filter-status">حالة الشركات</label>
                        <select id="filter-status" class="form-control select2" data-placeholder="اختر  حالة الشرك">
                            <option value=""></option>
                            <option value="pending">قيد المراجعة</option>
                            <option value="active">نشط</option>
                            <option value="suspended">معلق</option>
                            <option value="cancelled">ملغي</option>
                            <option value="expired">منتهي</option>
                        </select>
                    </div>
                </div>
                <hr class="mt-10">
            @endif
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

    {{-- Confirmation Modal for Accept/Reject --}}
    @include('admin.subscription_manage.partials.confirm_modal')
@endsection
