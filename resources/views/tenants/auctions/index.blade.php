@extends('tenants.layouts.layoutMaster')

@section('title', 'إدارة المزادات')

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

            $('#auctions-table').on('init.dt', function() {
                const table = $(this).DataTable();
                const tableWrapper = $('#auctions-table_wrapper');

                tableWrapper.on('click', '#select-all', function() {
                    $('.row-checkbox', table.rows({ search: 'applied' }).nodes()).prop('checked', this.checked);
                });

                tableWrapper.on('change', '.row-checkbox', function() {
                    if (!this.checked) {
                        $('#select-all').prop('checked', false);
                    }
                });
            });

            $('#auctions-table').on('draw.dt', function() {
                $('#select-all').prop('checked', false);
            });
        });

        function handleMassDelete() {
            const selectedIds = [];
            $('.row-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });

            if (selectedIds.length === 0) {
                toastr.warning('يرجى تحديد مزاد واحد على الأقل');
                return;
            }

            confirmDeleteSelectedmss(selectedIds, window.routes.massDelete, 'Auction');
        }
        window.handleMassDelete = handleMassDelete;
    </script>
@endsection

@section('content')

    <x-data-table.page table-id="auctions-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="إجمالي المزادات" :value="$totalAuctions" subtitle="مزاد في جميع الحالات"
                icon="ti-gavel" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="مزادات نشطة" :value="$activeAuctions" subtitle="قيد التنفيذ حالياً"
                icon="ti-player-play" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="قيد الإعداد" :value="$inPreparationAuctions" subtitle="تحت التجهيز"
                icon="ti-clock" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl-3" title="بانتظار الاعتماد" :value="$pendingAuctions" subtitle="في انتظار المراجعة"
                icon="ti-file-check" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#auctions-table">
                <x-filter.select id="filter-status" filterKey="status" label="الحالة" placeholder="جميع الحالات"
                    :enum="\App\Enums\Tenants\Auctions\AuctionStatus::class" />
                <x-filter.select id="filter-type" filterKey="type" label="نوع المزاد" placeholder="جميع الأنواع"
                    :enum="\App\Enums\Tenants\Auctions\AuctionType::class" />
                <x-form.controls.input name="filter_platform_name" id="filter-platform" label="المنصة / القاعة" placeholder="بحث بالمنصة أو القاعة..." />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auctions-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
