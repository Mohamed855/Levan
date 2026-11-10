@php
    $is_profile = $is_profile ?? false;
    $steps = [['id' => 'user-info-validation', 'title' => 'بيانات المستخدم']];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="user-wizard" :steps="$steps">

            <form id="user-wizard-form"
                action="{{ $is_profile ? route('admin.profile.update') : ($is_edit ? route('admin.users.update', $user) : route('admin.users.store')) }}"
                method="POST" enctype="multipart/form-data" autocomplete="off" data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                @if (!$is_edit) data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-resource-key="admin.user-create" @endif>
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="user-info-validation" :fields="$is_profile
                    ? ['name', 'email', 'phone', 'image']
                    : ['name', 'email', 'phone', 'role_id', 'password', 'password_confirmation', 'image']">
                    @include('admin.users.partials.steps.user-info', ['user' => $user])

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="$is_profile ? route('admin.profile.show') : route('admin.users.index')" />
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
