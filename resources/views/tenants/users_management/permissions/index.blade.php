@extends('tenants.layouts.layoutMaster')

@section('title', 'قائمة الصلاحيات')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
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

    @if (session('success'))
        <script>
            document.addEventListener("DOMContentLoaded", function() {
                Swal.fire({
                    title: 'تم!',
                    text: '{{ session('success') }}',
                    icon: 'success',
                    confirmButtonText: 'موافق'
                });
            });
        </script>
    @endif

    <!-- Add Permission Modal -->
    <div class="modal fade" id="addPermissionModal" tabindex="-1" aria-labelledby="addPermissionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addPermissionModalLabel">
                        <i class="ti ti-key text-primary me-2"></i>إضافة صلاحية جديدة
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                </div>
                <form id="addPermissionForm" action="{{ tenant_route_name('permissions.store') }}" method="POST">
                    @csrf
                    <div class="modal-body">
                        <p class="text-muted mb-3">صلاحيات يمكن استخدامها وتعيينها لمستخدميك.</p>
                        <div class="mb-3">
                            <label class="form-label" for="modalPermissionName">اسم الصلاحية <span class="text-danger">*</span></label>
                            <input type="text" id="modalPermissionName" name="name" class="form-control"
                                placeholder="مثال: properties.view" required />
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                        <button type="submit" class="btn btn-primary">إضافة الصلاحية</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!--/ Add Permission Modal -->

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
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPermissionModal">
                        <i class="fas fa-plus-circle me-1"></i> إضافة صلاحية جديدة
                    </a>
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
                                <td>{{ $permission->name }}</td>
                                <td>{{ $permission->created_at }}</td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2">
                                        <a href="#" class="btn btn-icon btn-label-primary" data-bs-toggle="modal"
                                            data-bs-target="#editPermissionModal-{{ $permission->id }}">
                                            <i class="ti ti-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-icon btn-label-primary"
                                            onclick="confirmDelete({{ $permission->id }})">
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

                            <!-- Edit Permission Modal -->
                            <div class="modal fade" id="editPermissionModal-{{ $permission->id }}" tabindex="-1"
                                aria-labelledby="editPermissionModalLabel-{{ $permission->id }}" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="editPermissionModalLabel-{{ $permission->id }}">
                                                <i class="ti ti-edit text-primary me-2"></i>تعديل الصلاحية
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                        </div>
                                        <form id="editPermissionForm-{{ $permission->id }}"
                                            action="{{ tenant_route_name('permissions.update', $permission->id) }}"
                                            method="POST">
                                            @csrf
                                            @method('PUT')
                                            <div class="modal-body">
                                                <p class="text-muted mb-3">تعديل تفاصيل الصلاحية الحالية.</p>
                                                <div class="mb-3">
                                                    <label class="form-label" for="modalPermissionName-{{ $permission->id }}">اسم الصلاحية <span class="text-danger">*</span></label>
                                                    <input type="text" id="modalPermissionName-{{ $permission->id }}" name="name"
                                                        class="form-control" value="{{ $permission->name }}" required />
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <button type="submit" class="btn btn-primary">تحديث الصلاحية</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <!--/ Edit Permission Modal -->
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
        function confirmDelete(id) {
            Swal.fire({
                title: 'هل أنت متأكد?',
                text: "لن تتمكن من التراجع عن هذا!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'نعم، احذفه!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('delete-form-' + id).submit();
                }
            });
        }

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

        document.getElementById('rows-per-page').addEventListener('change', function() {
            fetchPermissions(1);
        });

        document.getElementById('filter-search').addEventListener('keyup', function() {
            fetchPermissions(1);
        });

        $(document).on('click', '.pagination a', function(e) {
            e.preventDefault();
            var page = $(this).attr('href').split('page=')[1];
            fetchPermissions(page);
        });
    </script>
@endsection
