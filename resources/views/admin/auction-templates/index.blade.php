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
            <x-ui.stat-card title="إجمالي القوالب" :value="$statistics['total']" subtitle="جميع القوالب المسجلة" icon="ti-files"
                color="primary" />
            <x-ui.stat-card title="قوالب البروشور" :value="$statistics['brochure']" subtitle="قوالب الكتيبات التعريفية"
                icon="ti-file-description" color="info" />
            <x-ui.stat-card title="اللوحات الإعلانية" :value="$statistics['billboard']" subtitle="قوالب اللوحات الخارجية" icon="ti-ad"
                color="warning" />
            <x-ui.stat-card title="السوشيال ميديا" :value="$statistics['social']" subtitle="قوالب المنشورات والشبكات"
                icon="ti-brand-instagram" color="success" />
            <x-ui.stat-card title="قوالب معطلة" :value="$statistics['inactive']" subtitle="غير متاحة للمستأجرين" icon="ti-ban"
                color="danger" />
        </x-slot:stats>

        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <x-slot:filters>
            <x-data-table.filters table="#auction-templates-table">
                <x-filter.select id="filter-type" filterKey="type" label="نوع القالب" placeholder="جميع الأنواع"
                    :options="$typeFilterOptions ?? []" />
                <x-filter.select id="filter-status" filterKey="is_active" label="حالة القالب" placeholder="جميع الحالات"
                    :options="$statusFilterOptions ?? []" />
            </x-data-table.filters>
        </x-slot:filters>

        <x-slot:table>
            {!! $dataTable->table(
                ['class' => 'table table-striped table-bordered w-100', 'id' => 'auction-templates-table'],
                false,
            ) !!}
        </x-slot:table>
    </x-data-table.page>

    {{-- Modal to inspect extracted template fields --}}
    <div class="modal fade" id="templateFieldsModal" tabindex="-1" aria-labelledby="templateFieldsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="templateFieldsModalLabel">
                        <i class="ti ti-list-check me-2 text-primary"></i>تفاصيل حقول القالب: <span id="modalTemplateName"
                            class="text-primary fw-bold"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted">إجمالي الحقول المستخرجة: <span id="modalTotalFields"
                                class="badge bg-label-primary font-monospace"></span></span>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover border">
                            <thead class="table-light">
                                <tr>
                                    <th>اسم الحقل في PDF</th>
                                    <th>البيان المقابل</th>
                                    <th class="text-center">الأهمية</th>
                                    <th class="text-center">حالة الحقل في القالب</th>
                                </tr>
                            </thead>
                            <tbody id="modal-fields-tbody">
                                <tr>
                                    <td colspan="4" class="text-center py-4">جاري التحميل...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div id="modal-extra-fields" class="mt-3 d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إغلاق</button>
                </div>
            </div>
        </div>
    </div>
@endsection
