@extends('tenants.layouts.layoutMaster')

@section('title', 'قائمة الأدوار')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
    <style>
        .cursor-pointer {
            cursor: pointer !important;
        }

        .badge.cursor-pointer:hover {
            opacity: 0.8;
            transform: scale(1.02);
            transition: all 0.2s ease;
        }
    </style>
@endsection

@section('page-script')
    <script>
        window.routes = {
            rolesIndex: "{{ tenant_route_name('roles.index') }}",
            getUserRoles: "{{ tenant_route_name('roles.user.get', ['id' => '___ID___']) }}",
            updateUserRoles: "{{ tenant_route_name('roles.user.update', ['id' => '___ID___']) }}",
            updateStatus: "{{ tenant_route_name('roles.user.status', ['userId' => '___ID___']) }}"
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/assets/js/tenants/user-mangment/role/index.js'])
@endsection

@section('content')
    <!-- عرض قائمة الأدوار -->
    <div class="row">
        @foreach ($roles as $role)
            <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                <div class="card h-100 w-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-normal mb-0 text-body">إجمالي {{ $role->users->count() }} مستخدمين</h6>
                            <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                                @foreach ($role->users->take(5) as $user)
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top"
                                        title="{{ $user->name }}" class="avatar pull-up">
                                        <img class="rounded-circle"
                                            src="{{ $user->image ? tasset($user->image) : asset('assets/img/branding/logo.svg') }}"
                                            alt="Avatar" width="40" height="40">
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="d-flex justify-content-between align-items-end">
                            <div class="role-heading">
                                <h5 class="mb-1">{{ $role->name }}</h5>
                                @can('تعديل دور')
                                    <a href="{{ tenant_route_name('roles.edit', $role->id) }}">
                                        <span>تعديل الدور</span>
                                    </a>
                                @endcan
                            </div>
                            @can('حذف دور')
                                @if ($role->id != 1)
                                    <a href="javascript:void(0);" onclick="confirmDelete({{ $role->id }})">
                                        <i class="ti ti-trash ti-md text-danger"></i>
                                    </a>
                                    <form id="delete-form-{{ $role->id }}"
                                        action="{{ tenant_route_name('roles.destroy', $role->id) }}" method="POST"
                                        style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>
        @endforeach


        @php
            use App\Helpers\Tenants\Settings\SettingsHelper;

            $logoImage = SettingsHelper::get('image');
            $logoPath = tasset($logoImage);
        @endphp


        <!-- بطاقة إضافة دور جديد -->
        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
            <div class="card h-100 w-100">
                <div class="card-body d-flex h-100">
                    <div class="row h-100 w-100 m-0">
                        <div class="col-sm-5 d-flex align-items-center justify-content-center p-3">
                            <div class="d-flex align-items-center h-100 justify-content-center mt-sm-0 mt-4">
                                <img src="{{ $logoPath }}" class="img-fluid mt-sm-4 mt-md-0" alt="add-new-roles"
                                    width="83">
                            </div>
                        </div>
                        {{-- @can('إضافة دور') --}}
                        <div class="col-sm-7 d-flex align-items-center justify-content-center">
                            <div class="card-body text-sm-end text-center ps-sm-0">
                                <a href="{{ tenant_route_name('roles.create') }}" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-1"></i> إضافة دور جديد
                                </a>
                            </div>
                        </div>
                        {{-- @endcan --}}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عرض الفلاتر و الجدول الخاص بالمستخدمين -->
    <div class="card" id="card-users">
        <div class="card-body">
            <!-- الفلاتر -->
            <div class="filters row">
                <div class="col-md-6">
                    <label for="filter-status">حالة المستخدم</label>
                    <select id="filter-status" class="form-control select2" data-placeholder="اختر حالة المستخدم">
                        <option value=""></option>
                        <option value="active">نشط</option>
                        <option value="inactive">غير نشط</option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="filter-role">الدور</label>
                    <select id="filter-role" class="form-control select2" data-placeholder="اختر الدور">
                        <option value=""></option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->id }}">{{ $role->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <hr class="mt-10">

            <!-- جدول المستخدمين -->
            <table id="users-table" class="table table-striped table-bordered text-center">
                <thead>
                    <tr>
                        <th></th>
                        <th class="custom-checkbox">
                            <input type="checkbox" id="select-all">
                        </th>
                        <th>الصورة</th>
                        <th>المستخدم</th>
                        <th>البريد الإلكتروني</th>
                        <th>رقم الهاتف</th>
                        <th>الدور</th>
                        <th>الحالة</th>
                        <th>تاريخ الإضافة</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- سيتم تعبئة البيانات بواسطة DataTables -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- مودال تعديل الدور والحالة والفروع -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="updateRoleForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل دور المستخدم والحالة</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>

                    <div class="modal-body">
                        <input type="hidden" id="user-id" name="user_id">

                        <!-- اختيار الدور -->
                        <div class="mb-3">
                            <label for="user-role" class="form-label">
                                الدور
                                <span data-toggle="tooltip" data-placement="top"
                                    title="عند تحديث دور المستخدم، سيتم حذف جميع الصلاحيات المضافة فردياً والصلاحيات المنزوعة، وسيتم الاعتماد على صلاحيات الدور الجديد."
                                    style="color: var(--primary-color);">
                                    <i class="fa fa-question-circle fa-lg" aria-hidden="true"></i>
                                </span>
                            </label>
                            <select id="user-role" name="role" class="form-control select2" required>
                                <option value=""></option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- تحديث الحالة (مخفية حالياً) -->
                        <div class="mb-3" style="display: none;">
                            <label for="user-status" class="form-label">الحالة</label>
                            <select id="user-status" name="status" class="form-control select2" required>
                                <option value=""></option>
                                <option value="active">نشط</option>
                                <option value="inactive">غير نشط</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">الفروع الإضافية المسموح بها</label>
                            <div id="branches-wrapper" class="border rounded p-2"
                                style="max-height: 250px; overflow-y: auto;">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">تحديث</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
