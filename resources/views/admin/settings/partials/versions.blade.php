<div class="tab-pane fade" id="versionSettings">
    <div class="card mb-6">
        <form id="formversionSettings" method="POST" action="{{ route('admin.settings.update') }}"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" value="#versionSettings">

            <div class="card-body pt-4">
                <div class="row g-2 mt-10">

                    {{-- <div class="mb-4 col-md-6">
                        <label for="default_tenant_version_id" class="form-label">الإصدارة الافتراضية للعملاء</label>
                        <small class="text-muted d-block">تُستخدم هذه الإصدارة تلقائيًا للعملاء الجدد أو الذين لم يُحدد
                            لهم
                            إصدار بعد.</small>
                        <select id="default_tenant_version_id" name="default_tenant_version_id"
                            class="form-select select2" data-placeholder="اختر الإصدارة الافتراضية">
                            <option value="">بدون إصدارة</option>
                            @foreach ($versions as $version)
                                <option value="{{ $version->id }}"
                                    {{ old('default_tenant_version_id', $settings->default_tenant_version_id) == $version->id ? 'selected' : '' }}>
                                    {{ $version->version }} — {{ $version->name ?? 'بدون اسم' }}
                                </option>
                            @endforeach
                        </select>

                    </div> --}}

                    <!-- إصدارة الصفحة التعريفية -->
                    <div class="mb-4 col-md-6">
                        <label for="default_landing_version_id" class="form-label">إصدارة الصفحة التعريفية (Landing
                            Page)</label>
                        <small class="text-muted d-block">تُستخدم لتحديد الإصدارة الخاصة بعرض الصفحة التعريفية
                            العامة.</small>
                        <select id="default_landing_version_id" name="default_landing_version_id"
                            class="form-select select2" data-placeholder="اختر الإصدارة الخاصة بالصفحة التعريفية">
                            <option value="">بدون إصدارة</option>
                            @foreach ($versions as $version)
                                <option value="{{ $version->id }}"
                                    {{ old('default_landing_version_id', $settings->default_landing_version_id) == $version->id ? 'selected' : '' }}>
                                    {{ $version->version }} — {{ $version->name ?? 'بدون اسم' }}
                                </option>
                            @endforeach
                        </select>
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
