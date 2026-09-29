@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل الدور')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">

                <div class="card-header py-4">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-automatic-gearbox text-warning me-2"></i>
                        تعديل الدور
                    </h6>
                </div>

                <div class="border-1 border-light border-dashed mb-2"></div>

                <div class="card-body">

                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="col-md-12 mb-3">
                            <label for="name" class="form-label">اسم الدور</label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name', $role->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="permissions" class="form-label">الصلاحيات</label>
                            <div id="permissions-treeview">

                                <!-- تحديد الكل العام -->
                                <div class="form-check mb-3">
                                    <input type="checkbox" class="form-check-input" id="select-all">
                                    <label class="form-check-label" for="select-all">تحديد الكل</label>
                                </div>

                                <!-- الأقسام -->
                                <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                                    @foreach ($permissions as $section => $perms)
                                        <div style="flex: 1; min-width: 300px;">
                                            <div class="accordion" id="accordion-{{ $loop->index }}">
                                                <div class="accordion-item">
                                                    <h2 class="accordion-header" id="heading-{{ $loop->index }}">
                                                        <button type="button" class="accordion-button collapsed"
                                                            data-bs-toggle="collapse"
                                                            data-bs-target="#collapse-{{ $loop->index }}"
                                                            aria-expanded="false"
                                                            aria-controls="collapse-{{ $loop->index }}">
                                                            {{ trans()->has('permissions.sections.' . $section) ? __('permissions.sections.' . $section) : (trans()->has('permissions.' . $section) ? __('permissions.' . $section) : $section) }}
                                                        </button>
                                                    </h2>
                                                    <div id="collapse-{{ $loop->index }}"
                                                        class="accordion-collapse collapse"
                                                        aria-labelledby="heading-{{ $loop->index }}"
                                                        data-bs-parent="#accordion-{{ $loop->index }}">
                                                        <div class="accordion-body">

                                                            <!-- تحديد الكل داخل القسم -->
                                                            <div class="form-check mb-2">
                                                                <input type="checkbox"
                                                                    class="form-check-input section-select-all"
                                                                    id="section-select-all-{{ $loop->index }}"
                                                                    data-section-index="{{ $loop->index }}">
                                                                <label class="form-check-label"
                                                                    for="section-select-all-{{ $loop->index }}">
                                                                    تحديد الكل داخل القسم
                                                                </label>
                                                            </div>

                                                            <hr>

                                                            <!-- الصلاحيات -->
                                                            <div style="max-height: 300px; overflow-y: auto;">
                                                                @foreach ($perms as $permission)
                                                                    @if ($permission)
                                                                        <div class="form-check mb-2">
                                                                            <input type="checkbox" name="permissions[]"
                                                                                value="{{ $permission->name }}"
                                                                                class="form-check-input permission-checkbox"
                                                                                id="permission-{{ $permission->id }}"
                                                                                data-section="{{ $loop->parent->index }}"
                                                                                {{ $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                                                            <label class="form-check-label"
                                                                                for="permission-{{ $permission->id }}">
                                                                                {{ trans()->has('permissions.' . $permission->name) ? __('permissions.' . $permission->name) : $permission->name }}
                                                                            </label>
                                                                        </div>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <div class="text-end">
                            <button type="submit" class="btn btn-primary">تحديث</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const selectAllGlobal = document.getElementById('select-all');
            const sectionSelectAllCheckboxes = document.querySelectorAll('.section-select-all');
            const individualCheckboxes = document.querySelectorAll('.permission-checkbox');

            // تحديد الكل العام
            selectAllGlobal.addEventListener('change', function() {
                const isChecked = this.checked;

                // تحديد جميع الصلاحيات
                individualCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                });

                // تحديد جميع أقسام "تحديد الكل داخل القسم"
                sectionSelectAllCheckboxes.forEach(checkbox => {
                    checkbox.checked = isChecked;
                    checkbox.indeterminate = false;
                });
            });

            // تحديد الكل داخل القسم
            sectionSelectAllCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const sectionIndex = this.getAttribute('data-section-index');
                    const childCheckboxes = document.querySelectorAll(
                        `[data-section="${sectionIndex}"]`);
                    const isChecked = this.checked;

                    childCheckboxes.forEach(child => {
                        child.checked = isChecked;
                    });

                    this.indeterminate = false;
                    updateGlobalSelectAll();
                });
            });

            // تحديد الصلاحيات الفردية
            individualCheckboxes.forEach(checkbox => {
                checkbox.addEventListener('change', function() {
                    const sectionIndex = this.getAttribute('data-section');
                    updateSectionSelectAll(sectionIndex);
                    updateGlobalSelectAll();
                });
            });

            // تحديث حالة تحديد الكل داخل القسم
            function updateSectionSelectAll(sectionIndex) {
                const sectionCheckboxes = document.querySelectorAll(`[data-section="${sectionIndex}"]`);
                const sectionSelectAll = document.querySelector(`[data-section-index="${sectionIndex}"]`);

                if (!sectionSelectAll) return;

                const checkedCount = Array.from(sectionCheckboxes).filter(cb => cb.checked).length;
                const totalCount = sectionCheckboxes.length;

                if (checkedCount === 0) {
                    sectionSelectAll.checked = false;
                    sectionSelectAll.indeterminate = false;
                } else if (checkedCount === totalCount) {
                    sectionSelectAll.checked = true;
                    sectionSelectAll.indeterminate = false;
                } else {
                    sectionSelectAll.checked = false;
                    sectionSelectAll.indeterminate = true;
                }
            }

            // تحديث حالة تحديد الكل العام
            function updateGlobalSelectAll() {
                const checkedCount = Array.from(individualCheckboxes).filter(cb => cb.checked).length;
                const totalCount = individualCheckboxes.length;

                if (checkedCount === 0) {
                    selectAllGlobal.checked = false;
                    selectAllGlobal.indeterminate = false;
                } else if (checkedCount === totalCount) {
                    selectAllGlobal.checked = true;
                    selectAllGlobal.indeterminate = false;
                } else {
                    selectAllGlobal.checked = false;
                    selectAllGlobal.indeterminate = true;
                }
            }

            // تحديث حالة جميع checkboxes عند تحميل الصفحة
            function initializeCheckboxStates() {
                // تحديث حالة تحديد الكل لكل قسم
                sectionSelectAllCheckboxes.forEach(checkbox => {
                    const sectionIndex = checkbox.getAttribute('data-section-index');
                    updateSectionSelectAll(sectionIndex);
                });

                // تحديث حالة تحديد الكل العام
                updateGlobalSelectAll();
            }

            // تشغيل التحديث عند تحميل الصفحة
            initializeCheckboxStates();
        });
    </script>
@endsection
