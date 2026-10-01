@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل بيانات الشركة — ' . $company->company_name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="ti ti-edit text-primary me-2"></i>تعديل بيانات الشركة
                    </h5>
                    <div class="d-flex gap-2">
                        <a href="{{ route('admin.companies.show', $company->id) }}" class="btn btn-sm btn-label-info">
                            <i class="ti ti-eye me-1"></i>عرض التفاصيل
                        </a>
                        <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">
                            <i class="ti ti-arrow-right me-1"></i>رجوع
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger mb-4">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.companies.update', $company->id) }}" method="POST" autocomplete="off">
                        @csrf
                        @method('PUT')

                        {{-- قسم 1: البيانات التجارية والأساسية --}}
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="ti ti-building me-1"></i>البيانات التجارية الأساسية
                            </h6>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label for="company_name" class="form-label">
                                        اسم الشركة <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="company_name" name="company_name"
                                        value="{{ old('company_name', $company->company_name) }}" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="commercial_number" class="form-label">
                                        رقم السجل التجاري <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" class="form-control" id="commercial_number"
                                        name="commercial_number"
                                        value="{{ old('commercial_number', $company->commercial_number) }}" maxlength="10"
                                        required>
                                </div>

                                <div class="col-md-6">
                                    <label for="tax_number" class="form-label">
                                        الرقم الضريبي
                                    </label>
                                    <input type="text" class="form-control" id="tax_number" name="tax_number"
                                        value="{{ old('tax_number', $company->tax_number) }}">
                                </div>

                                <div class="col-md-6">
                                    <label for="city_id" class="form-label">
                                        المدينة <span class="text-danger">*</span>
                                    </label>
                                    <select class="form-select select2" id="city_id" name="city_id" required>
                                        <option value="">اختر المدينة</option>
                                        @foreach ($cities as $city)
                                            <option value="{{ $city->id }}" @selected(old('city_id', $company->city_id) == $city->id)>
                                                {{ $city->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label">
                                        العنوان <span class="text-danger">*</span>
                                    </label>
                                    <textarea class="form-control" id="address" name="address" rows="2" required>{{ old('address', $company->address) }}</textarea>
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- قسم 2: بيانات المسؤول المرجعية --}}
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="ti ti-user me-1"></i>بيانات المسؤول المرجعية (Central DB)
                            </h6>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <label for="primary_contact_name" class="form-label">اسم المسؤول</label>
                                    <input type="text" class="form-control" id="primary_contact_name"
                                        name="primary_contact_name"
                                        value="{{ old('primary_contact_name', $company->primary_contact_name) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="primary_contact_email" class="form-label">البريد الإلكتروني</label>
                                    <input type="email" class="form-control" id="primary_contact_email"
                                        name="primary_contact_email"
                                        value="{{ old('primary_contact_email', $company->primary_contact_email) }}">
                                </div>

                                <div class="col-md-4">
                                    <label for="primary_contact_phone" class="form-label">رقم الجوال</label>
                                    <input type="text" class="form-control" id="primary_contact_phone"
                                        name="primary_contact_phone"
                                        value="{{ old('primary_contact_phone', $company->primary_contact_phone) }}"
                                        placeholder="05xxxxxxxx">
                                </div>
                            </div>
                        </div>

                        <hr class="my-4">

                        {{-- قسم 3: إعدادات الشركة الإضافية --}}
                        <div class="mb-4">
                            <h6 class="fw-bold text-primary mb-3">
                                <i class="ti ti-settings me-1"></i>إعدادات الشركة الخاصة
                            </h6>
                            <div class="row g-4">
                                <div class="col-md-4">
                                    <div class="card border p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0 fw-semibold">تنبيهات البريد</h6>
                                                <small class="text-muted">إرسال إشعارات البريد</small>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input type="checkbox" class="form-check-input"
                                                    name="email_notifications_enabled" value="1"
                                                    @checked(old('email_notifications_enabled', $settings->email_notifications_enabled ?? true))>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0 fw-semibold">التحقق الثنائي</h6>
                                                <small class="text-muted">فرض 2FA على مستخدمي الشركة</small>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input type="checkbox" class="form-check-input" name="two_factor_enabled"
                                                    value="1" @checked(old('two_factor_enabled', $settings->two_factor_enabled ?? false))>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <div class="card border p-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-0 fw-semibold">التكاملات الخارجية</h6>
                                                <small class="text-muted">السماح بربط API والأنظمة</small>
                                            </div>
                                            <div class="form-check form-switch mb-0">
                                                <input type="checkbox" class="form-check-input"
                                                    name="external_integrations_enabled" value="1"
                                                    @checked(old('external_integrations_enabled', $settings->external_integrations_enabled ?? false))>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">
                                إلغاء
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-check me-1"></i>حفظ التعديلات
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
