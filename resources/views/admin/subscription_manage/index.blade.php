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
            <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي الشركات" :value="$totalSubscriptions"
                subtitle="عدد جميع الشركات" icon="ti-building" color="primary" />

            <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات الفعالة" :value="$activeSubscriptions"
                subtitle="شركات فعالة حاليًا" icon="ti-bolt" color="success" />

            <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات المعلقة" :value="$pendingSubscriptions"
                subtitle="لم تتم الموافقة عليها بعد" icon="ti-alert-circle" color="warning" />

            <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات المنتهية" :value="$expiredSubscriptions"
                subtitle="أوقف اشتراكها أو انتهت مدته" icon="ti-clock-pause" color="danger" />
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
