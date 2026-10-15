@extends('tenants.layouts.layoutMaster')

@section('title', 'إعدادات حالات العميل')

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
    <style>
        .color-picker-container {
            position: relative;
        }

        .color-input-wrapper {
            display: flex;
            align-items: center;
            border: 1px solid #d9dee3;
            border-radius: 0.375rem;
            padding: 0.5rem;
            background-color: #fff;
        }

        .color-display {
            width: 2.5rem;
            height: 2.5rem;
            border-radius: 50%;
            border: 2px solid #ddd;
            margin-left: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .color-display:hover {
            transform: scale(1.1);
            border-color: #007bff;
        }

        .color-input {
            opacity: 0;
            position: absolute;
            width: 2.5rem;
            height: 2.5rem;
            cursor: pointer;
        }

        .color-text {
            flex: 1;
            border: none;
            outline: none;
            background: transparent;
            font-family: monospace;
            font-weight: 500;
        }

        .color-preview {
            transition: all 0.3s ease;
        }

        .color-preview:hover {
            transform: scale(1.2);
        }
    </style>
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

            // تهيئة Color Picker
            function initColorPicker() {
                $('.color-input').on('input', function() {
                    const colorValue = $(this).val();
                    const container = $(this).closest('.color-input-wrapper');
                    container.find('.color-display').css('background-color', colorValue);
                    container.find('.color-text').val(colorValue);
                });

                $('.color-display').on('click', function() {
                    $(this).siblings('.color-input').click();
                });

                $('.color-text').on('input', function() {
                    const colorValue = $(this).val();
                    if (/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/.test(colorValue)) {
                        const container = $(this).closest('.color-input-wrapper');
                        container.find('.color-display').css('background-color', colorValue);
                        container.find('.color-input').val(colorValue);
                    }
                });
            }

            initColorPicker();

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
                    data: 'color_preview',
                    name: 'color_preview',
                    title: 'اللون',
                    orderable: false,
                    searchable: false,
                    class: 'text-center'
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
                    [4, 'desc']
                ],
                dom: '<"dt-toolbar mb-3"' +
                    '<"dt-toolbar-left"' +
                    'l' +
                    '>' +
                    '<"dt-toolbar-right"' +
                    'f' +
                    'B' +
                    '>' +
                    '>' +
                    'rt' +
                    '<"row"' +
                    '<"col-12 d-flex align-items-center justify-content-between"' +
                    'i' +
                    'p' +
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
                                    'SettingsCustomerStatus'
                                );
                            }
                        }]
                    },
                    {
                        text: '<i class="fas fa-plus-circle me-1"></i> إضافة حالة جديدة',
                        className: 'btn btn-primary btn-add',
                        action: function(e, dt, node, config) {
                            $('#addSectionModal').modal('show');
                        }
                    }
                ],
                language: {
                    url: "{{ asset('static/i18n/ar.json') }}"
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
                        <h4 class="mb-2">إضافة حالة عميل جديدة</h4>
                    </div>
                    <form class="needs-validation row" novalidate id="addForm"
                        action="{{ tenant_route_name($route . '.store') }}" method="POST">
                        @csrf

                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalSettingName">اسم حالة العميل</label>
                            <input type="text" id="modalSettingName" name="name" class="form-control"
                                placeholder="اسم حالة العميل" required />
                            <div class="valid-feedback"></div>
                            <div class="invalid-feedback"> اسم حالة العميل مطلوب</div>
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalSettingColor">لون الحالة</label>
                            <div class="color-picker-container">
                                <div class="color-input-wrapper">
                                    <div class="color-display" style="background-color: #007bff;"></div>
                                    <input type="color" class="color-input" name="color" value="#007bff" required>
                                    <input type="text" class="color-text" value="#007bff" placeholder="#007bff"
                                        maxlength="7">
                                </div>
                            </div>
                            <div class="valid-feedback"></div>
                            <div class="invalid-feedback">لون الحالة مطلوب</div>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editSectionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-simple">
            <div class="modal-content">
                <div class="modal-body">
                    <button type="button" class="btn-close btn-pinned" data-bs-dismiss="modal" aria-label="Close"></button>
                    <div class="text-center mb-6">
                        <h4 class="mb-2">تعديل بيانات حالة العميل</h4>
                    </div>
                    <form id="editSettingForm" class="needs-validation row" novalidate method="POST">
                        @csrf
                        @method('PUT')

                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalSettingNameEdit"> اسم حالة العميل </label>
                            <input type="text" id="modalSettingNameEdit" name="name" class="form-control"
                                placeholder="اسم حالة العميل" required />
                            <div class="valid-feedback"></div>
                            <div class="invalid-feedback">اسم حالة العميل مطلوب</div>
                        </div>

                        <div class="col-12 mb-4">
                            <label class="form-label" for="modalSettingColorEdit">لون الحالة</label>
                            <div class="color-picker-container">
                                <div class="color-input-wrapper">
                                    <div class="color-display" id="editColorDisplay" style="background-color: #007bff;">
                                    </div>
                                    <input type="color" class="color-input" id="modalSettingColorEdit" name="color"
                                        value="#007bff" required>
                                    <input type="text" class="color-text" id="editColorText" value="#007bff"
                                        placeholder="#007bff" maxlength="7">
                                </div>
                            </div>
                            <div class="valid-feedback"></div>
                            <div class="invalid-feedback">لون الحالة مطلوب</div>
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
                        <th>اسم حالة العميل</th>
                        <th>اللون</th>
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
            var color = $(this).data('color') || '#007bff';
            var editForm = $('#editSettingForm');

            var actionUrl = "{{ tenant_route_name($route . '.update', ':id') }}".replace(':id', id);

            editForm.attr('action', actionUrl);
            $('#modalSettingNameEdit').val(name);
            $('#modalSettingColorEdit').val(color);
            $('#editColorText').val(color);
            $('#editColorDisplay').css('background-color', color);
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
                            badge.removeClass('bg-secondary').css({
                                'background-color': response.color,
                                'border-color': response.color
                            }).text('نشط');
                        } else if (response.status === 'inactive') {
                            badge.removeClass().addClass('badge bg-secondary status-toggle').css({
                                'background-color': '',
                                'border-color': ''
                            }).text('غير نشط');
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
