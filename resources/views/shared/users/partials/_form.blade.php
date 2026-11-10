@php
    $is_profile = $is_profile ?? false;
    $is_edit = $is_edit ?? false;
    $steps = [['id' => 'user-info-validation', 'title' => 'بيانات المستخدم']];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper :id="$stepperId" :steps="$steps">

            <form id="{{ $formId }}" action="{{ $action }}" method="POST" enctype="multipart/form-data"
                autocomplete="off" data-form-validate data-validation-url="{{ $validationUrl }}"
                @if (!$is_edit && !empty($draftUrl)) data-draft-url="{{ $draftUrl }}"
                    data-draft-resource-key="{{ $draftResourceKey }}" @endif>
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="user-info-validation" :fields="$is_profile
                    ? ['name', 'email', 'phone', 'image']
                    : ['name', 'email', 'phone', 'role_id', 'image']">
                    @include('shared.users.partials.steps.user-info', [
                        'user' => $user ?? null,
                        'roles' => $roles ?? collect(),
                        'is_profile' => $is_profile,
                        'imageUrl' => $imageUrl ?? null,
                    ])

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="$backUrl" />
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
