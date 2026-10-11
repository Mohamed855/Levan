<div class="tab-pane fade" id="SmsSettings">
    <div class="card mb-6">
        <div class="card-body">
            <form id="SmsSettings" method="POST" action="{{ tenant_route_name('settings.update') }}" novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="active_tab" id="" value="#SmsSettings">
                <div class="row mt-10">

                    <!-- إعدادات Microsoft الحالية -->
                    <!-- مزود الخدمة -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="sms_provider" class="form-label">مزود الخدمة</label>
                        <select class="form-select select2" id="sms_provider" name="sms_provider"
                            data-placeholder="اختر مزود الخدمة">
                            <x-form.controls.enum-options :enum="\App\Enums\Tenants\Settings\SmsProvider::class"
                                :selected="old('sms_provider', $settings->sms_provider ?? '')" />
                        </select>
                        @error('sms_provider')
                        <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- مفتاح API -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="sms_api_key" class="form-label"> Api Key</label>
                        <input type="text" class="form-control" id="sms_api_key" name="sms_api_key"
                            value="{{ old('sms_api_key', $settings->sms_api_key ?? '') }}">
                        @error('sms_api_key')
                        <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- سر API -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="sms_api_secret" class="form-label">Api Secret</label>
                        <input type="text" class="form-control" id="sms_api_secret" name="sms_api_secret"
                            value="{{ old('sms_api_secret', $settings->sms_api_secret ?? '') }}">
                        @error('sms_api_secret')
                        <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- معرف المرسل -->
                    <div class="col-12 col-md-6 mb-4">
                        <label for="sms_sender_id" class="form-label">معرف المرسل</label>
                        <input type="text" class="form-control" id="sms_sender_id" name="sms_sender_id"
                            value="{{ old('sms_sender_id', $settings->sms_sender_id ?? '') }}">
                        @error('sms_sender_id')
                        <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                    <!-- تمكين الرسائل النصية -->
                    <div class="mb-4 col-md-6">
                        <label for="sms_enabled" class="form-label">حالة الرسائل</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="sms_enabled" name="sms_enabled" {{
                                $settings->sms_enabled ? 'checked' : '' }}>
                            <label class="form-check-label" for="sms_enabled">
                                تمكين الرسائل النصية
                            </label>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <label for="sms_auto_reminders_enabled" class="form-label">التذكيرات التلقائية</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" id="sms_auto_reminders_enabled"
                                name="sms_auto_reminders_enabled" {{ !empty($settings->sms_auto_reminders_enabled) ?
                            'checked' : '' }}>
                            <label class="form-check-label" for="sms_auto_reminders_enabled">
                                تفعيل إرسال التذكيرات التلقائية
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const smsEnabledCheckbox = document.getElementById('sms_enabled');
        const autoReminderCheckbox = document.getElementById('sms_auto_reminders_enabled');

        if (!smsEnabledCheckbox || !autoReminderCheckbox) return;

        function syncAutoReminderState() {
            if (!smsEnabledCheckbox.checked) {
                autoReminderCheckbox.checked = false;
                autoReminderCheckbox.disabled = true;
                autoReminderCheckbox.closest('.form-check').classList.add('text-muted');
            } else {
                autoReminderCheckbox.disabled = false;
                autoReminderCheckbox.closest('.form-check').classList.remove('text-muted');
            }
        }

        // أول تحميل للصفحة
        syncAutoReminderState();

        // عند تغيير حالة تمكين الرسائل
        smsEnabledCheckbox.addEventListener('change', function () {
            syncAutoReminderState();
            // if (!smsEnabledCheckbox.checked && window.toastr) {
            //     toastr.info('تم إيقاف التذكيرات التلقائية تلقائياً بسبب إيقاف خدمة الرسائل النصية.', 'تنبيه');
            // }
        });

        // حماية إضافية: لو حاول تفعيل التلقائي بدون تفعيل الأساسي (حتى لو شال disabled من الـ Inspect)
        autoReminderCheckbox.addEventListener('change', function (e) {
            if (!smsEnabledCheckbox.checked && autoReminderCheckbox.checked) {
                autoReminderCheckbox.checked = false;

                if (window.toastr) {
                    toastr.warning(
                        'لا يمكن تفعيل الإرسال التلقائي قبل تفعيل خدمة الرسائل النصية.',
                        'تنبيه'
                    );
                } else {
                    alert('لا يمكن تفعيل الإرسال التلقائي قبل تفعيل خدمة الرسائل النصية.');
                }

                e.preventDefault();
            }
        });
    });
</script>
