<div class="tab-pane fade show active" id="generalSettings">
    <div class="card mb-6">
        <form id="formGeneralSettings" method="POST" action="{{ tenant_route_name('settings.update') }}"
            enctype="multipart/form-data" novalidate data-form-validate
            data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" id="" value="#generalSettings">

            <div class="card-body pt-4">
                <div class="row">
                    <div class="mb-4 col-md-6">
                        <label for="officeName" class="form-label">إسم الشركة</label>
                        <input class="form-control" type="text" id="officeName" name="office_name"
                            value="{{ old('office_name', $settings->office_name ?? '') }}"
                            placeholder="أدخل إسم الشركة" />
                        @error('office_name')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="mb-4 col-md-6">
                        <label for="logoText" class="form-label">النص بجانب الشعار</label>
                        <input type="text" class="form-control" id="logoText" name="logo_text"
                            value="{{ old('logo_text', $settings->logo_text ?? '') }}"
                            placeholder="أدخل النص بجانب الشعار" maxlength="14" />
                        @error('logo_text')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">الحد الأقصى لعدد الأحرف: 14.</small>
                    </div>

                    <x-form.controls.file name="image" label="شعار الشركة" :value="$settings->image ? tasset($settings->image) : null"
                        accept="image/png, image/jpeg, image/webp"
                        help="الملفات المسموح بها: JPG, PNG أو WebP. الأبعاد الموصى بها: 150px × 150px"
                        :max-size="10" col="col-12" />

                    <div class="mb-4 col-md-6">
                        <label for="maintenance_mode" class="form-label">وضع الصيانة</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode"
                                name="maintenance_mode" {{ $settings->maintenance_mode ? 'checked' : '' }}>
                            <label class="form-check-label" for="maintenance_mode">تفعيل وضع الصيانة</label>
                        </div>
                    </div>
                    @if (auth()->user()->hasRole('Admin'))
                        <div class="mb-4 col-md-6 d-none">
                            <label for="breadcrumb_visibility" class="form-label">إظهار قائمة التنقل</label>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="breadcrumb_visibility"
                                    name="breadcrumb_visibility"
                                    {{ old('breadcrumb_visibility', $settings->breadcrumb_visibility) ? 'checked' : '' }}>
                                <label class="form-check-label" for="breadcrumb_visibility">تفعيل عرض قائمة
                                    التنقل</label>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="mt-2 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-3"><i class="ti ti-check me-1"></i>تحديث</button>
                </div>
            </div>
        </form>

    </div>
</div>
