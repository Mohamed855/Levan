@extends('tenant.layouts.layoutMaster')

@section('title', 'طلبات إقامة المزادات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')
    <x-ui.validation-errors />
    <x-data-table.page table-id="auction-establishment-requests-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي المزادات" :value="number_format($statistics['total'])" subtitle="كافة المزادات المسجلة" icon="ti-gavel" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="طلبات لم تقدم" :value="number_format($statistics['not_started'])" subtitle="لم يسجل طلب الإقامة بعد" icon="ti-file-plus" color="secondary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="قيد المراجعة" :value="number_format($statistics['under_review'])" subtitle="بانتظار اعتماد الطلب" icon="ti-clock" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="طلبات معتمدة" :value="number_format($statistics['approved'])" subtitle="تم حفظ رقم الموافقة" icon="ti-file-check" color="success" />
        </x-slot:stats>
        <x-slot:filters>
            <x-data-table.filters table="#auction-establishment-requests-table">
                <x-filter.select id="establishment-status-filter" filterKey="request_status" table="#auction-establishment-requests-table" label="حالة الطلب" placeholder="جميع الحالات" :options="['not_started' => 'لم يقدم', 'under_review' => 'قيد المراجعة', 'approved' => 'معتمد']" />
                <x-filter.date id="establishment-submitted-from" filterKey="submitted_from" target="#auction-establishment-requests-table" label="تاريخ تقديم الطلب من" />
                <x-filter.date id="establishment-submitted-to" filterKey="submitted_to" target="#auction-establishment-requests-table" label="تاريخ تقديم الطلب إلى" />
                <x-filter.date id="establishment-created-from" filterKey="created_from" target="#auction-establishment-requests-table" label="تاريخ إنشاء المزاد من" />
                <x-filter.date id="establishment-created-to" filterKey="created_to" target="#auction-establishment-requests-table" label="تاريخ إنشاء المزاد إلى" />
            </x-data-table.filters>
        </x-slot:filters>
        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auction-establishment-requests-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
