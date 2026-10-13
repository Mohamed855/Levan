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
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي الشركات" :value="$totalSubscriptions"
                subtitle="عدد جميع الشركات" icon="ti-building" color="primary" />

            <x-ui.stat-card col="col-sm-6 col-xl-3" title="الشركات الفعالة" :value="$activeSubscriptions"
                subtitle="شركات فعالة حاليًا" icon="ti-bolt" color="success" />

            <x-ui.stat-card col="col-sm-6 col-xl-3" title="الشركات المعلقة" :value="$pendingSubscriptions"
                subtitle="لم تتم الموافقة عليها بعد" icon="ti-alert-circle" color="warning" />

            <x-ui.stat-card col="col-sm-6 col-xl-3" title="الشركات المنتهية" :value="$expiredSubscriptions"
                subtitle="أوقف اشتراكها أو انتهت مدته" icon="ti-clock-pause" color="danger" />
        </div>
    @endif

    @if (!($isPendingPage ?? false) && !($isExpiringPage ?? false) && !($isExpiredPage ?? false))
        {{-- Filters Card --}}
        <x-data-table.filters table="#subscriptions-table">
            <x-filter.select id="filter-status" filterKey="status" label="حالة الشركات"
                placeholder="اختر حالة الشركة" :enum="\App\Enums\Admin\Subscriptions\SubscriptionStatus::class" />
        </x-data-table.filters>
    @endif

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'subscriptions-table'],
                false,
            ) !!}
        </div>
    </div>

    {{-- Confirmation Modal for Accept/Reject --}}
    @include('admin.subscription_manage.partials.confirm_modal')
@endsection
