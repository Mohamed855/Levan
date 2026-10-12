@extends('tenants.layouts.layoutMaster')

@section('title', 'تجديد النطاق')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('content')
    @php
        $selectedYears = (int) old('years', 1);
        $selectedTotal = $unitPrice * $selectedYears;
    @endphp

    <section class="section-py bg-body">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <a href="{{ tenant_route_name('domains.setting') }}" class="btn btn-secondary mb-4">
                        <i class="ti ti-arrow-right me-1"></i>
                        العودة إلى النطاقات
                    </a>

                    <form method="POST" action="{{ tenant_route_name('domains.moyasar.renewal') }}" class="card">
                        @csrf
                        <input type="hidden" name="domain_id" value="{{ $domain->id }}">

                        <div class="card-header border-bottom">
                            <p class="text-muted mb-1">تجديد النطاق</p>
                            <h5 class="mb-0" dir="ltr">{{ $domain->domain }}</h5>
                        </div>

                        <div class="card-body">
                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            <div class="alert alert-info d-flex align-items-start gap-2 mb-5" role="alert">
                                <i class="ti ti-shield-lock fs-5"></i>
                                <span>سيتم إدخال بيانات البطاقة داخل نموذج Moyasar الآمن في الخطوة التالية.</span>
                            </div>

                            <label class="form-label d-block mb-3">اختر مدة التجديد</label>
                            <div class="row g-3">
                                @for ($year = 1; $year <= 5; $year++)
                                    <div class="col-md-4 col-sm-6">
                                        <input class="btn-check" id="renewal-years-{{ $year }}" name="years"
                                            type="radio" value="{{ $year }}" @checked($selectedYears === $year)>
                                        <label class="btn btn-outline-primary w-100 text-start p-3"
                                            for="renewal-years-{{ $year }}">
                                            <span class="d-block fw-semibold mb-1">{{ $year }}
                                                {{ $year === 1 ? 'سنة' : 'سنوات' }}</span>
                                            <span class="small">{{ number_format($unitPrice * $year, 2) }} ر.س</span>
                                        </label>
                                    </div>
                                @endfor
                            </div>

                            <div class="bg-lighter rounded p-3 mt-5 d-flex align-items-center justify-content-between">
                                <span class="text-muted">إجمالي الخيار الحالي</span>
                                <strong>{{ number_format($selectedTotal, 2) }} <span
                                        class="icon-saudi_riyal fs-6"></span></strong>
                            </div>
                        </div>

                        <div class="card-footer border-top d-flex justify-content-end">
                            <button type="submit" class="btn btn-primary">
                                المتابعة للدفع الآمن
                                <i class="ti ti-arrow-left ms-1"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>
@endsection
