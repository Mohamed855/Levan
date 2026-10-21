@extends('layouts.dashboard')

@section('title', 'إضافة شركة جديدة')
@section('dashboard-scope', 'tenant')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-building text-primary me-2"></i>
                إضافة شركة جديدة
            </h4>
            <p class="text-muted mb-0">تسجيل شركة أو جهة شريكة مع المفوضين الرسميين.</p>
        </div>
        <div>
            <a href="{{ route('tenant.companies.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger mb-4">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('tenant.companies.store') }}" enctype="multipart/form-data" id="company-form">
        @csrf
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">البيانات الأساسية للشركة</h5>
                    </div>
                    <div class="card-body pt-4">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label" for="company-name">اسم الشركة / الجهة <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="company-name" class="form-control" placeholder="مثال: شركة التطوير العقاري المحدودة" value="{{ old('name') }}" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label" for="company-logo">شعار الشركة (اختياري)</label>
                                <input type="file" name="logo" id="company-logo" class="form-control" accept="image/*">
                                <small class="text-muted">الامتدادات: JPG, PNG, WebP بحد أقصى 2 ميجابايت.</small>
                            </div>

                            <div class="col-12">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="status" value="active" id="company-status" @checked(old('status', 'active') === 'active')>
                                    <label class="form-check-label fw-bold" for="company-status">تفعيل الشركة فوراً في النظام</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header border-bottom d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">المفوضون الرسميون</h5>
                        <button type="button" class="btn btn-sm btn-primary" id="add-person-row">
                            <i class="ti ti-plus me-1"></i> إضافة مفوض
                        </button>
                    </div>
                    <div class="card-body pt-4">
                        <div id="authorized-people-container">
                            <div class="border rounded p-3 mb-3 person-row">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label class="form-label small">اسم المفوض <span class="text-danger">*</span></label>
                                        <input type="text" name="authorized_people[0][name]" class="form-control form-control-sm" placeholder="الاسم الكامل" value="{{ old('authorized_people.0.name') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">المنصب / الصفة</label>
                                        <input type="text" name="authorized_people[0][position]" class="form-control form-control-sm" placeholder="مثال: المدير التنفيذي" value="{{ old('authorized_people.0.position') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">رقم الجوال</label>
                                        <input type="text" name="authorized_people[0][phone]" class="form-control form-control-sm font-monospace" placeholder="05xxxxxxxx" value="{{ old('authorized_people.0.phone') }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label small">البريد الإلكتروني</label>
                                        <input type="email" name="authorized_people[0][email]" class="form-control form-control-sm" placeholder="email@company.com" value="{{ old('authorized_people.0.email') }}">
                                    </div>
                                    <div class="col-12 mt-2">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="authorized_people[0][is_default]" value="1" id="default-0" checked>
                                            <label class="form-check-label small" for="default-0">المفوض الرئيسي للتواصل</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
            <a href="{{ route('tenant.companies.index') }}" class="btn btn-label-secondary">إلغاء</a>
            <x-form.wizard.draft-button />
            <button type="submit" class="btn btn-primary">
                <i class="ti ti-device-floppy me-1"></i> حفظ الشركة
            </button>
        </div>
    </form>
@endsection

@section('page-script')
    <script>
        let personIndex = 1;
        $('#add-person-row').on('click', function () {
            const html = `
                <div class="border rounded p-3 mb-3 person-row position-relative">
                    <button type="button" class="btn btn-sm btn-icon btn-label-danger position-absolute top-0 end-0 m-2 remove-person-row" title="حذف">
                        <i class="ti ti-x"></i>
                    </button>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label small">اسم المفوض <span class="text-danger">*</span></label>
                            <input type="text" name="authorized_people[${personIndex}][name]" class="form-control form-control-sm" placeholder="الاسم الكامل" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">المنصب / الصفة</label>
                            <input type="text" name="authorized_people[${personIndex}][position]" class="form-control form-control-sm" placeholder="مثال: المدير التنفيذي">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">رقم الجوال</label>
                            <input type="text" name="authorized_people[${personIndex}][phone]" class="form-control form-control-sm font-monospace" placeholder="05xxxxxxxx">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">البريد الإلكتروني</label>
                            <input type="email" name="authorized_people[${personIndex}][email]" class="form-control form-control-sm" placeholder="email@company.com">
                        </div>
                        <div class="col-12 mt-2">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="authorized_people[${personIndex}][is_default]" value="1" id="default-${personIndex}">
                                <label class="form-check-label small" for="default-${personIndex}">المفوض الرئيسي للتواصل</label>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            $('#authorized-people-container').append(html);
            personIndex++;
        });

        $(document).on('click', '.remove-person-row', function () {
            $(this).closest('.person-row').remove();
        });
    </script>
@endsection
