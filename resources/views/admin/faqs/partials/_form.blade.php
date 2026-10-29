@php
    $is_edit = $is_edit ?? (isset($faq) && $faq->exists);
    $steps = [
        [
            'id' => 'faq-info',
            'title' => 'بيانات السؤال الشائع',
        ],
    ];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="faq-wizard" :steps="$steps">
            <x-ui.validation-errors />

            <form id="faq-wizard-form"
                action="{{ $is_edit ? route('admin.faqs.update', $faq->id) : route('admin.faqs.store') }}"
                method="POST"
                autocomplete="off"
                data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                @if (!$is_edit)
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-resource-key="admin.faq-create"
                @endif
            >
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="faq-info" :fields="['question', 'answer', 'is_active']">
                    <div class="row g-4">
                        <x-form.controls.input name="question" label="السؤال"
                            :value="old('question', $is_edit ? $faq->question : '')"
                            placeholder="مثال: كيف يمكنني إضافة مزاد جديد؟"
                            required col="col-12" />

                        <x-form.controls.textarea name="answer" label="الإجابة"
                            :value="old('answer', $is_edit ? $faq->answer : '')"
                            placeholder="اكتب الإجابة هنا..."
                            rows="4"
                            required col="col-12" />

                        <x-form.controls.select name="is_active" label="الحالة"
                            :value="old('is_active', $is_edit ? ($faq->is_active ? '1' : '0') : '1')"
                            :enum="\App\Enums\Shared\ActivationStatus::class"
                            required col="col-md-6" />
                    </div>

                    <x-form.wizard.actions>
                        <x-form.wizard.previous-button :href="route('admin.faqs.index')" />
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
