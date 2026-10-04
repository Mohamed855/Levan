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
    <x-ui.validation-errors class="mb-4" />

    <x-data-table.page table-id="companies-table">
        <x-slot:stats>
        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="إجمالي الشركات" :value="$totalCompanies ?? 0"
            subtitle="جميع الشركات المسجلة" icon="ti-building" color="primary" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات النشطة" :value="$activeCompanies ?? 0" subtitle="شركات مفعلة ونشطة"
            icon="ti-circle-check" color="success" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="الشركات الموقوفة" :value="$suspendedCompanies ?? 0"
            subtitle="شركات موقوفة مؤقتاً" icon="ti-ban" color="danger" />

        <x-admin.dashboard-card col="col-sm-6 col-xl-3" title="بانتظار التفعيل" :value="$pendingCompanies ?? 0"
            subtitle="شركات جديدة بانتظار الاعتماد" icon="ti-clock-pause" color="warning" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#companies-table">
                <x-filter.select col="col-md-3" id="filter-status" filterKey="status" label="حالة الشركة"
                    placeholder="اختر حالة الشركة" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select col="col-md-3" id="filter-sub-status" filterKey="subscription_status" label="حالة آخر اشتراك"
                    placeholder="اختر حالة الاشتراك" :options="$filterOptions['subscriptionStatuses'] ?? []" />
                <x-filter.select col="col-md-2" id="filter-plan" filterKey="plan_id" label="الباقة"
                    placeholder="اختر الباقة" :options="$filterOptions['plans'] ?? []" />
                <x-filter.select col="col-md-2" id="filter-city" filterKey="city_id" label="المدينة"
                    placeholder="اختر المدينة" :options="$filterOptions['cities'] ?? []" />
                <x-filter.select col="col-md-2" id="filter-expiring" filterKey="expiring_in" label="انتهاء الاشتراك"
                    placeholder="انتهاء الاشتراك" :options="$filterOptions['expiring'] ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'companies-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection

@push('scripts')
    <script>
        $(function() {

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
