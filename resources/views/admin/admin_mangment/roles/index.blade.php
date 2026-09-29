@extends('admin.layouts.layoutMaster')

@section('title', ' المستخدمين و الأدوار')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss', 'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/moment/moment.js', 'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
    {!! $dataTable->scripts() !!}
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
    {{-- #card-roles tooltip overflow fix is now in resources/scss/shared/_tables.scss --}}
@endsection

@section('page-script')
    <script>
        // Route data — must stay in Blade (dynamic per-dashboard)
        window.routes = {
            rolesIndex:      "{{ route('admin.roles.index') }}",
            getUserRoles:    "{{ route('admin.roles.get-user-roles', ':id') }}",
            updateUserRoles: "{{ route('admin.roles.update-user-role', ':id') }}",
            toggleStatus:    "{{ route('admin.users.toggle-status', ':id') }}",
        };
        window.assets = {
            arabicDataTable: "{{ asset('assets/json/ar.json') }}",
        };
        window.permissions = {
            canAddRole: {{ (auth()->user()?->can('roles.create') ?? true) ? 'true' : 'false' }},
        };
    </script>
    @vite(['resources/assets/js/admin/admin_mangment/roles/index.js'])
@endsection

@section('content')
    <!-- جدول قائمة الأدوار -->
    <div class="card mb-4" id="card-roles">
        <div class="card-body">
            <div class="table-responsive">
                <table id="roles-table" class="table table-striped table-bordered text-center w-100">
                    <thead>
                        <tr>
                            <th style="width: 70px;">#</th>
                            <th>اسم الدور</th>
                            <th>المستخدمين</th>
                            <th>تاريخ الإضافة</th>
                            <th style="width: 140px;">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($roles as $index => $role)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <span class="fw-semibold text-heading">{{ $role->name }}</span>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center justify-content-center gap-2">
                                        <span class="badge bg-label-primary">{{ $role->users->count() }} مستخدم</span>
                                        @if ($role->users->count() > 0)
                                            <ul class="list-unstyled d-flex align-items-center avatar-group mb-0">
                                                @foreach ($role->users->take(4) as $user)
                                                    <li data-bs-toggle="tooltip"
                                                        data-bs-placement="top" data-bs-title="{{ $user->name }}"
                                                        class="avatar avatar-xs pull-up">
                                                        <img class="rounded-circle"
                                                            src="{{ $user->image ? asset('storage/' . $user->image) : asset('img/logo.svg') }}"
                                                            alt="Avatar" width="26" height="26">
                                                    </li>
                                                @endforeach
                                            </ul>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    {{ $role->created_at ? $role->created_at->locale('ar')->translatedFormat('d F Y') : '-' }}
                                </td>
                                <td>
                                    <div class="d-flex justify-content-center gap-1">
                                        @can('roles.edit')
                                            <a href="{{ route('admin.roles.edit', $role->id) }}"
                                                class="btn btn-icon btn-label-primary me-1" title="تعديل الدور">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        @endcan
                                        @can('roles.delete')
                                            @if ($role->id != 1)
                                                <button type="button" class="btn btn-icon btn-label-primary"
                                                    onclick="confirmDelete({{ $role->id }})" title="حذف الدور">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                                <form id="delete-form-{{ $role->id }}"
                                                    action="{{ route('admin.roles.destroy', $role->id) }}" method="POST"
                                                    class="d-hidden">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            @endif
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- عرض الفلاتر و الجدول الخاص بالمستخدمين -->
    <div class="card" id="card-users">
        <div class="card-body">
            <!-- الفلاتر -->
            <div class="filters row g-3">
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
            {!! $dataTable->table(['class' => 'table table-striped table-bordered w-100'], false) !!}
        </div>
    </div>

    <!-- مودال تعديل الدور  -->
    <div class="modal fade" id="editRoleModal" tabindex="-1" aria-labelledby="editRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <form id="updateRoleForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">تعديل دور المستخدم </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="user-id" name="user_id">

                        <!-- اختيار الدور -->
                        <div class="mb-3">
                            <label for="user-role" class="form-label">
                                الدور

                            </label>
                            <select id="user-role" name="role" class="form-control select2" required>
                                <option value=""></option>
                                @foreach ($roles as $role)
                                    <option value="{{ $role->id }}">{{ $role->name }}</option>
                                @endforeach
                            </select>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">حفظ التغييرات</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

@endsection
