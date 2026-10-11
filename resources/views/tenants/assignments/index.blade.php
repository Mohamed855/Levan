@extends('tenants.layouts.layoutMaster')

@section('title', 'الإسنادات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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

            $('#assignments-table').on('init.dt', function() {
                const table = $(this).DataTable();
                const tableWrapper = $('#assignments-table_wrapper');

                tableWrapper.on('click', '#select-all', function() {
                    $('.row-checkbox', table.rows({
                        search: 'applied'
                    }).nodes()).prop('checked', this.checked);
                });

                tableWrapper.on('change', '.row-checkbox', function() {
                    if (!this.checked) {
                        $('#select-all').prop('checked', false);
                    }
                });
            });

            $('#assignments-table').on('draw.dt', function() {
                $('#select-all').prop('checked', false);
            });
        });

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                toastr.warning('يرجى تحديد إسناد واحد على الأقل');
                return;
            }

            confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Assignment');
        }
        window.handleMassDelete = handleMassDelete;

        $(document).on('click', '.copyable-code-wrapper', function() {
            const code = $(this).data('code');
            if (code) {
                navigator.clipboard.writeText(code).then(() => {
                    toastr.success('تم نسخ رقم الإسناد: ' + code);
                });
            }
        });
    </script>
@endsection

@section('content')

    <x-data-table.page table-id="assignments-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي الإسنادات" :value="$totalAssignments" subtitle="جميع الحالات"
                icon="ti-file-text" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="إسناد نشط" :value="$activeAssignments" subtitle="الإسنادات النشطة"
                icon="ti-circle-check" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="قيد المراجعة" :value="$underReviewAssignments" subtitle="تحت التدقيق"
                icon="ti-clock" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="متوقف" :value="$suspendedAssignments" subtitle="إسنادات معلقة" icon="ti-ban"
                color="danger" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="محول لمزادات" :value="$auctionedAssignments" subtitle="تم ربطه بمزاد"
                icon="ti-flag" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#assignments-table">
                <x-filter.select id="filter-status" filterKey="status" label="حالة الإسناد" placeholder="جميع الحالات"
                    :enum="\App\Enums\Tenants\Assignments\AssignmentStatus::class" />
                <x-filter.select id="filter-category" filterKey="category" label="نوع الإسناد" placeholder="جميع التصنيفات"
                    :enum="\App\Enums\Tenants\Assignments\AssignmentCategory::class" />
                <x-filter.select id="filter-city" filterKey="city" label="المدينة" placeholder="جميع المدن"
                    :options="$cityOptions" />
                <x-form.controls.input name="filter_issuing_entity" id="filter-entity" label="الجهة المسندة"
                    placeholder="بحث باسم الجهة..." />
                <x-form.controls.input name="start_date_from" id="filter-start-date" type="date" label="تاريخ البداية" />
                <x-form.controls.input name="end_date_to" id="filter-end-date" type="date" label="تاريخ النهاية" />
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
