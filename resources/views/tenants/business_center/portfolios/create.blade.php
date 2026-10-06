@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة محفظة جديدة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/cdn/sweetalert2.js'])

    <script>
        window.typeOptions = @json($typeOptions);
        window.isEditMode = false;
        window.existingFields = [];
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenants/business-center/portfolios/validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2" data-form-stepper>
                <div class="bs-stepper-header">
                    <div class="step" data-target="#basic-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">المعلومات الأساسية</span>
                                <span class="bs-stepper-subtitle">اسم المحفظة والشركة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#portfolio-details-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">تفاصيل المحفظة</span>
                                <span class="bs-stepper-subtitle">الحقول وتاريخ الإسناد</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <h6 class="alert-heading mb-2"><i class="ti ti-alert-triangle me-2"></i>يرجى تصحيح الأخطاء
                                التالية:
                            </h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ tenant_route_name('portfolios.store') }}" method="POST"
                        novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                        @csrf

                        <!-- المعلومات الأساسية -->
                        <div id="basic-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="company_id">الشركة <span
                                            class="text-danger">*</span></label>
                                    <select name="company_id" id="company_id" required
                                        class="form-control select2 @error('company_id') is-invalid @enderror"
                                        data-placeholder="اختر الشركة">
                                        <option value=""></option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}"
                                                {{ old('company_id') == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('company_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-6">
                                    <label class="form-label" for="name">اسم المحفظة <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" required
                                        class="form-control @error('name') is-invalid @enderror"
                                        placeholder="ادخل اسم المحفظة" value="{{ old('name') }}" />
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-sm-12">
                                    <div class="form-check form-switch mt-3">
                                        <input class="form-check-input" type="checkbox" name="has_split_names"
                                            id="has_split_names" value="1"
                                            {{ old('has_split_names') ? 'checked' : '' }}>
                                        <label class="form-check-label" for="has_split_names">تتطلب هذه المحفظة إدخال أسماء
                                            العملاء بشكل مقسم (الاسم الأول، الأب، ...)</label>
                                    </div>
                                </div>
                                <div class="mt-4 d-flex justify-content-between">
                                    <a href="{{ tenant_route_name('portfolios.index') }}" class="btn btn-secondary">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </a>
                                    <button type="button" class="btn btn-primary btn-next" disabled>
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- تفاصيل المحفظة -->
                        <div id="portfolio-details-validation" class="content">
                            <div class="row g-6">
                                <!-- تاريخ الإسناد -->
                                <div class="col-sm-4">
                                    <label class="form-label" for="assignment_date">تاريخ الإسناد</label>
                                    <input type="date" name="assignment_date" id="assignment_date" class="form-control"
                                        placeholder="اختر تاريخ الإسناد" value="{{ old('assignment_date') }}" />
                                </div>

                                <div class="col-sm-4">
                                    <label class="form-label" for="call_start_time">
                                        <i class="ti ti-clock-play me-1"></i>وقت بدء الاتصال
                                    </label>
                                    <input type="time" name="call_start_time" id="call_start_time"
                                        class="form-control @error('call_start_time') is-invalid @enderror"
                                        value="{{ old('call_start_time') }}" />
                                    @error('call_start_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-sm-4">
                                    <label class="form-label" for="call_end_time">
                                        <i class="ti ti-clock-stop me-1"></i>وقت نهاية الاتصال
                                    </label>
                                    <input type="time" name="call_end_time" id="call_end_time"
                                        class="form-control @error('call_end_time') is-invalid @enderror"
                                        value="{{ old('call_end_time') }}" />
                                    @error('call_end_time')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- حقول المحفظة -->
                                <div class="col-12 mt-4">
                                    <div>
                                        <div class="card-header d-flex justify-content-between align-items-center">
                                            <h6 class="mb-0">حقول المحفظة</h6>
                                            <button type="button" class="btn btn-primary btn-sm"
                                                id="add-portfolio-field-btn">
                                                <i class="ti ti-plus me-1"></i>إضافة حقل
                                            </button>
                                        </div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <small class="text-muted">إضافة وتخصيص حقول المحفظة حسب
                                                        احتياجاتك</small>
                                                </div>
                                            </div>

                                            <div id="portfolio-fields-container">
                                                <!-- سيتم إضافة جميع الحقول هنا -->
                                            </div>

                                            <div id="empty-fields-state" class="text-center py-5">
                                                <i class="ti ti-database text-muted" style="font-size: 3rem;"></i>
                                                <h6 class="mt-3 text-muted">لا توجد حقول محددة</h6>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-submit">
                                            <span class="align-middle d-sm-inline-block d-none me-sm-2">حفظ</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- قالب الحقل -->
    <script type="text/template" id="portfolio-field-template">
    <div class="portfolio-field-row mb-3" data-field-source="custom" data-required="false">
    <input type="hidden" name="field_ids[]" class="field-id-input" value="">
    <div class="card border">
      <div class="card-body">
        <div class="row align-items-center g-3">
          <div class="col-1 text-center">
            <span class="field-number badge bg-primary"></span>
          </div>
          <div class="col-md-5">
            <label class="form-label fw-medium">اسم الحقل</label>
            <input type="text" name="field_names[]" class="form-control field-name-input" placeholder="ادخل اسم الحقل">
          </div>
          <div class="col-md-3">
            <label class="form-label fw-medium">نوع الحقل</label>
            <select name="field_types[]" class="form-control select2 field-type-select" data-placeholder="اختر النوع">
              @foreach($typeOptions as $val => $lab)
                <option value="{{ $val }}">{{ $lab }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label fw-medium d-block">مطلوب</label>
            <div class="form-check mt-2 d-flex">
              <div class="required-field-container" data-field-index="">
                <input type="hidden" name="field_required_flags[]" value="0" class="field-required-hidden">
                <input type="checkbox" class="form-check-input field-required-checkbox" data-field-index="">
              </div>
            </div>
          </div>
          <div class="col-md-1">
            <label class="form-label fw-medium">العمليات</label>
            <button type="button" class="btn btn-outline-danger btn-sm remove-portfolio-field-btn">
              <i class="ti ti-trash me-1"></i>حذف
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</script>
@endsection
