@php
    $is_edit = $is_edit ?? (isset($domain) && $domain->exists);
    $steps = [
        [
            'id' => 'domain-info',
            'title' => 'بيانات امتداد الدومين',
        ],
    ];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="domain-wizard" :steps="$steps">
            <x-ui.validation-errors />

            <form id="domain-wizard-form"
                action="{{ $is_edit ? route('admin.settings.domains.update', $domain->id) : route('admin.settings.domains.store') }}"
                method="POST"
                autocomplete="off"
                data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                @if (!$is_edit)
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-resource-key="admin.domain-create"
                @endif
            >
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="domain-info" :fields="['domain_extension', 'price']">
                    <div class="row g-4">
                        <x-form.controls.input 
                            name="domain_extension" 
                            id="domain_extension" 
                            label="الامتداد"
                            :value="old('domain_extension', $is_edit ? $domain->domain_extension : '')" 
                            placeholder=".com" 
                            :readonly="$is_edit" 
                            required 
                            col="col-md-6" 
                            :help="$is_edit ? 'امتداد النطاق ثابت لا يمكن تعديله.' : 'مثال: .com أو .sa'"
                        />

                        <x-form.controls.input 
                            type="number" 
                            step="0.01" 
                            name="price" 
                            id="price"
                            label="السعر" 
                            :value="old('price', $is_edit ? $domain->price : '')" 
                            placeholder="2000" 
                            required 
                            col="col-md-6" 
                        />
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="route('admin.settings.domains.index')" />
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
