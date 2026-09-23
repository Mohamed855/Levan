<div class="tab-pane fade" id="whatsapp">
    <div class="card mb-6">
        <div class="card-body">
            <form id="whatsappSettings" method="POST" action="{{ tenant_route_name('settings.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="active_tab" id="" value="#whatsapp">

                <div class="row mt-10">
                    <!-- حساب SID -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="twilio_account_sid" class="form-label">Account SID</label>
                        <input type="text" class="form-control" id="twilio_account_sid" name="twilio_account_sid"
                            value="{{ old('twilio_account_sid', $settings->twilio_account_sid ?? '') }}" required>
                        @error('twilio_account_sid')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- رمز المصادقة -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="twilio_auth_token" class="form-label">Auth Token</label>
                        <input type="password" class="form-control" id="twilio_auth_token" name="twilio_auth_token"
                            value="{{ old('twilio_auth_token', $settings->twilio_auth_token ?? '') }}" required>
                        @error('twilio_auth_token')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- رقم WhatsApp -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="twilio_whatsapp_from" class="form-label">رقم WhatsApp</label>
                        <input type="text" class="form-control" id="twilio_whatsapp_from" name="twilio_whatsapp_from"
                            value="{{ old('twilio_whatsapp_from', $settings->twilio_whatsapp_from ?? '') }}"
                            placeholder="مثال: whatsapp:+966XXXXXXXXX" required>
                        @error('twilio_whatsapp_from')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- تمكين WhatsApp -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="whatsapp_enabled" class="form-label">حالة WhatsApp</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="whatsapp_enabled"
                                name="whatsapp_enabled" {{ $settings->whatsapp_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="whatsapp_enabled">
                                تمكين إرسال رسائل WhatsApp
                            </label>
                        </div>
                    </div>
                </div>

                <div class="mt-2 d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary me-3">تحديث</button>
                </div>
            </form>
        </div>
    </div>
</div>
