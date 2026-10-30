@php
    $is_profile = $is_profile ?? false;
    $steps = [
        [
            'id' => 'user-info-validation',
            'title' => 'بيانات المستخدم',
        ],
    ];
@endphp

<div class="row">
    <div class="col-12 mb-6">
        <x-form.wizard.stepper id="user-wizard" :steps="$steps">
            <x-ui.validation-errors />

            <form id="user-wizard-form"
                action="{{ $is_profile ? route('admin.profile.update') : ($is_edit ? route('admin.users.update', $user) : route('admin.users.store')) }}"
                method="POST"
                enctype="multipart/form-data"
                autocomplete="off"
                data-form-validate
                data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}"
                @if (!$is_edit)
                    data-draft-url="{{ route('admin.wizard-drafts.store') }}"
                    data-draft-resource-key="admin.user-create"
                @endif
            >
                @csrf
                @if ($is_edit)
                    @method('PUT')
                @endif

                <x-form.wizard.step id="user-info-validation" :fields="$is_profile
                    ? ['name', 'email', 'phone', 'image']
                    : ['name', 'email', 'phone', 'password', 'password_confirmation', 'image']">
                    <div class="row g-4">
                        <x-form.controls.input name="name" label="الاسم الكامل" :value="old('name', $user->name ?? '')"
                            placeholder="ادخل الاسم الكامل" required col="col-md-6" />

                        <x-form.controls.input name="email" type="email" label="البريد الإلكتروني" :value="old('email', $user->email ?? '')"
                            placeholder="ادخل البريد الإلكتروني" autocomplete="off" required col="col-md-6" />

                        <x-form.controls.input name="phone" label="رقم الهاتف" :value="old('phone', $user->phone ?? '')"
                            placeholder="05xxxxxxxx" maxlength="10" inputmode="numeric" required col="col-md-6" />

                        <x-form.controls.file name="image" label="الصورة الشخصية"
                            :value="!empty($user?->image) ? asset('storage/' . $user->image) : null"
                            accept="image/*" col="col-md-6" />

                        @if (!$is_profile)
                            <x-form.controls.password name="password"
                                :label="$is_edit ? 'كلمة المرور الجديدة' : 'كلمة المرور'"
                                :placeholder="$is_edit ? 'اتركه فارغاً إذا لم ترغب بالتعديل' : 'ادخل كلمة المرور'"
                                data-validation-dependents="password_confirmation"
                                :required="!$is_edit" col="col-md-6" />

                            <x-form.controls.password name="password_confirmation"
                                :label="$is_edit ? 'تأكيد كلمة المرور الجديدة' : 'تأكيد كلمة المرور'"
                                :placeholder="$is_edit ? 'أعد إدخال كلمة المرور' : 'أعد إدخال كلمة المرور'"
                                :required="!$is_edit" col="col-md-6" />
                        @endif
                    </div>

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

