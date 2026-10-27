@extends('tenant.layouts.layoutMaster')

@section('title', 'قائمة الصلاحيات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('content')
    <x-ui.validation-errors />

    <!-- Add Permission Modal -->
    <x-bootstrap.modal id="addPermissionModal" title="إضافة صلاحية جديدة" icon="ti-key">
        <form id="addPermissionForm" action="{{ tenant_route_name('permissions.store') }}" method="POST">
            @csrf
            <p class="text-muted mb-3">صلاحيات يمكن استخدامها وتعيينها لمستخدميك.</p>
            <div class="mb-3">
                <label class="form-label" for="modalPermissionName">اسم الصلاحية <span class="text-danger">*</span></label>
                <input type="text" id="modalPermissionName" name="name" class="form-control"
                    placeholder="مثال: properties.view" required />
            </div>
            <x-slot:footer>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary">إضافة الصلاحية</button>
            </x-slot:footer>
        </form>
    </x-bootstrap.modal>
    <!--/ Add Permission Modal -->

    <!-- Edit Permission Modal (Single instance outside loop) -->
    <x-bootstrap.modal id="editPermissionModal" title="تعديل الصلاحية" icon="ti-edit">
        <form id="editPermissionForm" method="POST" action="">
            @csrf
            @method('PUT')
            <p class="text-muted mb-3">تعديل تفاصيل الصلاحية الحالية.</p>
            <div class="mb-3">
                <label class="form-label" for="editModalPermissionName">اسم الصلاحية <span class="text-danger">*</span></label>
                <input type="text" id="editModalPermissionName" name="name" class="form-control" required />
            </div>
            <x-slot:footer>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                <button type="submit" class="btn btn-primary">تحديث الصلاحية</button>
            </x-slot:footer>
        </form>
    </x-bootstrap.modal>
    <!--/ Edit Permission Modal -->

    <div class="card">
        <div class="card-header">
            <div class="row align-items-center mb-3">
                <div class="col-md-2">
                    <input type="text" id="filter-search" class="form-control" placeholder="ابحث هنا ">
                </div>
                <div class="col-md-1">
                    <select id="rows-per-page" class="form-select" style="width: 100%;">
                        <x-form.controls.enum-options :enum="\App\Enums\Shared\RowsPerPage::class" />
                    </select>
                </div>
                <div class="col-md-6 text-end">
                    <p>&nbsp;</p>
                </div>
                <div class="col-md-3 text-end">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPermissionModal">
                        <i class="fas fa-plus-circle me-1"></i> إضافة صلاحية جديدة
                    </button>
                </div>
            </div>

        </div>
        <div class="table-responsive text-nowrap">
            <table class="table">
                <thead>
                    <tr>
                        <th> م </th>
                        <th>اسم الصلاحية</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="permission-table-body">
                    @if ($permissions->isEmpty())
                        <tr>
                            <td colspan="4" class="text-center">لاتوجد بيانات متاحة</td>
                        </tr>
                    @else
                        @foreach ($permissions as $index => $permission)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ \App\Support\AccessLabels::permission($permission->name) }}</td>
                                <td>{{ $permission->created_at }}</td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <button type="button" class="btn btn-icon btn-primary js-edit-permission-btn"
                                            data-id="{{ $permission->id }}"
                                            data-name="{{ $permission->name }}"
                                            data-action="{{ tenant_route_name('permissions.update', $permission->id) }}"
                                            title="تعديل">
                                            <i class="ti ti-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-icon btn-danger"
                                            onclick="confirmDelete({{ $permission->id }})"
                                            title="حذف">
                                            <i class="ti ti-trash"></i>
                                        </button>
                                    </div>
                                    <form id="delete-form-{{ $permission->id }}"
                                        action="{{ tenant_route_name('permissions.destroy', $permission->id) }}"
                                        method="POST" style="display: none;">
                                        @csrf
                                        @method('DELETE')
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    </div>

    <br>
    <div class="d-flex justify-content-start align-items-left">
        <div>
            {{ $permissions->links('pagination::bootstrap-4') }}
        </div>
    </div>

    <script>
        // فتح مودال التعديل وملء البيانات ديناميكياً
        $(document).on('click', '.js-edit-permission-btn', function (e) {
            e.preventDefault();
            const id = $(this).data('id');
            const name = $(this).data('name');
            const action = $(this).data('action');

            const form = document.getElementById('editPermissionForm');
            if (form) form.action = action;

            const input = document.getElementById('editModalPermissionName');
            if (input) input.value = name;

            const modalEl = document.getElementById('editPermissionModal');
            if (modalEl) {
                bootstrap.Modal.getOrCreateInstance(modalEl).show();
            }
        });

        // الفلترة والبحث الحي
        function fetchPermissions(page = 1) {
            var search = document.getElementById('filter-search').value;
            var rows = document.getElementById('rows-per-page').value;

            $.ajax({
                url: '{{ tenant_route_name('permissions.index') }}',
                method: 'GET',
                data: {
                    search: search,
                    page: page,
                    rows: rows
                },
                success: function(response) {
                    $('#permission-table-body').html($(response.view).find('#permission-table-body').html());
                    $('.pagination').html($(response.view).find('.pagination').html());
                },
                error: function(xhr) {
                    console.error(xhr.responseText);
                }
            });
        }

        document.getElementById('rows-per-page')?.addEventListener('change', function() {
            fetchPermissions(1);
        });

        document.getElementById('filter-search')?.addEventListener('keyup', function() {
            fetchPermissions(1);
        });

        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            fetchPermissions(page);
        });
    </script>
@endsection
