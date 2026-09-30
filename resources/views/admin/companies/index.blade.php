@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة الشركات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
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

    <div class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي الشركات" :value="$totalSubscriptions ?? $totalCompanies ?? 0"
            subtitle="عدد جميع الشركات" icon="ti-building" color="primary" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات الفعالة" :value="$activeSubscriptions ?? $activeCompanies ?? 0"
            subtitle="شركات فعالة حاليًا" icon="ti-bolt" color="success" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات المعلقة" :value="$pendingSubscriptions ?? $pendingCompanies ?? 0"
            subtitle="لم تتم الموافقة عليها بعد" icon="ti-alert-circle" color="warning" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات المنتهية" :value="$expiredSubscriptions ?? $expiredCompanies ?? 0"
            subtitle="أوقف اشتراكها أو انتهت مدته" icon="ti-clock-pause" color="danger" />
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>
@endsection
