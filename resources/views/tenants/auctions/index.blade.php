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
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي المزادات" :value="$totalAuctions" subtitle="مزاد في جميع الحالات"
                icon="ti-gavel" color="primary" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="مزادات نشطة" :value="$activeAuctions" subtitle="قيد التنفيذ حالياً"
                icon="ti-player-play" color="success" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="قيد الإعداد" :value="$inPreparationAuctions" subtitle="تحت التجهيز"
                icon="ti-clock" color="warning" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="بانتظار الاعتماد" :value="$pendingAuctions" subtitle="في انتظار المراجعة"
                icon="ti-file-check" color="info" />
            <x-ui.stat-card col="col-sm-6 col-xl" title="تحتاج مراجعة" :value="$needsReviewAuctions" subtitle="مزادات معلقة"
                icon="ti-alert-triangle" color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#auctions-table">
                <x-filter.select id="filter-type" filterKey="type" table="#auctions-table" label="نوع المزاد" placeholder="جميع الأنواع"
                    :enum="\App\Enums\Tenants\Auctions\AuctionType::class" />
                <x-filter.select id="filter-status" filterKey="status" table="#auctions-table" label="الحالة" placeholder="جميع الحالات"
                    :enum="\App\Enums\Tenants\Auctions\AuctionStatus::class" />
                <x-filter.select id="filter-assignment" filterKey="assignment_id" table="#auctions-table" label="الإسناد" placeholder="جميع الإسنادات"
                    :options="$assignments->pluck('name', 'id')->all()" />
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="filter-start-date">تاريخ البداية</label>
                    <input id="filter-start-date" type="date" class="form-control" data-datatable-filter="true"
                        data-filter-key="start_date" data-datatable-target="#auctions-table" />
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="filter-end-date">تاريخ النهاية</label>
                    <input id="filter-end-date" type="date" class="form-control" data-datatable-filter="true"
                        data-filter-key="end_date" data-datatable-target="#auctions-table" />
                </div>
                <x-filter.select id="filter-city" filterKey="city" table="#auctions-table" label="المدينة" placeholder="جميع المدن"
                    :options="$cityOptions" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'auctions-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection
