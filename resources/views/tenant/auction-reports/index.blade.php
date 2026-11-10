@extends('tenant.layouts.layoutMaster')

@section('title', 'تقارير المزادات')

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

    <x-data-table.page table-id="auction-reports-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي التقارير" :value="number_format($statistics['total'] ?? 0)" subtitle="تقريران لكل مزاد" icon="ti-file-analytics" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="تقارير غير مكتملة" :value="number_format($statistics['incomplete'] ?? 0)" subtitle="بعض أقسام الحملات أو حقولها غير مكتملة" icon="ti-file-alert" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="تقارير مكتملة" :value="number_format($statistics['complete'] ?? 0)" subtitle="اكتملت جميع أقسام الحملات والحقول المطلوبة" icon="ti-file-check" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#auction-reports-table">
                <x-filter.select id="report-type-filter" filterKey="report_kind" table="#auction-reports-table" label="نوع التقرير" placeholder="جميع التقارير" :enum="\App\Enums\Tenants\Auctions\AuctionReportKind::class" />
                <x-filter.select id="report-state-filter" filterKey="content_status" table="#auction-reports-table" label="حالة التقرير" placeholder="جميع الحالات" :enum="\App\Enums\Tenants\Auctions\AuctionReportContentStatus::class" />
                <x-filter.date id="report-created-from" filterKey="created_from" target="#auction-reports-table" label="تاريخ إنشاء المزاد من" />
                <x-filter.date id="report-created-to" filterKey="created_to" target="#auction-reports-table" label="تاريخ إنشاء المزاد إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auction-reports-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
