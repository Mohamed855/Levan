@extends('tenants.layouts.layoutMaster')

@section('title', 'إعدادات حالات العميل')

@section('breadcrumb')
    <li class="breadcrumb-item d-flex align-items-center"><a href="#"> إعدادات حالات العميل</a></li>
@endsection

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
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
            index: "{{ tenant_route_name('general-settings.settings-customer-status.index') }}",
            store: "{{ tenant_route_name('general-settings.settings-customer-status.store') }}",
            update: "{{ tenant_route_name('general-settings.settings-customer-status.update', ':id') }}",
            toggleStatus: "{{ tenant_route_name('general-settings.settings-customer-status.toggleStatus', ':id') }}",
            destroy: "{{ tenant_route_name('general-settings.settings-customer-status.destroy', ':id') }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>
    @vite(['resources/assets/js/tenants/general-settings/customer_status/index.js'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- مودال إضافة حالة جديدة -->
    <div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">إضافة حالة عميل جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addForm" class="needs-validation" novalidate>
                        @csrf

                        <div class="mb-3">
                            <label class="form-label" for="add_name">اسم الحالة <span class="text-danger">*</span></label>
                            <input type="text" id="add_name" name="name" class="form-control"
                                placeholder="اسم حالة العميل" required>
                            <div class="invalid-feedback">اسم الحالة مطلوب</div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">إضافة</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- مودال تعديل الحالة -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">تعديل حالة العميل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" class="needs-validation" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_id" name="id">

                        <div class="mb-3">
                            <label class="form-label" for="edit_name">اسم الحالة <span class="text-danger">*</span></label>
                            <input type="text" id="edit_name" name="name" class="form-control"
                                placeholder="اسم حالة العميل" required>
                            <div class="invalid-feedback">اسم الحالة مطلوب</div>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                            <button type="submit" class="btn btn-primary">تحديث</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>
@endsection
