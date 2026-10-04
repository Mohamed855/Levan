@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة عميل جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    {{-- routes + CSRF --}}
    <script>
        window.routes = {
            portfoliosByCompany: "{{ tenant_route_name('customers.portfolios-by-company') }}",
            fieldsByPortfolio: "{{ tenant_route_name('customers.fields-by-portfolio') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
    </script>

    @vite(['resources/assets/js/tenants/business-center/customers/dynamic-fields/customers-dynamic-fields.js', 'resources/assets/js/tenants/business-center/customers/phone-fields/customers-phone-fields.js', 'resources/assets/js/tenants/business-center/customers/basic/validation.js', 'resources/assets/js/tenants/business-center/customers/name-toggle/customers-name-toggle.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#personal-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">المعلومات الشخصية</span>
                                <span class="bs-stepper-subtitle">البيانات الأساسية للعميل</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#company-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">معلومات الشركة</span>
                                <span class="bs-stepper-subtitle">الشركة والمحفظة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#contact-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">بيانات الاتصال</span>
                                <span class="bs-stepper-subtitle">الهواتف والحسابات</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ tenant_route_name('customers.store') }}" method="POST"
                        onSubmit="return false">
                        @csrf

                        <!-- المرحلة الأولى: المعلومات الشخصية -->
                        <div id="personal-info-validation" class="content">
                            <div class="row g-6">

                                {{-- NEW position – inside Step 1 --}}
                                <div class="col-sm-6">
                                    <label class="form-label" for="company_id">الشركة <span
                                            class="text-danger">*</span></label>
                                    <select name="company_id" id="company_id" class="form-control select2"
                                        data-placeholder="اختر الشركة">
                                        <option value=""></option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}"
                                                {{ old('company_id', $customer->company_id ?? '') == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="portfolio_id">المحفظة <span
                                            class="text-danger">*</span></label>
                                    <select name="portfolio_id" id="portfolio_id" class="form-control select2"
                                        data-placeholder="اختر المحفظة">
                                        <option value=""></option>
                                        @foreach ($portfolios as $portfolio)
                                            <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}"
                                                data-has-split="{{ $portfolio->has_split_names ? 1 : 0 }}"
                                                {{ old('portfolio_id', $customer->portfolio_id ?? '') == $portfolio->id ? 'selected' : '' }}>
                                                {{ $portfolio->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6" id="full-name-wrapper">
                                    <label class="form-label" for="name">اسم العميل <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="name" id="name" class="form-control"
                                        placeholder="ادخل اسم العميل" value="{{ old('name', $customer->name ?? '') }}" />
                                </div>


                                {{-- حقول الاسم المقسَّم --}}
                                {{-- الاسم المقسَّم --}}
                                <div id="split-names-wrapper" class="d-none flex-wrap justify-content-between gap-3 w-100">

                                    <div class="flex-grow-1" style="min-width:160px">
                                        <label class="form-label" for="first_name">الاسم الأول
                                            <span class="text-danger">*</span></label>
                                        <input type="text" id="first_name" name="first_name" class="form-control"
                                            value="{{ old('first_name', $customer->first_name ?? '') }}">
                                    </div>

                                    <div class="flex-grow-1" style="min-width:160px">
                                        <label class="form-label" for="father_name">اسم الأب</label>
                                        <input type="text" id="father_name" name="father_name" class="form-control"
                                            value="{{ old('father_name', $customer->father_name ?? '') }}">
                                    </div>

                                    <div class="flex-grow-1" style="min-width:160px">
                                        <label class="form-label" for="grandfather_name">اسم الجد</label>
                                        <input type="text" id="grandfather_name" name="grandfather_name"
                                            class="form-control"
                                            value="{{ old('grandfather_name', $customer->grandfather_name ?? '') }}">
                                    </div>

                                    <div class="flex-grow-1" style="min-width:160px">
                                        <label class="form-label" for="family_name">اسم العائلة
                                            <span class="text-danger">*</span></label>
                                        <input type="text" id="family_name" name="family_name" class="form-control"
                                            value="{{ old('family_name', $customer->family_name ?? '') }}">
                                    </div>
                                </div>


                                <div class="col-sm-6">
                                    <label class="form-label" for="customer_type">نوع العميل <span
                                            class="text-danger">*</span></label>
                                    <select name="customer_type" id="customer_type" class="form-control select2"
                                        data-placeholder="اختر نوع العميل">
                                        <option value=""></option>
                                        <option value="individual"
                                            {{ old('customer_type') == 'individual' ? 'selected' : '' }}>فردي</option>
                                        <option value="company" {{ old('customer_type') == 'company' ? 'selected' : '' }}>
                                            شركة</option>
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="identity_number">رقم الهوية <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="identity_number" id="identity_number" maxlength="10"
                                        class="form-control" placeholder="ادخل رقم الهوية"
                                        value="{{ old('identity_number') }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="nationality">الجنسية</label>
                                    <select name="nationality" id="nationality" class="form-control select2"
                                        data-placeholder="اختر الجنسية">
                                        <option value=""></option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->id }}"
                                                {{ old('nationality') == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>


                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev" disabled>
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-next">
                                            <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                            <i class="ti ti-arrow-right ti-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- المرحلة الثانية: معلومات الشركة -->
                        <div id="company-info-validation" class="content">
                            <div class="row g-6">


                                <div class="col-sm-6">
                                    <label class="form-label" for="relationship_manager_id">مسؤول العلاقة </label>
                                    <select name="relationship_manager_id" id="relationship_manager_id"
                                        class="form-control select2" data-placeholder="اختر مسؤول العلاقة">
                                        <option value=""></option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                {{ old('relationship_manager_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- … داخل div.row الخاص بالمحفظة -->
                                <div class="col-12 mt-4">
                                    {{-- <h5>حقول إضافية حسب المحفظة</h5> --}}
                                    <div id="dynamic-fields" class="row g-4"></div>
                                </div>


                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-primary btn-next">
                                            <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                            <i class="ti ti-arrow-right ti-xs"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- المرحلة الثالثة: بيانات الاتصال -->
                        <div id="contact-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="phone">رقم الهاتف الأساسي <span
                                            class="text-danger">*</span></label>
                                    <input type="text" name="phone" id="phone" class="form-control phone-input"
                                        placeholder="05xxxxxxxx" maxlength="10" value="{{ old('phone') }}" />
                                </div>

                                <!-- أرقام هواتف إضافية -->
                                <div class="col-sm-6">
                                    <label class="form-label">أرقام هواتف إضافية</label>
                                    <div id="additional-phones">
                                        <div class="phone-entry mb-2">
                                            <div class="input-group">
                                                <input type="text" name="phones[0][phone]"
                                                    class="form-control phone-input additional-phone"
                                                    placeholder="05xxxxxxxx" maxlength="10">
                                                <select name="phones[0][type]" class="form-select"
                                                    style="max-width: 100px;">
                                                    <option value="mobile">جوال</option>
                                                    <option value="home">منزل</option>
                                                    <option value="work">عمل</option>
                                                    <option value="other">أخرى</option>
                                                </select>
                                                <button type="button"
                                                    class="btn btn-outline-success js-add-phone-field-btn">
                                                    <i class="ti ti-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="account_number">رقم الحساب</label>
                                    <input type="text" name="account_number" id="account_number" class="form-control"
                                        placeholder="ادخل رقم الحساب" value="{{ old('account_number') }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="commission_rate">نسبة العمولة (%)</label>
                                    <input type="number" name="commission_rate" id="commission_rate"
                                        class="form-control" placeholder="ادخل نسبة العمولة" step="0.01"
                                        min="0" max="100" value="{{ old('commission_rate') }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="contract_date">تاريخ التعاقد</label>
                                    <input type="text" name="contract_date" id="contract_date"
                                        class="form-control flatpickr-date" placeholder="اختر تاريخ التعاقد"
                                        value="{{ old('contract_date') }}" />
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="default_date">تاريخ التعثر</label>
                                    <input type="text" name="default_date" id="default_date"
                                        class="form-control flatpickr-date" placeholder="اختر تاريخ التعثر"
                                        value="{{ old('default_date') }}" />
                                </div>


                                <div class="mt-4 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <div class="d-flex gap-2">
                                        <button type="submit" class="btn btn-primary btn-next btn-submit">
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
@endsection
