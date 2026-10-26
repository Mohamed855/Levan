@php
    $isEdit = isset($domain) && $domain->exists;
@endphp

<div class="row g-4">
    <x-form.controls.input 
        name="domain_extension" 
        id="domain_extension" 
        label="الامتداد"
        :value="old('domain_extension', $domain->domain_extension ?? '')" 
        placeholder=".com" 
        :readonly="$isEdit" 
        required 
        col="col-md-6" 
        :help="$isEdit ? 'امتداد النطاق ثابت لا يمكن تعديله.' : 'مثال: .com أو .sa'"
    />

    <x-form.controls.input 
        type="number" 
        step="0.01" 
        name="price" 
        id="price"
        label="السعر" 
        :value="old('price', $domain->price ?? '')" 
        placeholder="2000" 
        required 
        col="col-md-6" 
    />
</div>

<div class="mt-4 d-flex justify-content-between align-items-center">
    <x-form.wizard.previous-button :href="route('admin.settings.domains.index')" text="العودة للقائمة" />
    <div class="d-flex gap-2">
        @if (!$isEdit)
            <x-form.wizard.draft-button />
        @endif
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>
            {{ $isEdit ? 'تعديل' : 'حفظ' }}
        </button>
        <a href="{{ route('admin.settings.domains.index') }}" class="btn btn-secondary">
            <i class="ti ti-x me-1"></i>
            إلغاء
        </a>
    </div>
</div>
