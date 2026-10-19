@extends('admin.layouts.layoutMaster')

@section('title', 'تعديل الإصدارة')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/shared/form-validation.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="wizard-validation" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#info-validation">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">تعديل الإصدارة</span>
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

                    <form id="wizard-validation-form" data-form-validate data-validation-url="{{ route('admin.wizard-drafts.validate-step') }}" action="{{ route('admin.versions.update', $version->id) }}"
                        method="POST" enctype="multipart/form-data" onSubmit="return false">
                        @csrf
                        @method('PUT')

                        <div id="info-validation" class="content">
                            <div class="row g-4">
                                <!-- رقم الإصدار -->
                                <x-form.controls.input name="version" id="version" label="رقم الإصدارة"
                                    :value="old('version', $version->version)" placeholder="مثال: v2.0.0" required col="col-md-6" />

                                <!-- تاريخ الإصدار -->
                                <x-form.controls.input type="date" name="released_at" id="released_at" label="تاريخ الإصدار"
                                    :value="old('released_at', optional($version->released_at)->format('Y-m-d'))" col="col-md-6" />

                                <!-- سجل التغييرات -->
                                <x-form.controls.textarea name="changelog" id="changelog" label="سجل التغييرات" rows="4"
                                    :value="old('changelog', $version->changelog)"
                                    placeholder="مثال: - تحسين الأداء العام&#10;- إصلاح مشكلة تسجيل الدخول&#10;- تحديث واجهات المستخدم" col="col-md-6" />

                                <!-- مسار الإصدارة -->
                                <x-form.controls.input name="file_path" id="file_path" label="مسار الإصدارة"
                                    :value="old('file_path', $version->file_path)"
                                    placeholder="المسار يتم توفيره من قبل الفريق التقني" required col="col-md-6" />

                                <!-- المرحلة -->
                                <x-form.controls.select name="stage" id="stage" label="مرحلة الإصدار"
                                    placeholder="اختر المرحلة" required select2 col="col-md-6">
                                    <option value=""> </option>
                                    @foreach ($versionStages as $item)
                                        <option value="{{ $item->value }}"
                                            {{ old('stage', $version->stage->value) === $item->value ? 'selected' : '' }}>
                                            {{ $item->label() }} - ({{ $item->description() }})
                                        </option>
                                    @endforeach
                                </x-form.controls.select>

                                <!-- الحالة -->
                                <x-form.controls.select name="is_active" id="is_active" label="حالة الإصدارة"
                                    placeholder="اختر الحالة" required select2 col="col-md-6">
                                    <option value="">اختر الحالة</option>
                                    <x-form.controls.enum-options :enum="\App\Enums\Shared\ActivationStatus::class" :selected="old('is_active', $version->is_active)" />
                                </x-form.controls.select>
                            </div>

                            <div class="col-12 d-flex justify-content-end mt-4">
                                <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                    <i class="bx bx-save me-1"></i>
                                    <span>تحديث</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
