@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة الشركات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Dashboard Cards --}}
    <div class="row g-4 mb-4">
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي الشركات" :value="$totalCompanies ?? 0"
            subtitle="جميع الشركات المسجلة" icon="ti-building" color="primary" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات النشطة" :value="$activeCompanies ?? 0" subtitle="شركات مفعلة ونشطة"
            icon="ti-circle-check" color="success" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات الموقوفة" :value="$suspendedCompanies ?? 0"
            subtitle="شركات موقوفة مؤقتاً" icon="ti-ban" color="danger" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="بانتظار التفعيل" :value="$pendingCompanies ?? 0"
            subtitle="شركات جديدة بانتظار الاعتماد" icon="ti-clock-pause" color="warning" />
    </div>

    {{-- Filters Card --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-4">
                <x-filter.select col="col-md-3" id="filter-status" label="حالة الشركة"
                    placeholder="اختر حالة الشركة" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-sub-status" label="حالة آخر اشتراك"
                    placeholder="اختر حالة الاشتراك" :options="$filterOptions['subscriptionStatuses'] ?? []" />
                <x-filter.select col="col-md-2" id="filter-plan" label="الباقة"
                    placeholder="اختر الباقة" :options="$filterOptions['plans'] ?? []" />
                <x-filter.select col="col-md-2" id="filter-city" label="المدينة"
                    placeholder="اختر المدينة" :options="$filterOptions['cities'] ?? []" />
                <x-filter.select col="col-md-2" id="filter-expiring" label="انتهاء الاشتراك"
                    placeholder="انتهاء الاشتراك" :options="$filterOptions['expiring'] ?? []" />
            </div>
            <div class="d-flex justify-content-end mt-3 gap-2">
                <button type="button" id="btn-reset-filters" class="btn btn-secondary">
                    <i class="ti ti-refresh me-1"></i>إعادة تعيين
                </button>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'companies-table'], false) !!}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('.select2').select2({
                allowClear: true,
                dir: 'rtl'
            });

            const tableId = 'companies-table';

            function getTable() {
                return window.LaravelDataTables ? window.LaravelDataTables[tableId] : null;
            }

            function applyFilters() {
                const table = getTable();
                if (!table) return;

                const url = new URL(table.ajax.url(), window.location.origin);
                const status = $('#filter-status').val();
                const subStatus = $('#filter-sub-status').val();
                const plan = $('#filter-plan').val();
                const city = $('#filter-city').val();
                const expiring = $('#filter-expiring').val();

                if (status) url.searchParams.set('status', status);
                else url.searchParams.delete('status');
                if (subStatus) url.searchParams.set('subscription_status', subStatus);
                else url.searchParams.delete('subscription_status');
                if (plan) url.searchParams.set('plan_id', plan);
                else url.searchParams.delete('plan_id');
                if (city) url.searchParams.set('city_id', city);
                else url.searchParams.delete('city_id');
                if (expiring) url.searchParams.set('expiring_in', expiring);
                else url.searchParams.delete('expiring_in');

                table.ajax.url(url.toString()).load();
            }

            $('#filter-status, #filter-sub-status, #filter-plan, #filter-city, #filter-expiring').on('change',
                applyFilters);

            $('#btn-reset-filters').on('click', function() {
                $('#filter-status, #filter-sub-status, #filter-plan, #filter-city, #filter-expiring').val(
                    '').trigger('change');
            });

            // Delete company confirmation
            $(document).on('click', '.js-delete-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const form = document.getElementById('delete-form-' + id);

                Swal.fire({
                    title: 'هل أنت متأكد من حذف هذه الشركة؟',
                    text: 'سيتم نقل الشركة للأرشيف وحذف جميع اشتراكاتها نهائياً.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، حذف الشركة',
                    cancelButtonText: 'إلغاء',
                    customClass: {
                        confirmButton: 'btn btn-danger me-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed && form) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
