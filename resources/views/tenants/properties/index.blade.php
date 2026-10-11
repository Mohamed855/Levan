@extends('tenants.layouts.layoutMaster')

@section('title', 'العقارات')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/table-status-toggle.js'])
    <script>
        window.routes = {
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };

        $(document).ready(function() {
            function initializeComponents() {
                if ($.fn.select2) {
                    $('.select2').not('.select2-hidden-accessible').each(function() {
                        $(this).select2({
                            placeholder: $(this).data('placeholder') || 'اختر',
                            allowClear: true,
                            width: '100%',
                            language: 'ar',
                            dir: 'rtl'
                        });
                    });
                }
            }
            initializeComponents();

            $('#properties-table').on('init.dt', function() {
                const table = $(this).DataTable();
                const tableWrapper = $('#properties-table_wrapper');

                tableWrapper.on('click', '#select-all', function() {
                    $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
                });

                tableWrapper.on('change', '.row-checkbox', function() {
                    if (!this.checked) {
                        $('#select-all').prop('checked', false);
                    }
                });
            });

            $('#properties-table').on('draw.dt', function() {
                $('#select-all').prop('checked', false);
            });
        });

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                toastr.warning('يرجى تحديد عقار واحد على الأقل');
                return;
            }

            confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Property');
        }
        window.handleMassDelete = handleMassDelete;

        $(document).on('click', '.copyable-code-wrapper', function() {
            const code = $(this).data('code');
            if (code) {
                navigator.clipboard.writeText(code).then(() => {
                    toastr.success('تم النسخ: ' + code);
                });
            }
        });
    </script>
@endsection

@section('content')

    <x-data-table.page table-id="properties-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي العقارات" :value="$totalProperties" subtitle="كافة العقارات المسجلة"
                icon="ti-building" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="عقارات متاحة" :value="$availableProperties" subtitle="جاهزة للإدراج بمزاد"
                icon="ti-check" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="مدرجة بمزادات" :value="$assignedProperties" subtitle="مرتبطة بمزاد حالي"
                icon="ti-gavel" color="info" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="مكتملة البيانات" :value="$completedProperties" subtitle="بيانات وصور مكتملة"
                icon="ti-file-check" color="warning" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#properties-table">
                <x-filter.select id="filter-assignment" filterKey="assignment_id" label="الإسناد" placeholder="جميع الإسنادات"
                    :options="$assignments->pluck('name', 'id')->toArray()" />
                <x-filter.select id="filter-type" filterKey="type" label="نوع العقار" placeholder="جميع الأنواع"
                    :options="[
                        'residential_land'     => 'أرض سكنية',
                        'villa'                => 'فيلا',
                        'residential_building' => 'عمارة سكنية',
                        'commercial_building'  => 'مبنى تجاري',
                        'commercial_land'      => 'أرض تجارية',
                        'mixed_use'            => 'متعدد الاستخدام'
                    ]" />
                <x-filter.select id="filter-data-status" filterKey="data_status" label="حالة البيانات" placeholder="جميع الحالات"
                    :options="[
                        'completed'    => 'مكتمل',
                        'needs_images' => 'يحتاج صور',
                        'incomplete'   => 'ناقص بيانات'
                    ]" />
                <x-filter.select id="filter-city" filterKey="city" label="المدينة" placeholder="جميع المدن"
                    :options="['الرياض' => 'الرياض', 'جدة' => 'جدة', 'مكة المكرمة' => 'مكة المكرمة', 'المدينة المنورة' => 'المدينة المنورة', 'الدمام' => 'الدمام']" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'properties-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
