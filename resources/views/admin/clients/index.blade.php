@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة العملاء')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
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
    @vite(['resources/assets/js/admin/clients/index.js'])
@endsection

@section('content')
    <x-ui.validation-errors class="mb-4" />

    <x-data-table.page table-id="clients-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-xl" title="إجمالي العملاء" :value="$totalClients ?? $totalCompanies ?? 0"
                subtitle="جميع العملاء المسجلين" icon="ti-building" color="primary" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="العملاء النشطون" :value="$activeClients ?? $activeCompanies ?? 0"
                subtitle="عملاء مفعلون ونشطون" icon="ti-circle-check" color="success" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="العملاء الموقوفون" :value="$suspendedClients ?? $suspendedCompanies ?? 0"
                subtitle="عملاء موقوفون مؤقتاً" icon="ti-ban" color="danger" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="بانتظار التفعيل" :value="$pendingClients ?? $pendingCompanies ?? 0"
                subtitle="عملاء جدد بانتظار الاعتماد" icon="ti-clock-pause" color="warning" />

            <x-ui.stat-card col="col-sm-6 col-xl" title="بدون اشتراك" :value="$withoutSubscriptionClients ?? $withoutSubscriptionCompanies ?? 0"
                subtitle="عملاء لم يشتركوا بعد" icon="ti-package-off" color="info" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#clients-table">
                <x-filter.select id="filter-sub-status" filterKey="subscription_status" label="حالة آخر اشتراك"
                    placeholder="اختر حالة الاشتراك" :options="$filterOptions['subscriptionStatuses'] ?? []" />
                <x-filter.select id="filter-plan" filterKey="plan_id" label="الباقة"
                    placeholder="اختر الباقة" :options="$filterOptions['plans'] ?? []" />
                <x-filter.select id="filter-city" filterKey="city_id" label="المدينة"
                    placeholder="اختر المدينة" :options="$filterOptions['cities'] ?? []" />
                <x-filter.select id="filter-expiring" filterKey="expiring_in" label="انتهاء الاشتراك"
                    placeholder="انتهاء الاشتراك" :options="$filterOptions['expiring'] ?? []" />
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="filter-created-from">من تاريخ الإنشاء</label>
                    <input id="filter-created-from" type="date" class="form-control" data-datatable-filter="true"
                        data-filter-key="created_from" />
                </div>
                <div class="col-md-6 col-lg-3">
                    <label class="form-label" for="filter-created-to">إلى تاريخ الإنشاء</label>
                    <input id="filter-created-to" type="date" class="form-control" data-datatable-filter="true"
                        data-filter-key="created_to" />
                </div>
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100', 'id' => 'clients-table'], false) !!}
        </x-slot:table>
    </x-data-table.page>
@endsection

@push('scripts')
    <script>
        $(function() {
            // Delete client confirmation
            $(document).on('click', '.js-delete-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const form = document.getElementById('delete-form-' + id);

                Swal.fire({
                    title: 'هل أنت متأكد من حذف هذا العميل؟',
                    text: 'سيتم نقل العميل للأرشيف وحذف جميع اشتراكاته نهائياً.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، حذف العميل',
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
