@extends('tenants.layouts.layoutMaster')

@section('title', 'إضافة هدف تحصيل جديد')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/flatpickr/flatpickr.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js', 'resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/flatpickr/flatpickr.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    <script>
        window.routes = {};
        window.csrfToken = "{{ csrf_token() }}";
        window.months = @json($months);
        window.years = @json($years);

        window.oldValues = {
            team_ids: @json(old('team_ids', [])),
            user_id: "{{ old('user_id') }}",
            target_type: "{{ old('target_type') }}",
            target_amount: "{{ old('target_amount') }}",
            target_month: "{{ old('target_month') }}",
            target_year: "{{ old('target_year') }}",
            custom_start_date: "{{ old('custom_start_date') }}",
            custom_end_date: "{{ old('custom_end_date') }}"
        };

        window.hasValidationErrors = {{ $errors->any() ? 'true' : 'false' }};
    </script>
    @vite(['resources/assets/js/tenants/financial_collection/targets/validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#target-info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">معلومات الهدف</span>
                                <span class="bs-stepper-subtitle">البيانات الاساسية</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <h6 class="alert-heading mb-2"><i class="ti ti-alert-triangle me-2"></i>يرجى تصحيح الأخطاء
                                التالية:</h6>
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form id="wizard-validation-form"
                        action="{{ tenant_route_name('financial-collections.collection-targets.store') }}" method="POST"
                        onSubmit="return false">
                        @csrf

                        <!-- معلومات الهدف -->
                        <div id="target-info-validation" class="content">
                            <div class="row g-6">

                                {{-- السطر الأول: المجموعات والمستخدم --}}
                                <div class="col-sm-6">
                                    <label class="form-label" for="team_ids">المجموعات <span
                                            class="text-danger">*</span></label>
                                    <select name="team_ids[]" id="team_ids"
                                        class="form-control select2 @error('team_ids') is-invalid @enderror"
                                        data-allow-clear="true" data-placeholder="اختر المجموعات" multiple>
                                        @foreach ($teams as $team)
                                            <option value="{{ $team->id }}"
                                                {{ in_array($team->id, old('team_ids', [])) ? 'selected' : '' }}>
                                                {{ $team->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('team_ids')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="user_id">المستخدم <span
                                            class="text-danger">*</span></label>
                                    <select name="user_id" id="user_id"
                                        class="form-control select2 @error('user_id') is-invalid @enderror"
                                        data-allow-clear="true" data-placeholder="اختر المستخدم">
                                        <option value=""></option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}"
                                                data-team-id="{{ $user->team_id }}"
                                                {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('user_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="col-sm-6">
                                    <label class="form-label" for="target_type">نوع الهدف <span
                                            class="text-danger">*</span></label>
                                    <select name="target_type" id="target_type"
                                        class="form-control select2 @error('target_type') is-invalid @enderror"
                                        data-allow-clear="true" data-placeholder="اختر نوع الهدف">
                                        <option value=""></option>
                                        <x-form.controls.enum-options :enum="\App\Enums\Tenants\Collections\TargetType::class"
                                            :selected="old('target_type')" />
                                    </select>
                                    @error('target_type')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- السطر الثالث: مبلغ الهدف وحقول الفترة --}}
                                <div class="col-sm-6">
                                    <label class="form-label" for="target_amount">مبلغ الهدف <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" name="target_amount" id="target_amount"
                                            class="form-control @error('target_amount') is-invalid @enderror"
                                            placeholder="ادخل مبلغ الهدف" value="{{ old('target_amount') }}" />
                                        <span class="input-group-text">ريال</span>
                                        @error('target_amount')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- حقول الفترة الزمنية (col-sm-6) --}}
                                <div class="col-sm-6" id="period-fields">
                                    {{-- سيتم إدراج حقول الفترة هنا بناءً على نوع الهدف --}}
                                </div>

                                {{-- أزرار التحكم --}}
                                <div class="mt-4 d-flex justify-content-between">
                                    <a href="{{ tenant_route_name('financial-collections.collection-targets.index') }}"
                                        class="btn btn-secondary">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </a>
                                    <div class="d-flex gap-2">
                                        <x-form.wizard.draft-button />
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

    {{-- قوالب HTML للحقول الديناميكية --}}
    <script type="text/template" id="monthly-fields-template">
        <div class="period-fields-container">
            <label class="form-label">الشهر والسنة <span class="text-danger">*</span></label>
            <div class="row g-4">
                <div class="col-6">
                    <select name="target_month" id="target_month" class="form-control select2" data-placeholder="الشهر">
                        <option value=""></option>
                    </select>
                    <div class="month-error-container"></div>
                </div>
                <div class="col-6">
                    <select name="target_year" id="target_year" class="form-control select2" data-placeholder="السنة">
                        <option value=""></option>
                    </select>
                    <div class="year-error-container"></div>
                </div>
            </div>
        </div>
    </script>

    <script type="text/template" id="yearly-fields-template">
        <div class="period-fields-container">
            <label class="form-label" for="target_year_only">السنة <span class="text-danger">*</span></label>
            <select name="target_year" id="target_year_only" class="form-control select2" data-placeholder="اختر السنة">
                <option value=""></option>
            </select>
            <div class="year-error-container"></div>
        </div>
    </script>

    <script type="text/template" id="custom-fields-template">
        <div class="period-fields-container">
            <label class="form-label">الفترة المخصصة <span class="text-danger">*</span></label>
            <div class="row g-4">
                <div class="col-6">
                    <input type="date" name="custom_start_date" id="custom_start_date"
                           class="form-control" placeholder="تاريخ البداية">
                    <div class="start-date-error-container"></div>
                </div>
                <div class="col-6">
                    <input type="date" name="custom_end_date" id="custom_end_date"
                           class="form-control" placeholder="تاريخ النهاية">
                    <div class="end-date-error-container"></div>
                </div>
            </div>
        </div>
    </script>
@endsection
