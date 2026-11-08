@extends('tenant.layouts.layoutMaster')

@section('title', 'تقارير المزادات')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js',
    ])
    {!! $dataTable->scripts() !!}
@endsection


@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')
    <x-ui.validation-errors />
    <x-data-table.page table-id="auction-reports-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي التقارير" :value="number_format($statistics['total'])" subtitle="تقريران لكل مزاد" icon="ti-file-analytics" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="تقارير غير مكتملة" :value="number_format($statistics['incomplete'])" subtitle="لم تسجل بيانات الحملات" icon="ti-file-alert" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="تقارير مكتملة" :value="number_format($statistics['complete'])" subtitle="حملات مسجلة ومزاد قابل للتحديث" icon="ti-file-check" color="info" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="تقارير منتهية" :value="number_format($statistics['ended'])" subtitle="مزادات بيعت جميع عقاراتها" icon="ti-lock" color="success" />
        </x-slot:stats>
        <x-slot:filters>
            <x-data-table.filters table="#auction-reports-table">
                <x-filter.select id="report-type-filter" filterKey="report_kind" label="نوع التقرير" :options="['outputs' => 'مخرجات المزاد', 'report' => 'التقرير النهائي']" />
                <x-filter.select id="report-state-filter" filterKey="content_status" label="حالة التقرير" :options="['incomplete' => 'غير مكتمل', 'complete' => 'مكتمل', 'ended' => 'منتهي']" />
                <x-filter.date id="report-created-from" filterKey="created_from" label="إنشاء المزاد من" />
                <x-filter.date id="report-created-to" filterKey="created_to" label="إنشاء المزاد إلى" />
            </x-data-table.filters>
        </x-slot:filters>
        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auction-reports-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection