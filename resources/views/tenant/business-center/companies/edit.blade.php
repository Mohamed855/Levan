@extends('tenant.layouts.layoutMaster')

@section('title', 'تعديل الشركة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/tenant/business-center/companies/validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#company-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">معلومات الشركة</span>
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

                    <form id="wizard-validation-form" action="{{ tenant_route_name('companies.update', $company) }}"
                        method="POST" enctype="multipart/form-data" novalidate data-form-validate
                        data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                        @csrf
                        @method('PUT')

                        <div id="company-info-validation" class="content">
                            <div class="row g-4 mb-4">
                                {{-- اسم الشركة --}}
                                <x-form.controls.input name="name" id="name" label="اسم الشركة"
                                    placeholder="ادخل اسم الشركة" :value="old('name', $company->name)" required col="col-md-6" />

                                {{-- نسبة المستهدف --}}
                                <x-form.controls.input type="number" name="target_percentage" id="target_percentage"
                                    label="نسبة المستهدف الشهري (%)" placeholder="مثال: 10" :value="old('target_percentage', $company->current_target_percentage)" step="0.01"
                                    min="0" max="100"
                                    help="النسبة المئوية من إجمالي المحفظة (مثال: 10 تعني 10%)" col="col-md-6">
                                    @if ($company->currentTarget)
                                        <div class="alert alert-info py-2 mt-2 mb-0">
                                            <small>
                                                <i class="ti ti-info-circle me-1"></i>
                                                <strong>المستهدف الحالي:</strong>
                                                {{ number_format($company->current_target_percentage, 2) }}%
                                                (ساري منذ {{ $company->currentTarget->effective_from->format('Y-m-d') }})
                                            </small>
                                        </div>
                                    @endif
                                </x-form.controls.input>

                                {{-- شعار الشركة --}}
                                <x-form.controls.file name="logo" id="logo" label="شعار الشركة"
                                    :value="$company->logo_url ? tasset($company->logo_url) : null" accept="image/jpeg,image/png,image/webp"
                                    help="الأنواع: JPG, PNG, WebP — الحجم الأقصى: 2MB" :max-size="2" col="col-md-6" />
                            </div>

                            {{-- المفوضون --}}
                            <div class="col-12 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <label class="form-label mb-0">المفوّضون</label>
                                    <button type="button" class="btn btn-primary btn-sm" id="btn-add-person">
                                        <i class="ti ti-plus me-1"></i>إضافة مفوّض
                                    </button>
                                </div>

                                <div id="authorized-people-list">
                                    @php
                                        $oldPeople = old(
                                            'authorized_people',
                                            $company->authorizedPeople
                                                ->map(
                                                    fn($p) => [
                                                        'name' => $p->name,
                                                        'phone' => $p->phone,
                                                        'email' => $p->email,
                                                        'position' => $p->position,
                                                        'is_default' => $p->is_default,
                                                    ],
                                                )
                                                ->toArray(),
                                        );
                                    @endphp
                                    @if (!empty($oldPeople))
                                        @foreach ($oldPeople as $idx => $person)
                                            <div class="authorized-person-item mb-3 p-3 border rounded">
                                                <div class="row g-4">
                                                    <div class="col-md-3">
                                                        <label class="form-label small">اسم المفوّض</label>
                                                        <input type="text" class="form-control"
                                                            name="authorized_people[{{ $idx }}][name]"
                                                            placeholder="ادخل اسم المفوّض"
                                                            value="{{ $person['name'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small">رقم الهاتف</label>
                                                        <input type="text" class="form-control phone-input"
                                                            name="authorized_people[{{ $idx }}][phone]"
                                                            placeholder="ادخل رقم الهاتف"
                                                            value="{{ $person['phone'] ?? '' }}" maxlength="10">
                                                    </div>
                                                    <div class="col-md-3">
                                                        <label class="form-label small">البريد الإلكتروني</label>
                                                        <input type="email" class="form-control email-input"
                                                            name="authorized_people[{{ $idx }}][email]"
                                                            placeholder="ادخل البريد الإلكتروني"
                                                            value="{{ $person['email'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-2">
                                                        <label class="form-label small">المنصب</label>
                                                        <input type="text" class="form-control"
                                                            name="authorized_people[{{ $idx }}][position]"
                                                            placeholder="ادخل المنصب"
                                                            value="{{ $person['position'] ?? '' }}">
                                                    </div>
                                                    <div class="col-md-1 d-flex align-items-center justify-content-center">
                                                        <div class="form-check mt-4">
                                                            <input class="form-check-input default-person-radio"
                                                                type="radio" name="default_person"
                                                                value="{{ $idx }}"
                                                                {{ $person['is_default'] ?? false ? 'checked' : '' }}>
                                                            <label class="form-check-label small text-muted">
                                                                افتراضي
                                                            </label>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-1 d-flex align-items-end">
                                                        <button type="button"
                                                            class="btn btn-outline-danger btn-sm btn-remove-person w-100">
                                                            حذف
                                                        </button>
                                                    </div>
                                                    <input type="hidden"
                                                        name="authorized_people[{{ $idx }}][is_default]"
                                                        value="{{ $person['is_default'] ?? false ? '1' : '0' }}"
                                                        class="is-default-input">
                                                </div>
                                            </div>
                                        @endforeach
                                    @endif
                                </div>
                            </div>

                            {{-- أزرار التحكم --}}
                            <div class="col-12 d-flex justify-content-between mt-4">
                                <x-form.wizard.previous-button :href="tenant_route_name('companies.index')" text="العودة" />
                                <button type="submit" class="btn btn-primary btn-next btn-submit">
                                    تحديث
                                </button>
                            </div>
                        </div>
                </div>
                </form>
            </div>
        </div>
    </div>
    </div>
@endsection
