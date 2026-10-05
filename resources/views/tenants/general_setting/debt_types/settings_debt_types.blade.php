{{-- resources/views/general_setting/settings_debt_types.blade.php --}}

@extends('tenants.layouts.layoutMaster')

@section('title', 'إعدادات أنواع المديونية')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/cleavejs/cleave.js', 'resources/assets/vendor/libs/cleavejs/cleave-phone.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('page-script')
    <script>
        window.debtTypesRoutes = {
            index: "{{ tenant_route_name($route . '.index') }}",
            edit: "{{ tenant_route_name($route . '.edit', ':id') }}",
            update: "{{ tenant_route_name($route . '.update', ':id') }}",
            massDelete: "{{ tenant_route_name('mass-delete') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
        window.assetsPath = "{{ asset('static') }}";
    </script>
    @vite(['resources/assets/js/tenants/general-settings/debt-types/debt-types.js'])
@endsection

@section('content')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Add setting Modal -->
    <div class="modal fade" id="addSectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-simple">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="mb-2">إضافة نوع مديونية جديد</h4>
                    </div>
                    <form class="needs-validation row" novalidate id="addForm"
                        action="{{ tenant_route_name($route . '.store') }}" method="POST">
                        @csrf

                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalSettingName">اسم نوع المديونية</label>
                            <input type="text" id="modalSettingName" name="name" class="form-control"
                                placeholder="اسم نوع المديونية" required />
                            <div class="valid-feedback"></div>
                            <div class="invalid-feedback"> اسم نوع المديونية مطلوب</div>
                        </div>
                        <div class="col-12 text-center demo-vertical-spacing">
                            <button type="submit" class="btn btn-primary me-4">إضافة</button>
                            <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal"
                                aria-label="Close">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!--/ Add setting Modal -->

    <div class="modal fade" id="editSectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-simple">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="mb-2">تعديل بيانات نوع المديونية</h4>
                    </div>
                    <form id="editSettingForm" class="needs-validation row" novalidate method="POST">
                        @csrf
                        @method('PUT')
                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalSettingNameEdit"> اسم نوع المديونية </label>
                            <input type="text" id="modalSettingNameEdit" name="name" class="form-control"
                                placeholder="اسم نوع المديونية" required />
                            <div class="valid-feedback"></div>
                            <div class="invalid-feedback">اسم نوع المديونية مطلوب</div>
                        </div>
                        <div class="col-12 text-center demo-vertical-spacing">
                            <button type="submit" class="btn btn-primary me-4">تحديث</button>
                            <button type="reset" class="btn btn-secondary" data-bs-dismiss="modal"
                                aria-label="Close">إلغاء</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <table id="setting-table" class="table table-striped table-bordered">
                <thead>
                    <tr>
                        <th class="custom-checkbox">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th>اسم نوع المديونية</th>
                        <th>الحالة</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                </tbody>
            </table>
        </div>
    </div>
@endsection
