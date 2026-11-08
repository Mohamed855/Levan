@extends('tenant.layouts.layoutMaster')

@section('title', 'الحملات التسويقية')

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

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('content')
    <x-ui.validation-errors />
    <x-data-table.page table-id="marketing-campaigns-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="إجمالي الحملات" :value="number_format($statistics['total'])" subtitle="كافة الحملات المسجلة" icon="ti-speakerphone" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="المزادات المرتبطة" :value="number_format($statistics['auctions'])" subtitle="مزادات لها حملات مسجلة" icon="ti-gavel" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="أدلة الحملات" :value="number_format($statistics['evidence'])" subtitle="الملفات المرفقة بالحملات" icon="ti-files" color="info" />
        </x-slot:stats>
        <x-slot:filters>
            <x-data-table.filters table="#marketing-campaigns-table">
                <x-filter.select id="campaign-auction-filter" filterKey="auction_id" label="المزاد" :options="$auctionOptions" />
                <x-filter.select id="campaign-channel-filter" filterKey="channel" label="نوع الحملة" :options="__('auction_reports.channels', [], 'ar')" />
                <x-filter.date id="campaign-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />
                <x-filter.date id="campaign-created-to" filterKey="created_to" label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>
        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'marketing-campaigns-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection