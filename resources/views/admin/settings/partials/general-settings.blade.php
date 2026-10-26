<div class="tab-pane fade show active" id="generalSettings">
    <div class="card mb-6">
        <form id="formGeneralSettings" method="POST" action="{{ route('admin.settings.update') }}"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" id="" value="#generalSettings">
            <div class="card-body pb-0">
                <x-form.controls.file name="image" label="شعار الموقع / الشعار العام"
                    :value="$settings->image ? asset('storage/' . $settings->image) : null"
                    accept="image/png, image/jpeg, image/webp"
                    help="الملفات المسموح بها: JPG, PNG أو WebP. الأبعاد الموصى بها: 150px × 150px"
                    :max-size="2" col="col-md-6" />
            </div>

            <div class="card-body pt-4">
                <div class="row g-2">
                    <div class="mb-4 col-md-6">
                        <label for="officeName" class="form-label">إسم الشركة</label>
                        <input class="form-control" type="text" id="officeName" name="office_name" required
                            value="{{ old('office_name', $settings->office_name ?? '') }}"
                            placeholder="أدخل إسم الشركة" />
                        @error('office_name')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- وضع الصيانة -->
                    <div class="mb-4 col-md-6">
                        <label for="logoText" class="form-label">النص بجانب الشعار</label>
                        <input type="text" class="form-control" id="logoText" name="logo_text" required
                            value="{{ old('logo_text', $settings->logo_text ?? '') }}"
                            placeholder="أدخل النص بجانب الشعار" maxlength="14" />
                        @error('logo_text')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">الحد الأقصى لعدد الأحرف: 14.</small>
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="office_address" class="form-label">عنوان المكتب</label>
                        <input class="form-control" type="text" id="office_address" name="office_address"
                            value="{{ old('office_address', $settings->office_address ?? '') }}"
                            placeholder="أدخل عنوان المكتب" />
                        @error('office_address')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="commercial_register" class="form-label">السجل التجاري</label>
                        <input class="form-control" type="text" id="commercial_register" name="commercial_register"
                            value="{{ old('commercial_register', $settings->commercial_register ?? '') }}"
                            placeholder="أدخل رقم السجل التجاري" />
                        @error('commercial_register')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="tax_number" class="form-label">الرقم الضريبي</label>
                        <input class="form-control" type="text" id="tax_number" name="tax_number"
                            value="{{ old('tax_number', $settings->tax_number ?? '') }}"
                            placeholder="أدخل الرقم الضريبي" />
                        @error('tax_number')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="phone_number" class="form-label">رقم الهاتف</label>
                        <input class="form-control" type="text" id="phone_number" name="phone_number" data-phone-format="international"
                            value="{{ old('phone_number', $settings->phone_number ?? '') }}"
                            placeholder="أدخل رقم الهاتف" />
                        @error('phone_number')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="email" class="form-label">البريد الإلكتروني</label>
                        <input class="form-control" type="email" id="email" name="email"
                            value="{{ old('email', $settings->email ?? '') }}" placeholder="أدخل البريد الإلكتروني" />
                        @error('email')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    @foreach (['facebook_url' => 'رابط Facebook', 'x_url' => 'رابط X', 'linkedin_url' => 'رابط LinkedIn'] as $field => $label)
                        <div class="mb-4 col-md-6"><label for="{{ $field }}" class="form-label">{{ $label }}</label><input class="form-control" type="url" id="{{ $field }}" name="{{ $field }}" value="{{ old($field, $settings->{$field} ?? '') }}" />@error($field)<div class="text-danger small">{{ $message }}</div>@enderror</div>
                    @endforeach
                    <div class="mb-4 col-md-6">
                        <label for="google_maps_embed_url" class="form-label">رابط Google Maps Embed</label>
                        <input class="form-control" type="text" id="google_maps_embed_url" name="google_maps_embed_url" value="{{ old('google_maps_embed_url', $settings->google_maps_embed_url ?? '') }}" placeholder="https://www.google.com/maps/embed?pb=..." />
                        <div class="form-text">يمكنك إدخال رابط التضمين مباشرة أو كود iframe بالكامل من خرائط جوجل.</div>
                        @error('google_maps_embed_url')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    {{--
                    <div class="mb-4 col-md-6">
                        <label for="trial_days_mode" class="form-label">فترة التجربة</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="trial_days_mode"
                                name="trial_days_mode" {{ $settings->trial_days_mode ? 'checked' : '' }}>
                            <label class="form-check-label" for="trial_days_mode">تفعيل فترة التجربة </label>
                        </div>
                    </div> --}}

                    {{-- <div class="mb-4 col-md-6">
                        <label for="maintenance_mode" class="form-label">وضع الصيانة</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="maintenance_mode"
                                name="maintenance_mode" {{ $settings->maintenance_mode ? 'checked' : '' }}>
                            <label class="form-check-label" for="maintenance_mode">تفعيل وضع الصيانة</label>
                        </div>
                    </div> --}}

                </div>
                <div class="mt-2 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-3">تحديث </button>
                </div>
            </div>
        </form>

    </div>
</div>
