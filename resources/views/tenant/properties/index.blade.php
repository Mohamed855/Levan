@extends('tenant.layouts.layoutMaster')

@section('title', 'العقارات')

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
            window.executeMassDelete('Property', window.routes.massDelete, 'يرجى تحديد عقار واحد على الأقل');
        }
        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')

    <x-data-table.page table-id="properties-table">
        <x-slot:stats>
            <x-ui.stat-card title="إجمالي العقارات" :value="$totalProperties" subtitle="جميع الحالات" icon="ti-building"
                color="primary" />
            <x-ui.stat-card title="عقار نشط" :value="$activeProperties" subtitle="العقارات النشطة" icon="ti-circle-check"
                color="success" />
            <x-ui.stat-card title="مكتملة البيانات" :value="$completedProperties" subtitle="جاهز للإسناد" icon="ti-file-check"
                color="info" />
            <x-ui.stat-card title="غير مرتبطة بإسناد" :value="$unlinkedProperties" subtitle="غير مسند" icon="ti-link-off"
                color="warning" />
            <x-ui.stat-card title="تحتاج للمراجعة" :value="$needsReviewProperties" subtitle="بيانات غير مكتملة" icon="ti-alert-triangle"
                color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#properties-table">
                <x-filter.select id="filter-assignment-status" filterKey="assignment_status" table="#properties-table"
                    label="حالة الارتباط" placeholder="الكل" :enum="\App\Enums\Tenants\Properties\PropertyAssignmentStatus::class" />
                <x-filter.select id="filter-assignment" filterKey="assignment_id" table="#properties-table" label="الإسناد"
                    placeholder="جميع الإسنادات" :options="$assignments->pluck('name', 'id')->toArray()" />
                <x-filter.select id="filter-type" filterKey="type" table="#properties-table" label="نوع العقار"
                    placeholder="جميع الأنواع" :enum="\App\Enums\Tenants\Properties\PropertyType::class" />
                <x-filter.select id="filter-data-status" filterKey="data_status" table="#properties-table"
                    label="حالة البيانات" placeholder="جميع الحالات" :enum="\App\Enums\Tenants\Properties\PropertyDataStatus::class" />
                <x-filter.select id="filter-city" filterKey="city" table="#properties-table" label="المدينة"
                    placeholder="جميع المدن" :options="$cityOptions" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'properties-table'],
                false,
            ) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
