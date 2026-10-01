@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل العقد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.routes = {
            generateNumber: "{{ tenant_route_name('contracts.generate-number') }}"
        };
        window.initialCommissionIndex = {{ count(old('commissions', $contractCommissions)) }};
    </script>
    @vite(['resources/assets/js/tenants/business-center/contracts/validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#contract-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">المعلومات الأساسية</span>
                                <span class="bs-stepper-subtitle">بيانات العقد والشركة</span>
                            </span>
                        </button>
                    </div>
                    <div class="line"><i class="ti ti-chevron-right"></i></div>
                    <div class="step" data-target="#commission-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">نظام العمولة والملاحظات</span>
                                <span class="bs-stepper-subtitle">شرائح العمولات</span>
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

                    <form id="wizard-validation-form" action="{{ tenant_route_name('contracts.update', $contract) }}"
                        method="POST" onSubmit="return false">
                        @csrf
                        @method('PUT')

                        <div id="contract-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-sm-6">
                                    <label class="form-label" for="company_id">الشركة <span
                                            class="text-danger">*</span></label>
                                    <select id="company_id" name="company_id" class="select2 form-select"
                                        data-placeholder="اختر شركة">
                                        <option></option>
                                        @foreach ($companies as $company)
                                            <option value="{{ $company->id }}"
                                                {{ old('company_id', $contract->company_id) == $company->id ? 'selected' : '' }}>
                                                {{ $company->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="contract_number">رقم العقد</label>
                                    <input type="text" id="contract_number" name="contract_number" class="form-control"
                                        value="{{ old('contract_number', $contract->contract_number) }}"
                                        placeholder="ادخل رقم العقد">
                                </div>

                                <div class="col-sm-4">
                                    <label class="form-label" for="start_date">تاريخ البداية <span
                                            class="text-danger">*</span></label>
                                    <input type="date" id="start_date" name="start_date" class="form-control"
                                        value="{{ old('start_date', $contract->start_date?->format('Y-m-d')) }}">
                                </div>

                                <div class="col-sm-4">
                                    <label class="form-label" for="end_date">تاريخ النهاية <span
                                            class="text-danger">*</span></label>
                                    <input type="date" id="end_date" name="end_date" class="form-control"
                                        value="{{ old('end_date', $contract->end_date?->format('Y-m-d')) }}">
                                </div>

                                <div class="col-sm-4">
                                    <label class="form-label" for="duration_readonly">المدة</label>
                                    <input type="text" id="duration_readonly" class="form-control" value="0"
                                        disabled>
                                    <input type="hidden" id="duration_months" name="duration_months"
                                        value="{{ old('duration_months', $contract->duration_months) }}">
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev" disabled>
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span>السابق</span>
                                    </button>
                                    <button type="button" class="btn btn-primary btn-next">
                                        <span class="me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div id="commission-info-validation" class="content">
                            <div class="row g-6">
                                <div class="col-12">
                                    <label class="form-label" for="commission_types">أنواع نظام العمولة <span
                                            class="text-danger">*</span></label>
                                    @php
                                        $existingTypes = old(
                                            'commission_types',
                                            collect($contractCommissions)
                                                ->pluck('commission_type')
                                                ->unique()
                                                ->values()
                                                ->toArray(),
                                        );
                                    @endphp
                                    <select id="commission_types" name="commission_types[]" class="select2 form-select"
                                        multiple data-placeholder="اختر أنواع العمولة">
                                        <option value="procedure"
                                            {{ in_array('procedure', $existingTypes) ? 'selected' : '' }}>
                                            حسب الإجراء
                                        </option>
                                        <option value="duration"
                                            {{ in_array('duration', $existingTypes) ? 'selected' : '' }}>
                                            حسب المدة
                                        </option>
                                        <option value="delinquency"
                                            {{ in_array('delinquency', $existingTypes) ? 'selected' : '' }}>
                                            حسب مدة التعثر
                                        </option>
                                    </select>
                                    <input type="hidden" id="commission_type" name="commission_type"
                                        value="{{ old('commission_type', $contract->commission_type) }}">
                                </div>

                                <div class="col-12">
                                    <div id="commission-type-buttons"
                                        class="mb-3 {{ count($existingTypes) > 0 ? '' : 'd-none' }}">
                                        <div class="d-flex gap-2 flex-wrap"></div>
                                    </div>
                                    <div id="commissions-wrapper">
                                        @foreach (old('commissions', $contractCommissions) as $index => $commission)
                                            @php
                                                $commissionType = is_array($commission)
                                                    ? $commission['commission_type'] ?? ''
                                                    : $commission->commission_type ?? '';
                                                $procedureType = is_array($commission)
                                                    ? $commission['procedure_type'] ?? ''
                                                    : $commission->procedure_type ?? '';
                                                $durationFrom = is_array($commission)
                                                    ? $commission['duration_from'] ?? ''
                                                    : $commission->duration_from ?? '';
                                                $durationTo = is_array($commission)
                                                    ? $commission['duration_to'] ?? ''
                                                    : $commission->duration_to ?? '';
                                                $commissionRate = is_array($commission)
                                                    ? $commission['commission_rate'] ?? ''
                                                    : $commission->commission_rate ?? '';

                                                $typeLabels = [
                                                    'procedure' => 'حسب الإجراء',
                                                    'duration' => 'حسب المدة',
                                                    'delinquency' => 'حسب مدة التعثر',
                                                ];
                                                $typeLabel = $typeLabels[$commissionType] ?? $commissionType;

                                                $typeCount = collect($contractCommissions)
                                                    ->filter(function ($c) use ($commissionType) {
                                                        $cType = is_array($c)
                                                            ? $c['commission_type'] ?? ''
                                                            : $c->commission_type ?? '';
                                                        return $cType === $commissionType;
                                                    })
                                                    ->keys()
                                                    ->search($index);
                                                $typeNumber = $typeCount !== false ? $typeCount + 1 : 1;
                                            @endphp
                                            <div class="commission-row border rounded p-3 mb-3 bg-white"
                                                data-index="{{ $index }}" data-type="{{ $commissionType }}">
                                                <div
                                                    class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                                                    <h6 class="mb-0 text-primary fw-bold">
                                                        <i class="ti ti-percentage me-1"></i>
                                                        شريحة <span
                                                            class="commission-type-label">{{ $typeLabel }}</span> -
                                                        <span class="commission-number">{{ $typeNumber }}</span>
                                                    </h6>
                                                    <button type="button"
                                                        class="btn btn-danger btn-sm remove-commission-btn"
                                                        title="حذف الشريحة">
                                                        <i class="ti ti-trash"></i>
                                                    </button>
                                                </div>

                                                <div class="row g-4">
                                                    <input type="hidden"
                                                        name="commissions[{{ $index }}][commission_type]"
                                                        value="{{ $commissionType }}">

                                                    @if ($commissionType === 'procedure')
                                                        <div class="col-md-6 procedure-type-col">
                                                            <label class="form-label fw-semibold">نوع الإجراء <span
                                                                    class="text-danger">*</span></label>
                                                            <select
                                                                name="commissions[{{ $index }}][procedure_type]"
                                                                class="select2-commission form-select">
                                                                <option value="">اختر نوع الإجراء</option>
                                                                <option value="friendly"
                                                                    {{ $procedureType === 'friendly' ? 'selected' : '' }}>
                                                                    ودي</option>
                                                                <option value="legal"
                                                                    {{ $procedureType === 'legal' ? 'selected' : '' }}>
                                                                    قانوني
                                                                </option>
                                                            </select>
                                                        </div>
                                                        <div class="col-md-6 commission-rate-col">
                                                            <label class="form-label fw-semibold">نسبة العمولة (%) <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="number" step="0.01"
                                                                name="commissions[{{ $index }}][commission_rate]"
                                                                class="form-control commission-rate-input"
                                                                placeholder="0.00" min="0.01" max="100"
                                                                value="{{ $commissionRate }}">
                                                        </div>
                                                    @elseif($commissionType === 'duration' || $commissionType === 'delinquency')
                                                        <div class="col-md-4 duration-from-col duration-fields">
                                                            <label class="form-label fw-semibold">من (يوم) <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="number"
                                                                name="commissions[{{ $index }}][duration_from]"
                                                                class="form-control" placeholder="1" min="1"
                                                                step="1" value="{{ $durationFrom }}">
                                                        </div>
                                                        <div class="col-md-4 duration-to-col duration-fields">
                                                            <label class="form-label fw-semibold">إلى (يوم) <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="number"
                                                                name="commissions[{{ $index }}][duration_to]"
                                                                class="form-control" placeholder="30" min="1"
                                                                step="1" value="{{ $durationTo }}">
                                                        </div>
                                                        <div class="col-md-4 commission-rate-col">
                                                            <label class="form-label fw-semibold">نسبة العمولة (%) <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="number" step="0.01"
                                                                name="commissions[{{ $index }}][commission_rate]"
                                                                class="form-control commission-rate-input"
                                                                placeholder="0.00" min="0.01" max="100"
                                                                value="{{ $commissionRate }}">
                                                        </div>
                                                    @else
                                                        <div class="col-md-12 commission-rate-col">
                                                            <label class="form-label fw-semibold">نسبة العمولة (%) <span
                                                                    class="text-danger">*</span></label>
                                                            <input type="number" step="0.01"
                                                                name="commissions[{{ $index }}][commission_rate]"
                                                                class="form-control commission-rate-input"
                                                                placeholder="0.00" min="0.01" max="100"
                                                                value="{{ $commissionRate }}">
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label" for="notes">ملاحظات</label>
                                    <textarea id="notes" name="notes" class="form-control" rows="4" placeholder="اكتب أي ملاحظات...">{{ old('notes', $contract->notes) }}</textarea>
                                </div>

                                <div class="col-12 d-flex justify-content-between">
                                    <button type="button" class="btn btn-secondary btn-prev">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span>السابق</span>
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-submit">
                                        <span>تحديث</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

            </div>
        </div>
    </div>

    <template id="commission-row-template">
        <div class="commission-row border rounded p-3 mb-3 bg-white" data-index="__INDEX__" data-type="__TYPE__">
            <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                <h6 class="mb-0 text-primary fw-bold">
                    <i class="ti ti-percentage me-1"></i>
                    شريحة <span class="commission-type-label">__TYPE_LABEL__</span> -
                    <span class="commission-number">__NUMBER__</span>
                </h6>
                <button type="button" class="btn btn-danger btn-sm remove-commission-btn" title="حذف الشريحة">
                    <i class="ti ti-trash"></i>
                </button>
            </div>

            <div class="row g-4">
                <input type="hidden" name="commissions[__INDEX__][commission_type]" value="__TYPE__">

                <div class="col-md-6 procedure-type-col" style="display:none;">
                    <label class="form-label fw-semibold">نوع الإجراء <span class="text-danger">*</span></label>
                    <select name="commissions[__INDEX__][procedure_type]" class="select2-commission form-select">
                        <option value="">اختر نوع الإجراء</option>
                        <option value="friendly">ودي</option>
                        <option value="legal">قانوني</option>
                    </select>
                </div>

                <div class="col-md-4 duration-from-col duration-fields" style="display:none;">
                    <label class="form-label fw-semibold">من (يوم) <span class="text-danger">*</span></label>
                    <input type="number" name="commissions[__INDEX__][duration_from]" class="form-control"
                        placeholder="1" min="1" step="1">
                </div>

                <div class="col-md-4 duration-to-col duration-fields" style="display:none;">
                    <label class="form-label fw-semibold">إلى (يوم) <span class="text-danger">*</span></label>
                    <input type="number" name="commissions[__INDEX__][duration_to]" class="form-control"
                        placeholder="30" min="1" step="1">
                </div>

                <div class="col-md-4 commission-rate-col">
                    <label class="form-label fw-semibold">نسبة العمولة (%) <span class="text-danger">*</span></label>
                    <input type="number" step="0.01" name="commissions[__INDEX__][commission_rate]"
                        class="form-control commission-rate-input" placeholder="0.00" min="0.01" max="100">
                </div>
            </div>
        </div>
    </template>
@endsection
