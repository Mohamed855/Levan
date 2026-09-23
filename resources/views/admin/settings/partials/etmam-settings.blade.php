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
                    <div class="mb-4 col-md-6">
                        <label for="etmam_api_token" class="form-label">API Key</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="etmam_api_token" name="etmam_api_token"
                                value="{{ old('etmam_api_token', $settings->etmam_api_token) }}"
                                placeholder="ادخل المفتاح المقدم من اتمام">

                        </div>
                    </div>

                    <!-- HMAC Secret -->
                    <div class="mb-4 col-md-6">
                        <label for="etmam_hmac_secret" class="form-label">API Secret</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="etmam_hmac_secret" name="etmam_hmac_secret"
                                value="{{ old('etmam_hmac_secret', $settings->etmam_hmac_secret) }}"
                                placeholder="ادخل المفتاح السري  المقدم من اتمام">
                            <button class="btn btn-outline-secondary" type="button"
                                onclick="toggleVisibility('etmam_hmac_secret', this)">
                                <i class="ti ti-eye"></i>
                            </button>
                        </div>
                    </div>

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
                        تحديث
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // زر إظهار/إخفاء كلمة السر
    function toggleVisibility(id, btn) {
        const input = document.getElementById(id);
        const icon = btn.querySelector('i');
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.replace('ti-eye', 'ti-eye-off');
        } else {
            input.type = 'password';
            icon.classList.replace('ti-eye-off', 'ti-eye');
        }
    }
</script>
