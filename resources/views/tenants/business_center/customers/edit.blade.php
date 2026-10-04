@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل العميل')

{{-- ▸ Vendor assets --}}
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection
@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

{{-- ▸ Page-level JS --}}
@section('page-script')
    <script>
        window.routes = {
            portfoliosByCompany: "{{ tenant_route_name('customers.portfolios-by-company') }}",
            fieldsByPortfolio: "{{ tenant_route_name('customers.fields-by-portfolio') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
        window.initialDynamic = @json($customer->dynamic);
    </script>

    @vite(['resources/assets/js/tenants/business-center/customers/dynamic-fields/customers-dynamic-fields.js', 'resources/assets/js/tenants/business-center/customers/phone-fields/customers-phone-fields.js', 'resources/assets/js/tenants/business-center/customers/basic/validation.js', 'resources/assets/js/tenants/business-center/customers/name-toggle/customers-name-toggle.js'])
@endsection


{{-- ▸ Page content --}}
@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">

                {{-- ▼ Header --}}
                <div class="bs-stepper-header">
                    <div class="step" data-target="#personal-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">المعلومات الشخصية</span>
                                <span class="bs-stepper-subtitle">البيانات الأساسية</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

                    <div class="step" data-target="#company-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">معلومات الشركة</span>
                                <span class="bs-stepper-subtitle">المديونية والعلاقة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>

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

                {{-- ▼ Content --}}
                <div class="bs-stepper-content">
                    @if (isset($errors) && $errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $e)
                                    <li>{{ $e }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form" action="{{ tenant_route_name('customers.update', $customer) }}"
                        method="POST" onsubmit="return false">
                        @csrf @method('PUT')

                        {{-- ───── STEP 1 ─ المعلومات الشخصية + الشركة / المحفظة ───── --}}
                        <div id="personal-info-validation" class="content">
                            <div class="row g-6">

                                {{-- الشركة --}}
                                <div class="col-sm-6">
                                    <label class="form-label" for="company_id">الشركة <span
                                            class="text-danger">*</span></label>
                                    <select name="company_id" id="company_id" class="form-control select2"
                                        data-placeholder="اختر الشركة">
                                        <option value=""></option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}"
                                                {{ old('company_id', $customer->company_id) == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- المحفظة --}}
                                <div class="col-sm-6">
                                    <label class="form-label" for="portfolio_id">المحفظة <span
                                            class="text-danger">*</span></label>
                                    <select name="portfolio_id" id="portfolio_id" class="form-control select2"
                                        data-placeholder="اختر المحفظة">
                                        <option value=""></option>
                                        @foreach ($portfolios as $portfolio)
                                            <option value="{{ $portfolio->id }}" data-company="{{ $portfolio->company_id }}"
                                                data-has-split="{{ $portfolio->has_split_names ? 1 : 0 }}"
                                                {{ old('portfolio_id', $customer->portfolio_id) == $portfolio->id ? 'selected' : '' }}>
                                                {{ $portfolio->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- الاسم الكامل --}}
                                <div class="col-sm-6" id="full-name-wrapper">
                                    <label class="form-label" for="name">اسم العميل <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="name" name="name" class="form-control"
                                        value="{{ old('name', $customer->name) }}" placeholder="ادخل اسم العميل">
                                </div>

                                {{-- الاسم المقسَّم --}}
                                <div id="split-names-wrapper" class="row g-4 d-none">
                                    <div class="col-sm-3">
                                        <label class="form-label" for="first_name">الاسم الأول <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="first_name" name="first_name" class="form-control"
                                            value="{{ old('first_name', $customer->first_name) }}">
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="form-label" for="father_name">اسم الأب</label>
                                        <input type="text" id="father_name" name="father_name" class="form-control"
                                            value="{{ old('father_name', $customer->father_name) }}">
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="form-label" for="grandfather_name">اسم الجد</label>
                                        <input type="text" id="grandfather_name" name="grandfather_name"
                                            class="form-control"
                                            value="{{ old('grandfather_name', $customer->grandfather_name) }}">
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="form-label" for="family_name">اسم العائلة <span
                                                class="text-danger">*</span></label>
                                        <input type="text" id="family_name" name="family_name" class="form-control"
                                            value="{{ old('family_name', $customer->family_name) }}">
                                    </div>
                                </div>

                                {{-- بقية الحقول --}}
                                <div class="col-sm-6">
                                    <label class="form-label" for="customer_type">نوع العميل <span
                                            class="text-danger">*</span></label>
                                    <select name="customer_type" id="customer_type" class="form-control select2"
                                        data-placeholder="اختر النوع">
                                        <option value=""></option>
                                        <option value="individual"
                                            {{ old('customer_type', $customer->customer_type) == 'individual' ? 'selected' : '' }}>
                                            فردي</option>
                                        <option value="company"
                                            {{ old('customer_type', $customer->customer_type) == 'company' ? 'selected' : '' }}>
                                            شركة</option>
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="identity_number">رقم الهوية <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="identity_number" name="identity_number" maxlength="10"
                                        class="form-control"
                                        value="{{ old('identity_number', $customer->identity_number) }}">
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="nationality">الجنسية</label>
                                    <select name="nationality" id="nationality" class="form-control select2"
                                        data-placeholder="اختر الجنسية">
                                        <option value=""></option>
                                        @foreach ($countries as $country)
                                            <option value="{{ $country->id }}"
                                                {{ old('nationality', $customer->nationality) == $country->id ? 'selected' : '' }}>
                                                {{ $country->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- أزرار التنقل --}}
                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev" disabled>
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ───── STEP 2 ─ مسؤول العلاقة + الحقول الديناميكية ───── --}}
                        <div id="company-info-validation" class="content">
                            <div class="row g-6">



                                @can('customers.edit_all')
                                    <div class="col-sm-4">
                                        <label class="form-label" for="relationship_manager_id">مسؤول العلاقة</label>
                                        <select name="relationship_manager_id" id="relationship_manager_id"
                                            class="form-control select2" data-placeholder="اختر مسؤول العلاقة">
                                            <option value=""></option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}"
                                                    {{ old('relationship_manager_id', $customer->relationship_manager_id) == $user->id ? 'selected' : '' }}>
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <div class="col-sm-4">
                                        <label class="form-label" for="">مسؤول العلاقة</label>
                                        <div class="input-group">
                                            <input type="text" id="" class="form-control"
                                                value="{{ $customer->relationshipManager?->name }}" readonly
                                                style="background-color: #e9ecef; border-left: none;">
                                        </div>
                                    </div>

                                @endcan


                                {{-- الحقول الإضافية التابعة للمحفظة --}}
                                <div class="col-12 mt-4">
                                    <div id="dynamic-fields" class="row g-4"></div>
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- ───── STEP 3 ─ بيانات الاتصال ───── --}}
                        <div id="contact-info-validation" class="content">
                            <div class="row g-6">

                                <div class="col-sm-6">
                                    <label class="form-label" for="phone">رقم الهاتف الأساسي <span
                                            class="text-danger">*</span></label>
                                    <input type="text" id="phone" name="phone" maxlength="10"
                                        class="form-control phone-input" value="{{ old('phone', $customer->phone) }}"
                                        placeholder="ادخل رقم الهاتف">
                                </div>

                                {{-- أرقام إضافية --}}
                                <div class="col-sm-6">
                                    <label class="form-label">أرقام هواتف إضافية</label>
                                    <div id="additional-phones">
                                        @forelse ($customer->phones as $i=>$phone)
                                            <div class="phone-entry mb-2">
                                                <div class="input-group">
                                                    <input type="text" name="phones[{{ $i }}][phone]"
                                                        maxlength="10" class="form-control phone-input additional-phone"
                                                        value="{{ $phone->phone }}">
                                                    <select name="phones[{{ $i }}][type]" class="form-select"
                                                        style="max-width:100px;">
                                                        <option value="mobile"
                                                            {{ $phone->type == 'mobile' ? 'selected' : '' }}>
                                                            جوال</option>
                                                        <option value="home"
                                                            {{ $phone->type == 'home' ? 'selected' : '' }}>
                                                            منزل</option>
                                                        <option value="work"
                                                            {{ $phone->type == 'work' ? 'selected' : '' }}>
                                                            عمل</option>
                                                        <option value="other"
                                                            {{ $phone->type == 'other' ? 'selected' : '' }}>
                                                            أخرى</option>
                                                    </select>
                                                    @if ($loop->first)
                                                        <button type="button"
                                                            class="btn btn-outline-success js-add-phone-field-btn">
                                                            <i class="ti ti-plus"></i>
                                                        </button>
                                                    @else
                                                        <button type="button"
                                                            class="btn btn-outline-danger js-remove-phone-field-btn">
                                                            <i class="ti ti-minus"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </div>
                                        @empty
                                            <div class="phone-entry mb-2">
                                                <div class="input-group">
                                                    <input type="text" name="phones[0][phone]" maxlength="10"
                                                        class="form-control phone-input additional-phone">
                                                    <select name="phones[0][type]" class="form-select"
                                                        style="max-width:100px;">
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
                                        @endforelse
                                    </div>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="account_number">رقم الحساب</label>
                                    <input type="text" id="account_number" name="account_number" class="form-control"
                                        value="{{ old('account_number', $customer->account_number) }}">
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="commission_rate">نسبة العمولة (%)</label>
                                    <input type="number" step="0.01" min="0" max="100"
                                        id="commission_rate" name="commission_rate" class="form-control"
                                        value="{{ old('commission_rate', $customer->commission_rate) }}">
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="contract_date">تاريخ التعاقد</label>
                                    <input type="text" id="contract_date" name="contract_date"
                                        class="form-control flatpickr-date"
                                        value="{{ old('contract_date', $customer->contract_date?->format('Y-m-d')) }}">
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="default_date">تاريخ التعثر</label>
                                    <input type="text" id="default_date" name="default_date"
                                        class="form-control flatpickr-date"
                                        value="{{ old('default_date', $customer->default_date?->format('Y-m-d')) }}">
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </button>

                                    @if (auth()->user()->can('تحديث مباشر للبيانات'))
                                        <button type="submit" class="btn btn-primary btn-next btn-submit">
                                            <span class="align-middle d-sm-inline-block d-none me-sm-2">تحديث</span>
                                        </button>
                                    @else
                                        <button type="submit" class="btn btn-primary btn-sm btn-next btn-submit">
                                            <span class="align-middle d-sm-inline-block d-none me-sm-2">إرسال الطلب
                                            </span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
