@php
    $isEdit = isset($permission) && $permission->exists;
@endphp

<div class="row g-4 mb-4">
    <x-form.controls.input 
        name="name" 
        id="name" 
        label="اسم الصلاحية" 
        :value="old('name', $permission->name ?? '')"
        placeholder="مثال: users.view"
        required 
        col="col-md-6" 
    />
</div>

<div class="d-flex justify-content-between align-items-center mt-4">
    <x-form.wizard.previous-button :href="tenant_route_name('permissions.index')" text="العودة" />
    <div class="d-flex gap-2">
        @if (!$isEdit)
            <x-form.wizard.draft-button />
        @endif
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>
            {{ $isEdit ? 'تحديث الصلاحية' : 'إضافة الصلاحية' }}
        </button>
    </div>
</div>
