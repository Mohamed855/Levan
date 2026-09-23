@extends('tenants.layouts.layoutMaster')

@section('title', 'تعديل الفرع')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/@form-validation/auto-focus.js'])
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('page-script')
    @vite(['resources/assets/js/tenants/branches/form-validation.js'])
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
                                <span class="bs-stepper-title">تعديل الفرع</span>
                                <span class="bs-stepper-subtitle">تحديث البيانات الأساسية</span>
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

                    <form id="wizard-validation-form" action="{{ tenant_route_name('branches.update', $branch->id) }}"
                        method="POST" onSubmit="return false">
                        @csrf
                        @method('PUT')

                        <div id="info-validation" class="content">
                            <div class="row g-3">

                                <div class="col-md-6">
                                    <label for="name" class="form-label">اسم الفرع <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name"
                                        value="{{ old('name', $branch->name) }}" placeholder="ادخل اسم الفرع" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="code" class="form-label">كود الفرع <span
                                            class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="code" name="code"
                                            value="{{ old('code', $branch->code) }}" style="text-transform: uppercase;"
                                            placeholder="ادخل كود الفرع" required>
                                        <button type="button" class="btn btn-outline-secondary" onclick="generateCode()">
                                            <i class="ti ti-wand"></i> توليد
                                        </button>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="city" class="form-label">المدينة</label>
                                    <input type="text" class="form-control" id="city" name="city"
                                        value="{{ old('city', $branch->city) }}" placeholder="ادخل المدينة">
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">رقم الهاتف</label>
                                    <input type="text" class="form-control" id="phone" name="phone"
                                        value="{{ old('phone', $branch->phone) }}" placeholder="ادخل رقم الهاتف">
                                </div>

                                <div class="col-12">
                                    <label for="address" class="form-label">العنوان</label>
                                    <textarea class="form-control" id="address" name="address" rows="3"
                                        placeholder="ادخل العنوان">{{ old('address', $branch->address) }}</textarea>
                                </div>

                                <div class="col-12 d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary waves-effect waves-light btn-submit">
                                        <i class="bx bx-save me-1"></i>
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

    <script>
        function generateCode() {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            let code = 'BR';
            for (let i = 0; i < 6; i++) code += chars.charAt(Math.floor(Math.random() * chars.length));
            document.getElementById('code').value = code;
        }
    </script>
@endsection