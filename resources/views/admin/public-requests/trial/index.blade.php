@extends('admin.layouts.layoutMaster')

@section('title', 'طلبات التجربة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')
    <div class="row g-4 mb-4">
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي الطلبات" :value="number_format($stats['total'])"
            subtitle="كل طلبات التجربة" icon="ti-clipboard-list" color="primary" />
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="قيد الانتظار" :value="number_format($stats['pending'])"
            subtitle="تحتاج إلى مراجعة" icon="ti-clock" color="warning" />
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="تمت الموافقة" :value="number_format($stats['approved'])"
            subtitle="طلبات مقبولة" icon="ti-check" color="success" />
        <x-ui.stat-card col="col-sm-6 col-xl-3" title="مرفوضة" :value="number_format($stats['rejected'])"
            subtitle="طلبات مرفوضة" icon="ti-x" color="danger" />
    </div>

    <x-data-table.filters table="#trial-requests-table">
        <x-filter.select id="filter-status" filterKey="status" label="الحالة"
            placeholder="اختر الحالة" :options="$filterOptions['statuses']" />
        <x-filter.date id="filter-date-from" filterKey="date_from" label="من تاريخ" />
        <x-filter.date id="filter-date-to" filterKey="date_to" label="إلى تاريخ" />
    </x-data-table.filters>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>
@endsection
