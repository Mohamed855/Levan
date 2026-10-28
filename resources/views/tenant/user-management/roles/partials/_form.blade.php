@php
    $isEdit = $is_edit ?? (isset($role) && $role->exists);
@endphp

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header py-4">
                <h6 class="card-title mb-0">
                    <i class="ti ti-automatic-gearbox text-warning me-2"></i>
                    {{ $isEdit ? 'تعديل الدور' : 'إضافة دور جديد' }}
                </h6>
            </div>

            <div class="border-1 border-light border-dashed mb-2"></div>

            <div class="card-body">
                <x-ui.validation-errors />

                <form action="{{ $isEdit ? tenant_route_name('roles.update', $role->id) : tenant_route_name('roles.store') }}"
                    method="POST" data-form-validate
                    data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                    @if (!$isEdit)
                        data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                        data-draft-resource-key="tenant.role-create"
                    @endif
                >
                    @csrf
                    @if ($isEdit)
                        @method('PUT')
                    @endif

                    <div class="row g-4">
                        <x-form.controls.input name="name" label="اسم الدور"
                            :value="old('name', isset($role) ? \App\Support\AccessLabels::role($role->name) : '')"
                            placeholder="مثال: ContentManager" required col="col-12" />
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center my-3 gap-3">
                            <label for="permissions" class="form-label">الصلاحيات</label>
                            <!-- تحديد الكل العام -->
                            <div class="form-check d-flex align-items-center gap-2">
                                <input type="checkbox" name="select_all" value="1" class="form-check-input" id="select-all"
                                    data-validation-skip>
                                <label class="form-check-label" for="select-all">تحديد الكل</label>
                            </div>
                        </div>
                        <div id="permissions-treeview">
                            <!-- الأقسام -->
                            <div style="display: flex; flex-wrap: wrap; gap: 1.25rem;">
                                @foreach ($permissions as $section => $perms)
                                    <div style="flex: 1; min-width: 300px;">
                                        <div class="accordion" id="accordion-{{ $loop->index }}">
                                            <div class="accordion-item">
                                                <h2 class="accordion-header" id="heading-{{ $loop->index }}">
                                                    <button type="button" class="accordion-button collapsed" data-bs-toggle="collapse"
                                                        data-bs-target="#collapse-{{ $loop->index }}" aria-expanded="false"
                                                        aria-controls="collapse-{{ $loop->index }}">
                                                        {{ \App\Support\AccessLabels::section($section) }}
                                                    </button>
                                                </h2>
                                                <div id="collapse-{{ $loop->index }}" class="accordion-collapse collapse"
                                                    aria-labelledby="heading-{{ $loop->index }}"
                                                    data-bs-parent="#accordion-{{ $loop->index }}">
                                                    <div class="accordion-body">

                                                        <!-- تحديد الكل داخل القسم -->
                                                        <div class="form-check mt-3">
                                                            <input type="checkbox" class="form-check-input section-select-all"
                                                                id="section-select-all-{{ $loop->index }}"
                                                                data-section-index="{{ $loop->index }}">
                                                            <label class="form-check-label" for="section-select-all-{{ $loop->index }}">
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
                                                                            {{ $isEdit && $role->hasPermissionTo($permission->name) ? 'checked' : '' }}>
                                                                        <label class="form-check-label"
                                                                            for="permission-{{ $permission->id }}">
                                                                            {{ \App\Support\AccessLabels::permission($permission->name) }}
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

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        <x-form.wizard.previous-button :href="tenant_route_name('roles.index')" />
                        <div class="d-flex gap-2">
                            @if (!$isEdit)
                                <x-form.wizard.draft-button step="1" />
                            @endif
                            <x-form.wizard.save-button />
                        </div>
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

        if (!selectAllGlobal) return;

        // تحديد الكل العام
        selectAllGlobal.addEventListener('change', function() {
            const isChecked = this.checked;

            individualCheckboxes.forEach(checkbox => {
                checkbox.checked = isChecked;
            });

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

        // تهيئة الحالة الأولية في حالة التعديل
        sectionSelectAllCheckboxes.forEach(checkbox => {
            const sectionIndex = checkbox.getAttribute('data-section-index');
            updateSectionSelectAll(sectionIndex);
        });
        updateGlobalSelectAll();
    });
</script>

