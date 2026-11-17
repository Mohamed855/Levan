@extends('admin.layouts.layoutMaster')

@section('title', 'إدارة الاشتراكات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/vendor/libs/select2/select2.js'])
    {!! $dataTable->scripts() !!}
@endsection


@section('page-style')
    @vite(['resources/assets/css/shared/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            massDelete: "{{ route('admin.mass-delete') }}",
        };
    </script>
    @vite(['resources/assets/js/admin/subscriptions/index.js'])
@endsection

@section('content')

    <x-data-table.page table-id="subscriptions-table">
        <x-slot:stats>
            <x-ui.stat-card col="col-sm-6 col-lg-4 col-xl" title="إجمالي الاشتراكات" :value="$totalSubscriptions ?? 0"
                subtitle="جميع الاشتراكات الصادرة" icon="ti-packages" color="primary" />

            <x-ui.stat-card col="col-sm-6 col-lg-4 col-xl" title="الاشتراكات النشطة" :value="$activeSubscriptions ?? 0"
                subtitle="اشتراكات سارية حالياً" icon="ti-circle-check" color="success" />

            <x-ui.stat-card col="col-sm-6 col-lg-4 col-xl" title="تجديدات قادمة" :value="$queuedSubscriptions ?? 0"
                subtitle="مجدولة ومسبقة الدفع" icon="ti-calendar-plus" color="info" />

            <x-ui.stat-card col="col-sm-6 col-lg-4 col-xl" title="تقارب على الانتهاء" :value="$expiringSubscriptions ?? 0"
                subtitle="تنتهي خلال 30 يوماً" icon="ti-alert-triangle" color="warning" />

            <x-ui.stat-card col="col-sm-6 col-lg-4 col-xl" title="اشتراكات سابقة" :value="$expiredSubscriptions ?? 0"
                subtitle="اشتراكات منتهية أو ملغاة" icon="ti-clock-x" color="danger" />
        </x-slot:stats>

        <x-slot:filters>
            <x-data-table.filters table="#subscriptions-table">
                <x-filter.select id="filter-sub-status" filterKey="status" label="حالة الاشتراك"
                    placeholder="اختر حالة الاشتراك" :options="$filterOptions['statuses'] ?? []" />
                <x-filter.select id="filter-plan" filterKey="plan_id" label="الباقة" placeholder="اختر الباقة"
                    :options="$filterOptions['plans'] ?? []" />
                <x-filter.select id="filter-city" filterKey="city_id" label="مدينة الشركة" placeholder="اختر المدينة"
                    :options="$filterOptions['cities'] ?? []" />
                <x-filter.select id="filter-expiring" filterKey="expiring_in" label="قرب الانتهاء"
                    placeholder="قرب الانتهاء" :options="$filterOptions['expiring'] ?? []" />
                <x-filter.date id="filter-created-from" filterKey="created_from" label="تاريخ الإنشاء من" />
                <x-filter.date id="filter-created-to" filterKey="created_to" label="تاريخ الإنشاء إلى" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'subscriptions-table'],
                false,
            ) !!}
        </x-slot:table>
    </x-data-table.page>

    {{-- Modal: عرض فترات وسجل الاشتراك --}}
    <x-bootstrap.modal id="subPeriodsModal" title="فترات وسجل الاشتراك" icon="ti-timeline" size="modal-lg">
        <div class="mb-3 d-flex justify-content-between align-items-center">
            <div>
                <span class="text-muted small">رقم الاشتراك:</span>
                <span class="fw-bold text-primary ms-1" id="modalSubNumber">-</span>
            </div>
            <div>
                <span class="text-muted small">الشركة:</span>
                <span class="fw-bold text-dark ms-1" id="modalSubCompany">-</span>
            </div>
        </div>
        <div class="table-responsive">
            <table class="table table-sm table-bordered table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>الفترة</th>
                        <th>الباقة</th>
                        <th>تاريخ البدء</th>
                        <th>تاريخ الانتهاء</th>
                        <th>الحالة</th>
                        <th>المستخدمون</th>
                        <th>المبلغ</th>
                    </tr>
                </thead>
                <tbody id="modalSubPeriodsBody">
                    <tr>
                        <td colspan="7" class="text-center py-3 text-muted">جارٍ تحميل البيانات...</td>
                    </tr>
                </tbody>
            </table>
        </div>
        <x-slot:footer>
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                <i class="ti ti-x me-1"></i>إغلاق
            </button>
        </x-slot:footer>
    </x-bootstrap.modal>
@endsection
