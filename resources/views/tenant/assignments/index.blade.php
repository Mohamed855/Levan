@extends('tenant.layouts.layoutMaster')

@section('title', 'الإسنادات')

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

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };

        function handleMassDelete() {
            window.executeMassDelete('Assignment', window.routes.massDelete, 'يرجى تحديد إسناد واحد على الأقل');
        }
        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')

    <x-data-table.page table-id="assignments-table">
        <x-slot:stats>
            <x-ui.stat-card title="إجمالي الإسنادات" :value="$totalAssignments" subtitle="جميع الحالات" icon="ti-file-text"
                color="primary" />
            <x-ui.stat-card title="إسناد نشط" :value="$activeAssignments" subtitle="الإسنادات النشطة" icon="ti-circle-check"
                color="success" />
            <x-ui.stat-card title="قيد المراجعة" :value="$underReviewAssignments" subtitle="تحت التدقيق" icon="ti-clock" color="warning" />
            <x-ui.stat-card title="متوقف" :value="$suspendedAssignments" subtitle="إسنادات معلقة" icon="ti-ban" color="danger" />
            <x-ui.stat-card title="محول لمزادات" :value="$auctionedAssignments" subtitle="تم ربطه بمزاد" icon="ti-flag" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#assignments-table">
                <x-filter.select id="filter-category" filterKey="category" label="تصنيف الإسناد"
                    placeholder="جميع التصنيفات" :enum="\App\Enums\Tenants\Assignments\AssignmentCategory::class" />
                <x-filter.select id="filter-status" filterKey="status" label="حالة الإسناد" placeholder="جميع الحالات"
                    :options="$statusOptions" />
                <x-filter.date id="filter-start-date" filterKey="start_date" target="#assignments-table"
                    label="تاريخ البداية" />
                <x-filter.date id="filter-end-date" filterKey="end_date" target="#assignments-table"
                    label="تاريخ النهاية" />
                <x-filter.select id="filter-city" filterKey="city" label="المدينة" placeholder="جميع المدن"
                    :options="$cityOptions" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'assignments-table'],
                false,
            ) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
