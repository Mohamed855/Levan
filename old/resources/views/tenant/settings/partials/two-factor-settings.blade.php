<div class="tab-pane fade" id="twoFactorSettings">
    <div class="card mb-6">
        <form id="formTwoFactorSettings" method="POST" action="{{ tenant_route_name('settings.update') }}" novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" value="#twoFactorSettings">
            <input type="hidden" name="two_factor_settings_submit" value="1">

            <div class="card-body pt-4">
                <div class="row g-2">
                    <div class="mb-4 col-md-8">
                        <label for="force_two_factor_auth" class="form-label">إجبارية المصادقة الثنائية</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="force_two_factor_auth"
                                name="force_two_factor_auth" {{ $settings->force_two_factor_auth ? 'checked' : '' }}>
                            <label class="form-check-label" for="force_two_factor_auth">
                                فرض تفعيل المصادقة الثنائية إجباريًا على جميع حسابات المستخدمين
                            </label>
                        </div>
                        <small class="text-muted d-block mt-2">
                            عند التفعيل، سيُطالب أي مستخدم لم يقم بتفعيل المصادقة الثنائية بإعدادها فور تسجيل
                            الدخول، ولن يتمكن من متابعة استخدام النظام قبل إتمام ذلك.
                        </small>
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
