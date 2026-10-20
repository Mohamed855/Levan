@extends('tenant.layouts.layoutMaster')

@section('title', 'إضافة دور جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-script')
    @vite('resources/assets/js/shared/form-validation.js')
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card">
                <h5 class="card-header">إضافة دور جديد</h5>
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

                    <form action="{{ tenant_route_name('roles.store') }}" method="POST" novalidate data-form-validate
                        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                        data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                        data-draft-resource-key="tenant.role-create">
                        @csrf

                        <x-form.controls.input name="name" label="معرّف الدور" placeholder="مثال: collection_manager"
                            required col="col-md-6 mb-3" help="يُحفظ معرفاً إنجليزياً فقط، وتُترجم التسمية في الواجهة." />

                        <div class="mb-3" data-form-field data-field-name="permissions">
                            <label for="permissions" class="form-label">الصلاحيات</label>
                            <div id="permissions-treeview">

                                <!-- تحديد الكل العام -->
                                <div class="form-check mb-3">
                                    <input type="checkbox" name="select_all" value="1" class="form-check-input"
                                        id="select-all" data-validation-skip>
                                    <label class="form-check-label" for="select-all">تحديد الكل</label>
                                </div>

                                <!-- الأقسام -->
                                <div style="display: flex; flex-wrap: wrap; gap: 1.25rem;">
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
                                                                                data-form-control>
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
                            <div class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <x-form.wizard.previous-button :href="tenant_route_name('roles.index')" text="العودة" />
                            <div class="d-flex gap-2">
                            <x-form.wizard.draft-button />
                            <button type="submit" class="btn btn-primary">حفظ</button>
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
        });
    </script>
@endsection
