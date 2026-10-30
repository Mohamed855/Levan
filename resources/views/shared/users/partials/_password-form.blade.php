@php
    $steps = [['id' => 'password-info-validation', 'title' => 'تغيير كلمة المرور']];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="password-wizard" :steps="$steps">
            <x-ui.validation-errors />

            <form id="password-wizard-form" action="{{ $action }}" method="POST" novalidate
                data-form-validate data-validation-url="{{ $validationUrl }}">
                @csrf

                <x-form.wizard.step id="password-info-validation"
                    :fields="['current_password', 'new_password', 'new_password_confirmation']">
                    <div class="row g-4">
                        <x-form.controls.password name="current_password" label="كلمة المرور الحالية"
                            required autocomplete="current-password" col="col-12" />

                        <x-form.controls.password name="new_password" label="كلمة المرور الجديدة"
                            required autocomplete="new-password" col="col-md-6"
                            data-validation-dependents="new_password_confirmation" />

                        <x-form.controls.password name="new_password_confirmation" label="تأكيد كلمة المرور الجديدة"
                            required autocomplete="new-password" col="col-md-6" />
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="$backUrl" />
                        <x-form.wizard.save-button />
                    </x-form.wizard.actions>
                </x-form.wizard.step>
            </form>
        </x-form.wizard.stepper>
    </div>
</div>
