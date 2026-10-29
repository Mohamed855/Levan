<div class="tab-pane fade" id="microsoft">
    <div class="card mb-6">
        <div class="card-body">

            <form id="formMicrosoft" method="POST" action="{{ route('admin.settings.update') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="active_tab" id="" value="#microsoft">

                <div class="row g-2">
                    <div class="mb-4 col-md-6">
                        <label for="microsoft_client_id" class="form-label">Client ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="microsoft_client_id" name="microsoft_client_id"
                            required value="{{ old('microsoft_client_id', $settings['microsoft_client_id']) }}">
                        @error('microsoft_client_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="microsoft_client_secret" class="form-label">Client Secret</label>
                        <input type="text" class="form-control" id="microsoft_client_secret"
                            name="microsoft_client_secret" placeholder="اتركه فارغا إذا كنت لا تريد تغييره">
                        @error('microsoft_client_secret')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="microsoft_redirect_uri" class="form-label">Redirect URI <span class="text-danger">*</span></label>
                        <input type="url" class="form-control" id="microsoft_redirect_uri" required
                            name="microsoft_redirect_uri"
                            value="{{ old('microsoft_redirect_uri', $settings['microsoft_redirect_uri']) }}">
                        @error('microsoft_redirect_uri')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="microsoft_tenant_id" class="form-label">Tenant ID <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="microsoft_tenant_id" name="microsoft_tenant_id"
                            required value="{{ old('microsoft_tenant_id', $settings['microsoft_tenant_id']) }}">
                        @error('microsoft_tenant_id')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4 col-md-6">
                        <label for="main_email" class="form-label">البريد الالكتروني الخاص بالرسائل <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="main_email" name="main_email" required
                            value="{{ old('main_email', $settings['main_email']) }}">
                        @error('main_email')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="mt-2 d-flex justify-content-end">
                    <button type="submit" class="btn btn-sm btn-primary me-3"><i class="ti ti-check me-1"></i>حفظ التغييرات</button>
                </div>
            </form>
        </div>
    </div>
</div>
