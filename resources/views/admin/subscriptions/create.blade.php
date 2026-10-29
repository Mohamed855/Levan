@extends('admin.layouts.layoutMaster')

@section('title', 'إضافة اشتراك جديد')

@section('vendor-style')
    @vite(['resources/assets/css/components/icons.css', 'resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.scss'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/bs-stepper/bs-stepper.js'])
@endsection

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@php
    $companyOptions = $companies
        ->map(
            fn($c) => [
                'value' => $c->id,
                'label' => $c->company_name . ' (' . ($c->commercial_number ?? $c->id) . ')',
            ],
        )
        ->values()
        ->all();

    $planOptions = $plans
        ->map(
            fn($p) => [
                'value' => $p->id,
                'label' => $p->name . ' (' . number_format($p->price_after_discount, 2) . ' ر.س)',
            ],
        )
        ->values()
        ->all();
@endphp

@section('page-script')
    <script>
        window.companiesData = @json($companies);
        window.plansData = @json($plans);
    </script>
    @vite(['resources/assets/js/shared/form-validation.js', 'resources/assets/js/admin/subscriptions/create-wizard.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-1 fw-bold">إضافة اشتراك جديد</h4>
                    <p class="text-muted mb-0">إنشاء اشتراك جديد لشركة انتهى اشتراكها السابق</p>
                </div>
                <div>
                    <a href="{{ route('admin.subscriptions.index') }}" class="btn btn-secondary">
                        <i class="ti ti-arrow-right me-1"></i> العودة لقائمة الاشتراكات
                    </a>
                </div>
            </div>

            @include('admin.subscriptions.partials._form', ['is_edit' => false])
        </div>
    </div>
@endsection
