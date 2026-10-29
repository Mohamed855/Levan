@php
    $is_edit = $is_edit ?? false;
    $steps = [['id' => 'ticket-info-validation', 'title' => 'معلومات التذكرة']];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="wizard-validation" class="mt-2" :steps="$steps">
            <x-ui.validation-errors />

            <form id="wizard-validation-form"
                action="{{ $is_edit ? '#' : tenant_route_name('tickets.store') }}"
                method="POST"
                enctype="multipart/form-data"
                novalidate
                data-form-validate
                data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}"
                @if (!$is_edit)
                    data-draft-url="{{ tenant_route_name('wizard-drafts.store') }}"
                    data-draft-resource-key="tenant.ticket-create"
                @endif
            >
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="ticket-info-validation" :fields="['title', 'ticketing_sections_id', 'description', 'attachments']">
                    <div class="row g-4">
                        <x-form.controls.input name="title" id="title" label="عنوان التذكرة"
                            placeholder="أدخل عنوان التذكرة" required col="col-md-6" />

                        <x-form.controls.select name="ticketing_sections_id" id="ticketing_sections_id"
                            label="تصنيف التذكرة" placeholder="اختر التصنيف" required select2 :options="$sections"
                            col="col-md-6" />

                        <x-form.controls.textarea name="description" id="description" label="وصف المشكلة" rows="5"
                            placeholder="اشرح تفاصيل المشكلة التي تواجهها..." required />

                        <x-form.controls.file name="attachments" id="attachments" label="المرفقات" multiple
                            accept="image/*,application/pdf,.doc,.docx,.txt"
                            help="يمكنك رفع عدة صور أو ملفات (حد أقصى 5 ملفات، كل ملف حتى 10MB)" :max-size="10" />
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="tenant_route_name('tickets.index')" />
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
