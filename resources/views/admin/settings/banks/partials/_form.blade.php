@php
    $is_edit = $is_edit ?? (isset($bank) && $bank->exists);
    $steps = [
        [
            'id' => 'bank-info',
            'title' => 'بيانات البنك',
        ],
    ];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="bank-wizard" :steps="$steps">
            <x-ui.validation-errors />

            <form id="bank-wizard-form"
                action="{{ $is_edit ? route('admin.settings.banks.update', $bank->id) : route('admin.settings.banks.store') }}"
                method="POST"
                enctype="multipart/form-data"
                autocomplete="off"
                data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                @if (!$is_edit)
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-file-url="{{ route('admin.wizard-drafts.file.destroy') }}"
                    data-draft-resource-key="admin.bank-create"
                @endif
            >
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="bank-info" :fields="['bank_name', 'account_name', 'iban', 'account_number', 'icon']">
                    <div class="row g-4">
                        <!-- اسم البنك -->
                        <x-form.controls.input name="bank_name" id="bank_name" label="اسم البنك"
                            :value="old('bank_name', $is_edit ? $bank->bank_name : '')"
                            placeholder="مثال: البنك الأهلي السعودي" required col="col-md-6" />

                        <!-- اسم الحساب -->
                        <x-form.controls.input name="account_name" id="account_name" label="اسم الحساب"
                            :value="old('account_name', $is_edit ? $bank->account_name : '')"
                            placeholder="مثال: شركة ليفان للمزادات" required col="col-md-6" />

                        <!-- رقم الآيبان -->
                        <x-form.controls.input name="iban" id="iban" label="رقم الآيبان (IBAN)"
                            :value="old('iban', $is_edit ? $bank->iban : '')"
                            placeholder="SA0000000000000000000000" maxlength="24"
                            help="يجب أن يبدأ بـ SA ويتكون من 24 رقم" required col="col-md-6" />

                        <!-- رقم الحساب -->
                        <x-form.controls.input name="account_number" id="account_number" label="رقم الحساب"
                            :value="old('account_number', $is_edit ? $bank->account_number : '')"
                            placeholder="مثال: 1234567890" required col="col-md-6" />

                        <!-- أيقونة البنك -->
                        <x-form.controls.image-upload name="icon" id="icon" label="أيقونة البنك"
                            :value="$is_edit && $bank->icon ? asset('storage/' . $bank->icon) : null"
                            accept="image/jpeg,image/png,image/jpg,image/svg+xml"
                            :help="$is_edit ? 'اترك الحقل فارغاً للإبقاء على الصورة الحالية' : 'الصيغ المسموحة: JPG, PNG, SVG | الحجم الأقصى: 10MB'"
                            :required="!$is_edit" col="col-md-6" />
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="route('admin.settings.banks.index')" />
                        <div class="d-flex gap-2">
                            @if (!$is_edit)
                                <x-form.wizard.draft-button step="1" />
                            @endif
                            <x-form.wizard.save-button />
                        </div>
                    </x-form.wizard.actions>
                </x-form.wizard.step>
            </form>
        </x-form.wizard.stepper>
    </div>
</div>
