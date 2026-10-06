@extends('tenants.layouts.layoutMaster')

@section('title', 'إدارة المزادات')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/table-status-toggle.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-gavel me-2 text-primary"></i>إدارة المزادات</h4>
            <p class="text-muted mb-0">إدارة جميع المزادات الخاصة بشركتكم ومتابعة حالتها ومواعيدها والجهات المشرفة عليها.</p>
        </div>
        @can('auctions.create')
            <a href="{{ tenant_route_name('auctions.create') }}" class="btn btn-primary">
                <i class="ti ti-plus me-1"></i> إنشاء مزاد جديد
            </a>
        @endcan
    </div>

    <x-data-table.page table-id="auctions-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي المزادات" :value="$totalAuctions" subtitle="مزاد في جميع الحالات"
                icon="ti-gavel" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="مزادات نشطة" :value="$activeAuctions" subtitle="قيد التنفيذ حالياً"
                icon="ti-player-play" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="قيد الإعداد" :value="$inPreparationAuctions" subtitle="تحت التجهيز"
                icon="ti-clock" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="بانتظار الاعتماد" :value="$pendingAuctions" subtitle="في انتظار المراجعة"
                icon="ti-file-check" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#auctions-table">
                <x-filter.select col="col-md-4" id="filter-status" filterKey="status" label="الحالة" placeholder="جميع الحالات"
                    :options="[
                        'active'           => 'نشط',
                        'in_preparation'   => 'قيد الإعداد',
                        'pending_approval' => 'بانتظار الاعتماد',
                        'completed'        => 'منتهي',
                        'suspended'        => 'معلق'
                    ]" />
                <x-filter.select col="col-md-4" id="filter-type" filterKey="type" label="نوع المزاد" placeholder="جميع الأنواع"
                    :options="[
                        'electronic' => 'إلكتروني',
                        'in_person'  => 'حضوري',
                        'hybrid'     => 'هجين'
                    ]" />
                <x-form.controls.input col="col-md-4" name="filter_platform_name" id="filter-platform" label="المنصة / القاعة" placeholder="بحث بالمنصة أو القاعة..." />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auctions-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
