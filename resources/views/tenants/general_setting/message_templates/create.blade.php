@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة قالب جديد')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/select2/select2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js',
'resources/assets/vendor/libs/@form-validation/popular.js',
'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/select2/select2.js'])
@endsection

@section('toastr')
@vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
<script>
    window.variables = @json($variables ?? []);
        window.groupedVariables = @json($groupedVariables ?? []);
        window.routes = {
            getVariablesByCategory: "{{ tenant_route_name('general-settings.message-templates.get-variables-by-category') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
</script>
@vite(['resources/assets/js/tenants/general-settings/message_templates/message-templates-validation.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-12 mb-6">
        <div id="wizard-validation" class="bs-stepper mt-2">
            <div class="bs-stepper-header">
                <div class="step" data-target="#basic-info-validation">
                    <button type="button" class="step-trigger">
                        <span class="bs-stepper-circle">1</span>
                        <span class="bs-stepper-label mt-1">
                            <span class="bs-stepper-title">المعلومات الأساسية</span>
                            <span class="bs-stepper-subtitle">اسم القالب • الفئة • القناة</span>
                        </span>
                    </button>
                </div>
                <div class="line">
                    <i class="ti ti-chevron-right"></i>
                </div>
                <div class="step" data-target="#content-info-validation">
                    <button type="button" class="step-trigger">
                        <span class="bs-stepper-circle">2</span>
                        <span class="bs-stepper-label">
                            <span class="bs-stepper-title">محتوى القالب</span>
                            <span class="bs-stepper-subtitle">كتابة محتوى الرسالة</span>
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

                <form id="wizard-validation-form"
                    action="{{ tenant_route_name('general-settings.message-templates.store') }}" method="POST"
                    onSubmit="return false">
                    @csrf

                    <!-- المعلومات الأساسية -->
                    <div id="basic-info-validation" class="content">
                        <div class="row g-6">
                            <div class="col-sm-6">
                                <label class="form-label" for="name">اسم القالب <span
                                        class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control"
                                    placeholder="ادخل اسم القالب" value="{{ old('name') }}" />
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label" for="category">فئة القالب <span
                                        class="text-danger">*</span></label>
                                <select name="category" id="category" class="form-control select2"
                                    data-placeholder="اختر الفئة">
                                    <option value=""></option>
                                    @foreach ($categories as $key => $value)
                                    <option value="{{ $key }}" {{ old('category')==$key ? 'selected' : '' }}>{{ $value
                                        }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-sm-6">
                                <label class="form-label" for="channel">القناة <span
                                        class="text-danger">*</span></label>
                                <select name="channel" id="channel" class="form-control select2"
                                    data-placeholder="اختر القناة">
                                    <option value=""></option>
                                    @foreach ($channels as $key => $value)
                                    <option value="{{ $key }}" {{ old('channel')==$key ? 'selected' : '' }}>{{ $value }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="col-12 d-flex justify-content-between">
                                <button type="button" class="btn btn-label-secondary btn-prev" disabled>
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

                    <!-- محتوى القالب -->
                    <div id="content-info-validation" class="content">
                        <div class="row g-6">
                            <!-- المتغيرات المتاحة -->
                            <div class="col-12 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0"
                                            style="color: rgb(var(--bs-primary-rgb)); font-weight: 600;">
                                            <i class="ti ti-template me-2" style="font-size: 1.2rem;"></i>
                                            المتغيرات المتاحة
                                        </h5>
                                    </div>
                                    <div class="card-body" style="padding: 1rem;">
                                        <div id="variables-container">
                                            @if (isset($groupedVariables) && !empty($groupedVariables))
                                            <!-- كاردات المجموعات - حجم أصغر -->
                                            <div class="row g-2 mb-3">
                                                @foreach ($groupedVariables as $groupName => $groupVariables)
                                                <div class="col-md-2 col-sm-4 col-6">
                                                    <div class="variable-group-card" data-group="{{ $loop->index }}">
                                                        <div class="card h-100 variable-group-header"
                                                            style="cursor: pointer; border: 1.5px solid rgba(var(--bs-primary-rgb), 0.15); transition: all 0.3s ease; box-shadow: 0 1px 3px rgba(0,0,0,0.08);">
                                                            <div class="card-body text-center"
                                                                style="padding: 0.75rem 0.5rem;">
                                                                <div class="mb-1">
                                                                    <i class="ti ti-folder text-primary"
                                                                        style="font-size: 1.5rem;"></i>
                                                                </div>
                                                                <h6 class="card-title mb-1"
                                                                    style="font-weight: 600; font-size: 0.85rem; color: rgb(var(--bs-primary-rgb));">
                                                                    {{ $groupName }}
                                                                </h6>
                                                                <span class="badge bg-label-primary"
                                                                    style="font-size: 0.65rem; padding: 0.15rem 0.4rem;">
                                                                    {{ count($groupVariables) }} متغير
                                                                </span>
                                                                <div class="mt-1">
                                                                    <i class="ti ti-chevron-down transition-icon text-muted"
                                                                        style="font-size: 0.85rem; transition: transform 0.3s ease;"></i>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>

                                            <!-- المتغيرات المخفية - حجم أصغر -->
                                            @foreach ($groupedVariables as $groupName => $groupVariables)
                                            <div class="variable-group-content" data-group="{{ $loop->index }}"
                                                style="display: none;">
                                                <div class="card"
                                                    style="border: 1.5px solid rgba(var(--bs-primary-rgb), 0.12); background: rgba(var(--bs-primary-rgb), 0.02);">
                                                    <div class="card-header"
                                                        style="padding: 0.6rem 1rem; border-bottom: 1px solid rgba(var(--bs-primary-rgb), 0.1);">
                                                        <h6 class="mb-0"
                                                            style="color: rgb(var(--bs-primary-rgb)); font-weight: 600; font-size: 0.9rem;">
                                                            <i class="ti ti-variables me-1"
                                                                style="font-size: 0.95rem;"></i>
                                                            متغيرات {{ $groupName }}
                                                        </h6>
                                                    </div>
                                                    <div class="card-body" style="padding: 0.75rem;">
                                                        <div class="row g-2">
                                                            @foreach ($groupVariables as $variable => $description)
                                                            <div class="col-md-2 col-sm-3 col-4">
                                                                <button type="button"
                                                                    class="btn btn-outline-primary variable-btn w-100"
                                                                    data-variable="{{ $variable }}"
                                                                    title="{{ $description }}"
                                                                    style="font-size: 0.7rem; padding: 0.35rem 0.5rem; border-radius: 4px; transition: all 0.2s ease; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-width: 1px;">
                                                                    {{ $description }}
                                                                </button>
                                                            </div>
                                                            @endforeach
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            @endforeach
                                            @else
                                            <div class="text-center py-4" style="color: #6c757d;">
                                                <i class="ti ti-info-circle"
                                                    style="font-size: 2.5rem; margin-bottom: 0.75rem; opacity: 0.5;"></i>
                                                <p class="mb-0" style="font-size: 0.95rem;">اختر فئة القالب لعرض
                                                    المتغيرات المتاحة</p>
                                            </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- محتوى القالب -->
                            <div class="col-12">
                                <label class="form-label" for="content">محتوى القالب <span
                                        class="text-danger">*</span></label>
                                <textarea name="content" id="content" class="form-control" rows="10"
                                    placeholder="اكتب محتوى القالب...">{{ old('content') }}</textarea>
                                <div class="form-text">
                                    <small class="text-muted">
                                        <i class="ti ti-info-circle me-1"></i>
                                        استخدم المتغيرات أعلاه لتخصيص محتوى الرسالة
                                    </small>
                                </div>
                            </div>

                            <div class="col-12 d-flex justify-content-between">
                                <button type="button" class="btn btn-label-secondary btn-prev">
                                    <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                    <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                </button>
                                <button type="submit" class="btn btn-primary btn-next btn-submit">
                                    <span class="align-middle d-sm-inline-block d-none me-sm-2">حفظ</span>
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