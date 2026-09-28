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

        /* إصلاح تموضع الـ Tooltip بالنسبة للأب المباشر */
        #card-roles .table-responsive {
            overflow: visible !important;
        }
        #card-roles td {
            position: relative;
        }
        #card-roles .avatar-group {
            position: relative !important;
        }
        #card-roles .avatar {
            position: relative !important;
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

    <script>
        (function() {
            function initRoleTooltips() {
                var BS = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
                if (!BS || !BS.Tooltip) return;

                var tooltipElements = document.querySelectorAll('#card-roles [data-bs-toggle="tooltip"]');
                tooltipElements.forEach(function(el) {
                    var inst = BS.Tooltip.getInstance(el);
                    if (inst) inst.dispose();
                    new BS.Tooltip(el, {
                        container: el.parentNode,
                        placement: 'top'
                    });
                });
            }

            window.initRoleTooltips = initRoleTooltips;

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initRoleTooltips);
            } else {
                initRoleTooltips();
            }

            window.addEventListener('load', initRoleTooltips);
            setTimeout(initRoleTooltips, 300);
            setTimeout(initRoleTooltips, 1000);

            document.addEventListener('mouseover', function(e) {
                var trigger = e.target.closest('#card-roles [data-bs-toggle="tooltip"]');
                if (trigger && !trigger._fixedTooltipReady) {
                    trigger._fixedTooltipReady = true;
                    var BS = window.bootstrap || (typeof bootstrap !== 'undefined' ? bootstrap : null);
                    if (BS && BS.Tooltip) {
                        var existing = BS.Tooltip.getInstance(trigger);
                        if (existing) existing.dispose();
                        var tip = new BS.Tooltip(trigger, {
                            container: trigger.parentNode,
                            placement: 'top'
                        });
                        tip.show();
                    }
                }
            }, true);
        })();

        $(document).ready(function() {
            var canAddRole = {{ (auth()->user()?->can('إضافة دور') ?? true) ? 'true' : 'false' }};
            var roleButtons = [];
            if (canAddRole) {
                roleButtons.push({
                    text: '<i class="fas fa-plus-circle me-1"></i> إضافة دور جديد',
                    className: 'btn btn-primary btn-add',
                    action: function() {
                        window.location.href = "{{ tenant_route_name('roles.create') }}";
                    }
                });
            }

            if ($('#roles-table').length) {
                $('#roles-table').DataTable({
                    paging: false,
                    searching: false,
                    info: false,
                    ordering: false,
                    dom: '<"dt-toolbar mb-3"' +
                        '<"dt-toolbar-left">' +
                        '<"dt-toolbar-right"B>' +
                        '>' +
                        'rt',
                    buttons: roleButtons,
                    language: {
                        url: window.assets.arabicDataTable
                    },
                    drawCallback: function() {
                        if (typeof window.initRoleTooltips === 'function') {
                            window.initRoleTooltips();
                        }
                    }
                });
            }
        });
    </script>
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
                                                            src="{{ $user->image ? tasset($user->image) : asset('img/logo.svg') }}"
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
                                        @can('تعديل دور')
                                            <a href="{{ tenant_route_name('roles.edit', $role->id) }}"
                                                class="btn btn-icon btn-label-primary me-1" title="تعديل الدور">
                                                <i class="ti ti-edit"></i>
                                            </a>
                                        @endcan
                                        @can('حذف دور')
                                            @if ($role->id != 1)
                                                <button type="button" class="btn btn-icon btn-label-primary"
                                                    onclick="confirmDelete({{ $role->id }})" title="حذف الدور">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                                <form id="delete-form-{{ $role->id }}"
                                                    action="{{ tenant_route_name('roles.destroy', $role->id) }}" method="POST"
                                                    style="display: none;">
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
