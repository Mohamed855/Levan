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
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection
@section('page-script')
    <script>
        window.routes = Object.assign({}, window.routes || {}, {
            massDelete: "{{ route('admin.mass-delete', [], false) }}",
            acceptSubscription: "{{ route('admin.subscriptionManage.accept', ':id', false) }}",
            rejectSubscription: "{{ route('admin.subscriptionManage.reject', ':id', false) }}",
            massAccept: "{{ route('admin.subscriptionManage.mass-accept', [], false) }}",
            massReject: "{{ route('admin.subscriptionManage.mass-reject', [], false) }}"
        });
    </script>
    @vite(['resources/assets/js/admin/subscriptions/index.js'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4 mb-4">
        <x-ui.stat-card title="إجمالي الاشتراكات" :value="$totalSubscriptions ?? 0" subtitle="جميع اشتراكات الشركات" icon="ti-packages"
            color="primary" />

        <x-ui.stat-card title="الاشتراكات النشطة" :value="$activeSubscriptions ?? 0" subtitle="اشتراكات سارية حالياً" icon="ti-circle-check"
            color="success" />

        <x-ui.stat-card title="طلبات جديدة معلقة" :value="$pendingSubscriptions ?? 0" subtitle="بانتظار الموافقة" icon="ti-clock-pause"
            color="warning" />

        <x-ui.stat-card title="تقارب على الانتهاء" :value="$expiringSubscriptions ?? 0" subtitle="خلال 30 يوماً" icon="ti-alert-triangle"
            color="info" />

        <x-ui.stat-card title="الاشتراكات المنتهية" :value="$expiredSubscriptions ?? 0" subtitle="انتهت صلاحيتها" icon="ti-clock-x"
            color="danger" />
    </div>

    @if (!($isPendingPage ?? false) && !($isExpiringPage ?? false) && !($isExpiredPage ?? false))
        {{-- Filters Card --}}
        <x-data-table.filters table="#subscriptions-table">
            <x-filter.select id="filter-status" filterKey="status" label="حالة الشركات" placeholder="اختر حالة الشركة"
                :enum="\App\Enums\Admin\Subscriptions\SubscriptionStatus::class" />
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
    @include('admin.subscription-management.partials.confirm_modal')
@endsection
