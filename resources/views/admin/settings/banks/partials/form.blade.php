@php
    $isEdit = isset($bank) && $bank->exists;
@endphp

<div class="row g-4">
    <!-- اسم البنك -->
    <x-form.controls.input name="bank_name" id="bank_name" label="اسم البنك" :value="old('bank_name', $bank->bank_name ?? '')"
        placeholder="مثال: البنك الأهلي السعودي" required col="col-md-6" />

    <!-- اسم الحساب -->
    <x-form.controls.input name="account_name" id="account_name" label="اسم الحساب" :value="old('account_name', $bank->account_name ?? '')"
        placeholder="مثال: شركة لوتللمزادات" required col="col-md-6" />

    <!-- رقم الآيبان -->
    <x-form.controls.input name="iban" id="iban" label="رقم الآيبان (IBAN)" :value="old('iban', $bank->iban ?? '')"
        placeholder="SA0000000000000000000000" maxlength="24" help="يجب أن يبدأ بـ SA ويتكون من 24 رقم" required
        col="col-md-6" />

    <!-- رقم الحساب -->
    <x-form.controls.input name="account_number" id="account_number" label="رقم الحساب" :value="old('account_number', $bank->account_number ?? '')"
        placeholder="مثال: 1234567890" required col="col-md-6" />

    <!-- أيقونة البنك -->
    <x-form.controls.file name="icon" id="icon" label="أيقونة البنك" :value="$isEdit && $bank->icon ? asset('storage/' . $bank->icon) : null"
        accept="image/jpeg,image/png,image/jpg,image/svg+xml" :help="$isEdit
            ? 'اترك الحقل فارغاً للإبقاء على الصورة الحالية'
            : 'الصيغ المسموحة: JPG, PNG, SVG | الحجم الأقصى: 10MB'" :required="!$isEdit" col="col-md-6" />
</div>

<!-- الأزرار -->
<div class="mt-4 d-flex justify-content-between align-items-center">
    <x-form.wizard.previous-button :href="route('admin.settings.banks.index')" text="العودة للقائمة" />
    <div class="d-flex gap-2">
        @if (!$isEdit)
            <x-form.wizard.draft-button />
        @endif
        <button type="submit" class="btn btn-primary">
            <i class="ti ti-device-floppy me-1"></i>
            {{ $isEdit ? 'حفظ التغييرات' : 'حفظ البنك' }}
        </button>
        <a href="{{ route('admin.settings.banks.index') }}" class="btn btn-secondary">
            <i class="ti ti-x me-1"></i>
            إلغاء
        </a>
    </div>
</div>
