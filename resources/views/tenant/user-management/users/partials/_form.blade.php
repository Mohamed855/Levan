@php
    $steps = [
        ['id' => 'user-info-validation', 'title' => 'بيانات المستخدم'],
    ];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
            <x-ui.validation-errors />

            <form id="wizard-validation-form"
                action="{{ $is_edit ? tenant_route_name('users.update', $user) : tenant_route_name('users.store') }}"
                method="POST"
                enctype="multipart/form-data"
                autocomplete="off"
                data-form-validate
                data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                @if (!$is_edit)
                    data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                    data-draft-resource-key="tenant.user-create"
                @endif
            >
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="user-info-validation" :fields="['name', 'email', 'phone', 'role_id', 'password', 'password_confirmation', 'image']">
                    @include('tenant.user-management.users.partials.steps.user-info', ['user' => $user])

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="tenant_route_name('users.index')" />
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

