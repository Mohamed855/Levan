@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل أهداف ' . $user->name)

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
])
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
        userDetails: "{{ tenant_route_name('financial-collections.collection-targets.user-details', $user->id) }}",
        targetsIndex: "{{ tenant_route_name('financial-collections.collection-targets.index') }}"
    };
    window.csrfToken = "{{ csrf_token() }}";
</script>
@vite(['resources/assets/js/tenants/financial_collection/targets/show.js'])
@endsection

@section('content')
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">إجمالي الأهداف</span>
                        <div class="d-flex align-items-center my-1">
                            <h5 class="mb-0 me-2">{{ $statistics['total_targets'] }}</h5>
                        </div>
                        <small class="mb-0">عدد جميع الأهداف</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-target ti-26px"></i>
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
                        <span class="text-heading">إجمالي التحصيلات</span>
                        <div class="d-flex align-items-center my-1">
                            <h5 class="mb-0 me-2">{{ number_format($statistics['total_collected'], 0) }}</h5>
                        </div>
                        <small class="mb-0">المبلغ الكلي المحصل</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-info">
                            <i class="ti ti-cash ti-26px"></i>
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
                        <span class="text-heading">نسبة الإنجاز</span>
                        <div class="d-flex align-items-center my-1">
                            <h5 class="mb-0 me-2">
                                @if($currentTarget)
                                    {{ $currentTarget['progress']['percentage'] }}%
                                @else
                                    0%
                                @endif
                            </h5>
                        </div>
                        <small class="mb-0">للهدف الحالي</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="ti ti-chart-pie ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($currentTarget)
    <div class="col-sm-6 col-xl-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-heading">الهدف الحالي</span>
                        <div class="d-flex align-items-center my-1">
                            <h5 class="mb-0 me-2">{{ $currentTarget['formatted_target_amount'] }}</h5>
                        </div>
                        <small class="mb-0">{{ $currentTarget['target_type'] }} - {{ $currentTarget['period'] }}</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-target-arrow ti-26px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<div class="card">
    <div class="card-body">
        {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'user-targets-table'], false) !!}
    </div>
</div>
@endsection