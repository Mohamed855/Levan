@extends('admin.layouts.layoutMaster')

@section('title', 'قوالب المزادات')

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
            massDelete: "{{ route('admin.mass-delete') }}"
        };
    </script>
    @vite(['resources/assets/js/admin/auction-templates/index.js'])
@endsection

@section('content')
    <x-data-table.page table-id="auction-templates-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي القوالب" :value="$statistics['total']" subtitle="جميع القوالب المسجلة" icon="ti-files"
                color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="قوالب مفعلة" :value="$statistics['active']" subtitle="متاحة للاستخدام" icon="ti-check" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="قوالب البروشور" :value="$statistics['brochure']" subtitle="قوالب الكتيبات التعريفية"
                icon="ti-file-description" color="info" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="اللوحات الإعلانية" :value="$statistics['billboard']" subtitle="قوالب اللوحات الخارجية" icon="ti-ad"
                color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="قوالب معطلة" :value="$statistics['inactive']" subtitle="غير متاحة للمستأجرين" icon="ti-ban"
                color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#auction-templates-table">
                <x-filter.select id="filter-type" filterKey="type" table="#auction-templates-table" label="نوع القالب"
                    placeholder="جميع الأنواع" :enum="\App\Enums\Admin\AuctionTemplates\AuctionTemplateType::class" />
                <x-filter.select id="filter-status" filterKey="is_active" table="#auction-templates-table"
                    label="حالة القالب" placeholder="جميع الحالات" :options="$statusFilterOptions ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'auction-templates-table'],
                false,
            ) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
