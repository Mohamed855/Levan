<div class="tab-pane fade" id="etmamSettings">
    <div class="card mb-6">
        <form id="formetmamSettings" method="POST" action="{{ route('admin.settings.update') }}"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" value="#etmamSettings">

            <div class="card-body pt-4">
                <div class="row g-2 mt-10">

                    <!-- تمكين التكامل -->

                    <!-- عنوان API -->
                    <div class="mb-4 col-md-6">
                        <label for="etmam_base_url" class="form-label">الرابط الاساسي (API URL)</label>
                        <input type="text" class="form-control" id="etmam_base_url" name="etmam_base_url"
                            value="{{ old('etmam_base_url', $settings->etmam_base_url) }}"
                            placeholder="https://etmam.example.com/api">
                    </div>

                    <!-- كود التكامل -->
                    <div class="mb-4 col-md-6">
                        <label for="etmam_system_code" class="form-label">رمز التكامل (System Code)</label>
                        <input type="text" class="form-control" id="etmam_system_code" name="etmam_system_code"
                            value="{{ old('etmam_system_code', $settings->etmam_system_code) }}"
                            placeholder="مثال: HR أو POS أو CRM">
                    </div>

                    <!-- API Token -->
                    <x-form.controls.input
                        name="etmam_api_token"
                        id="etmam_api_token"
                        label="API Key"
                        :value="$settings->etmam_api_token"
                        placeholder="ادخل المفتاح المقدم من اتمام"
                        col="col-md-6 mb-4"
                    />

                    <!-- HMAC Secret -->
                    <x-form.controls.password
                        name="etmam_hmac_secret"
                        id="etmam_hmac_secret"
                        label="API Secret"
                        :value="$settings->etmam_hmac_secret"
                        autocomplete="new-password"
                        placeholder="ادخل المفتاح السري المقدم من اتمام"
                        col="col-md-6 mb-4"
                    />

                    <div class="mb-4 col-md-6">
                        <label for="etmam_enabled" class="form-label">حالة التكامل</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="etmam_enabled" name="etmam_enabled"
                                {{ $settings->etmam_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="etmam_enabled">تمكين الاتصال مع النظام المركزي
                                (اتمام)</label>
                        </div>
                    </div>

                </div>

                <div class="mt-2 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-3">
                        <i class="ti ti-check me-1"></i>تحديث
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>


