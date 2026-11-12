@props([
    'justify' => 'between',
])

@php
    $steps = [['id' => 'password-info-validation', 'title' => 'تغيير كلمة المرور']];
    $passwordFields = ($requiredPasswordChange ?? false)
        ? ['new_password', 'new_password_confirmation']
        : ['current_password', 'new_password', 'new_password_confirmation'];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="password-wizard" :steps="$steps">

            <form id="password-wizard-form" action="{{ $action }}" method="POST" novalidate data-form-validate
                data-validation-url="{{ $validationUrl }}">
                @csrf

                <x-form.wizard.step id="password-info-validation" :fields="$passwordFields">
                    <div class="row g-4">
                        @unless ($requiredPasswordChange ?? false)
                            <x-form.controls.password name="current_password" label="كلمة المرور الحالية" required
                                autocomplete="current-password" col="col-12" />
                        @endunless

                        <x-form.controls.password name="new_password" label="كلمة المرور الجديدة" required
                            autocomplete="new-password" col="col-md-6"
                            data-validation-dependents="new_password_confirmation" />

                        <x-form.controls.password name="new_password_confirmation" label="تأكيد كلمة المرور الجديدة"
                            required autocomplete="new-password" col="col-md-6" />
                    </div>

                    <x-form.wizard.actions :justify="$justify">
                        @unless ($requiredPasswordChange ?? false)
                            <x-form.wizard.previous-button :href="$backUrl" />
                        @endunless
                        <x-form.wizard.save-button />
                    </x-form.wizard.actions>
                </x-form.wizard.step>
            </form>
        </x-form.wizard.stepper>
    </div>
</div>
