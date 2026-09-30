@extends('tenants.layouts.layoutMaster')

@section('title', 'نتائج الاتصال')

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
        $(document).ready(function() {
            // تهيئة Select2
            $('.select2').select2({
                placeholder: function() {
                    return $(this).data('placeholder');
                },
                allowClear: true,
                width: '100%',
                language: 'ar',
                dir: 'rtl'
            });

            var table = $('#setting-table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ tenant_route_name($route . '.index') }}",

                },
                columns: [{
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false,
                    searchable: false,
                    className: 'custom-checkbox',
                    render: function(data, type, full, meta) {
                        return '<input type="checkbox" class="row-checkbox" value="' + full.id +
                            '">';
                    }
                }, {
                    data: 'name',
                    name: 'name'
                }, {
                    data: 'status',
                    name: 'status'
                }, {
                    data: 'created_at',
                    name: 'created_at'
                }, {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false
                }],
                order: [
                    [3, 'desc']
                ],
                dom: '<"dt-toolbar mb-3"' +
                    '<"dt-toolbar-left"' +
                    'l' + // حقل عرض عدد الصفوف
                    '>' +
                    '<"dt-toolbar-right"' +
                    'f' + // حقل البحث
                    'B' + // أزرار التصدير والإضافة
                    '>' +
                    '>' +
                    'rt' +
                    '<"row"' +
                    '<"col-12 d-flex align-items-center justify-content-between"' +
                    'i' + // معلومات الجدول
                    'p' + // أزرار التنقل بين الصفحات
                    '>' +
                    '>',
                buttons: [{
                        extend: 'collection',
                        className: 'btn btn-export btn',
                        text: 'الإجراءات',
                        buttons: [{
                                extend: 'copy',
                                text: 'نسخ'
                            }, {
                                extend: 'excel',
                                text: 'إكسل'
                            }, {
                                text: 'حذف المحدد',
                                className: 'btn btn-default btn-delete-selected',
                                action: function(e, dt, node, config) {
                                    var selectedIds = [];
                                    $('.row-checkbox:checked').each(function() {
                                        selectedIds.push($(this).val());
                                    });

                                    confirmDeleteSelectedmss(selectedIds,
                                        "{{ tenant_route_name('mass-delete') }}",
                                        'SettingsBranch'
                                    );
                                }
                            }

                        ]
                    },

                    {
                        text: '<i class="fas fa-plus-circle me-1"></i> إضافة نتيجة إتصال جديدة',
                        className: 'btn btn-primary btn-add',
                        action: function(e, dt, node, config) {
                            $('#addSectionModal').modal('show');
                        }
                    }

                ],
                language: {
                    url: "{{ asset('assets/json/ar.json') }}"
                },
                responsive: true,
            });
            // تحديد الكل
            $('#select-all').on('click', function() {
                var rows = table.rows({
                    'search': 'applied'
                }).nodes();
                $('input[type="checkbox"]', rows).prop('checked', this.checked);
            });

        });
    </script>
@endsection
<!-- Vendor Scripts -->

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
                        <h4 class="mb-2">إضافة نتيجة إتصال جديدة</h4>
                    </div>
                    <form class="needs-validation row" novalidate id="addForm"
                        action="{{ tenant_route_name($route . '.store') }}" method="POST">
                        @csrf

                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalSettingName">اسم النتيجة</label>
                            <input type="text" id="modalSettingName" name="name" class="form-control"
                                placeholder="اسم النتيجة" required />
                            <div class="valid-feedback"></div>
                            <div class="invalid-feedback"> اسم النتيجة مطلوب</div>
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
                        <h4 class="mb-2">تعديل بيانات النتيجة</h4>
                    </div>
                    <form id="editSettingForm" class="needs-validation row" novalidate method="POST">
                        @csrf
                        @method('PUT')
                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalSettingNameEdit"> اسم النتيجة </label>
                            <input type="text" id="modalSettingNameEdit" name="name" class="form-control"
                                placeholder="اسم النتيجة" required />
                            <div class="valid-feedback"></div>
                            <div class="invalid-feedback">اسم النتيجة مطلوب</div>
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
                        <th>الاسم</th>
                        <th>الحالة</th>
                        <th>تاريخ الإضافة</th>
                        <th>الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- سيتم تعبئة الجدول بواسطة DataTables -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // التعامل مع زر التعديل لفتح المودال وملء البيانات
        $(document).on('click', '.btn-edit', function() {
            var id = $(this).data('id');
            var name = $(this).data('name');
            var editForm = $('#editSettingForm');

            var actionUrl = "{{ tenant_route_name($route . '.update', ':id') }}".replace(':id', id);

            editForm.attr('action', actionUrl);
            $('#modalSettingNameEdit').val(name);
            $('#editSectionModal').modal('show');
        });


        // التعامل مع تغيير الحالة عند النقر على الشارة
        $(document).on('click', '.status-toggle', function() {
            var badge = $(this);
            var id = badge.data('id');

            $.ajax({
                url: "{{ tenant_route_name($route . '.edit', ':id') }}".replace(':id', id),
                method: 'get',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        if (response.status === 'active') {
                            badge.removeClass('bg-danger').addClass('bg-success').text('نشط');
                        } else if (response.status === 'inactive') {
                            badge.removeClass('bg-success').addClass('bg-danger').text('غير نشط');
                        }
                        toastr.success('تم تغيير الحالة بنجاح.');
                    } else {
                        toastr.error('فشل في تغيير الحالة.');
                    }
                },
                error: function(xhr) {
                    toastr.error('حدث خطأ أثناء تغيير الحالة.');
                }
            });
        });
    </script>
@endsection
