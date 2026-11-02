@php
    $is_edit = $is_edit ?? (isset($issuingEntity) && $issuingEntity->exists);
    $steps = [
        [
            'id' => 'issuing-entity-info',
            'title' => 'بيانات الجهة المسندة',
        ],
    ];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="issuing-entity-wizard" :steps="$steps">
            <x-ui.validation-errors />

            <form id="issuing-entity-wizard-form"
                action="{{ $is_edit ? tenant_route_name('issuing-entities.update', $issuingEntity->id) : tenant_route_name('issuing-entities.store') }}"
                method="POST"
                autocomplete="off"
                data-form-validate
                data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                @if (!$is_edit)
                    data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                    data-draft-resource-key="tenant.issuing-entity-create"
                @endif
            >
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="issuing-entity-info" :fields="['name', 'is_active']">
                    <div class="row g-4">
                        <x-form.controls.input name="name" label="اسم الجهة المسندة"
                            :value="old('name', $is_edit ? $issuingEntity->name : '')"
                            placeholder="مثال: مركز الإسناد والتصفية (إنفاذ) أو محكمة التنفيذ بالرياض"
                            required col="col-md-8" />

                        <x-form.controls.select name="is_active" label="الحالة"
                            :value="old('is_active', $is_edit ? ($issuingEntity->is_active ? '1' : '0') : '1')"
                            :enum="\App\Enums\Shared\ActivationStatus::class"
                            required col="col-md-4" />
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="tenant_route_name('issuing-entities.index')" />
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
