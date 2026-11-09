@extends('tenant.layouts.layoutMaster')

@section('title', 'الحملات التسويقية للمزادات')

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

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ tenant_route_name('marketing-campaigns.mass-delete') }}"
        };

        function handleMassDelete() {
            window.executeMassDelete('MarketingCampaign', window.routes.massDelete, 'يرجى تحديد مزاد واحد على الأقل لحذف حملاته');
        }
        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')
    <x-ui.validation-errors />

    <x-data-table.page table-id="marketing-campaigns-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="المزادات المسجل لها حملات" :value="number_format($statistics['auctions'] ?? 0)" subtitle="مزادات تحتوي على حملة واحدة على الأقل" icon="ti-gavel" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="إجمالي الحملات" :value="number_format($statistics['total'] ?? 0)" subtitle="كافة الحملات التسويقية المنفذة" icon="ti-speakerphone" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-4" title="مرفقات الحملات" :value="number_format($statistics['evidence'] ?? 0)" subtitle="الملفات والمستندات المرفقة" icon="ti-files" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#marketing-campaigns-table">
                <x-filter.select id="campaign-auction-filter" filterKey="auction_id" table="#marketing-campaigns-table" label="المزاد" placeholder="جميع المزادات" :options="$auctionOptions" />
                <x-filter.select id="campaign-channel-filter" filterKey="channel" table="#marketing-campaigns-table" label="نوع الحملة" placeholder="جميع الأنواع" :options="__('auction_reports.channels', [], 'ar')" />
                <x-filter.date id="campaign-created-from" filterKey="created_from" target="#marketing-campaigns-table" label="تاريخ المزاد من" />
                <x-filter.date id="campaign-created-to" filterKey="created_to" target="#marketing-campaigns-table" label="تاريخ المزاد إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'marketing-campaigns-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
