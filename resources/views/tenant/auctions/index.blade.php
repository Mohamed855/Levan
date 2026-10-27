@extends('tenant.layouts.layoutMaster')

@section('title', 'إدارة المزادات')

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
        window.routes = {
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };

        function handleMassDelete() {
            window.executeMassDelete('Auction', window.routes.massDelete, 'يرجى تحديد مزاد واحد على الأقل');
        }
        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')

    <x-data-table.page table-id="auctions-table">
        <x-slot:stats>
            <x-ui.stat-card title="إجمالي المزادات" :value="$totalAuctions" subtitle="مزاد في جميع الحالات" icon="ti-gavel"
                color="primary" />
            <x-ui.stat-card title="مزادات نشطة" :value="$activeAuctions" subtitle="قيد التنفيذ حالياً" icon="ti-player-play"
                color="success" />
            <x-ui.stat-card title="قيد الإعداد" :value="$inPreparationAuctions" subtitle="تحت الإعداد" icon="ti-clock" color="warning" />
            <x-ui.stat-card title="بانتظار الاعتماد" :value="$pendingAuctions" subtitle="في انتظار المراجعة" icon="ti-file-check"
                color="info" />
            <x-ui.stat-card title="تحتاج مراجعة" :value="$needsReviewAuctions" subtitle="مزادات معلقة" icon="ti-alert-triangle"
                color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#auctions-table">
                <x-filter.select id="filter-type" filterKey="type" table="#auctions-table" label="نوع المزاد"
                    placeholder="جميع الأنواع" :enum="\App\Enums\Tenants\Auctions\AuctionType::class" />
                <x-filter.select id="filter-status" filterKey="status" table="#auctions-table" label="الحالة"
                    placeholder="جميع الحالات" :enum="\App\Enums\Tenants\Auctions\AuctionStatus::class" />
                <x-filter.select id="filter-assignment" filterKey="assignment_id" table="#auctions-table" label="الإسناد"
                    placeholder="جميع الإسنادات" :options="$assignments->pluck('name', 'id')->all()" />
                <x-filter.date id="filter-start-date" filterKey="start_date" target="#auctions-table"
                    label="تاريخ البداية" />
                <x-filter.date id="filter-end-date" filterKey="end_date" target="#auctions-table" label="تاريخ النهاية" />
                <x-filter.select id="filter-city" filterKey="city" table="#auctions-table" label="المدينة"
                    placeholder="جميع المدن" :options="$cityOptions" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auctions-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
