@extends('tenant.layouts.layoutMaster')

@section('title', 'نقل نطاق')

@section('page-style')
    @vite(['resources/assets/css/components/icons.css'])
@endsection

@section('content')
    @php($currentDomain = old('domain', $domain ?? request('domain')))

    <section class="section-py bg-body">
        <div class="container-xxl">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <a href="{{ tenant_route_name('domains.setting') }}" class="btn btn-secondary mb-4">
                        <i class="ti ti-arrow-right me-1"></i>
                        العودة إلى النطاقات
                    </a>

                    <div class="card">
                        <div class="card-header border-bottom">
                            <div class="d-flex align-items-center justify-content-between gap-3">
                                <div>
                                    <p class="text-muted mb-1">نقل نطاق إلى مسند</p>
                                    <h5 class="mb-0">الخطوة {{ min((int) $step, 3) }} من 3</h5>
                                </div>
                                <span class="badge bg-primary">نقل نطاق</span>
                            </div>
                        </div>

                        <div class="card-body">
                            @if (session('error'))
                                <div class="alert alert-danger" role="alert">{{ session('error') }}</div>
                            @endif

                            @if ($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0 ps-3">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif

                            @if ((int) $step === 1)
                                <p class="text-muted mb-4">أدخل النطاق المسجل لدى مزود آخر والذي تريد نقله.</p>

                                <form method="GET" action="{{ tenant_route_name('domains.transfer-domain') }}">
                                    <input type="hidden" name="step" value="2">
                                    <div class="mb-4">
                                        <label class="form-label" for="transfer-domain">اسم النطاق <span class="text-danger">*</span></label>
                                        <input class="form-control" id="transfer-domain" name="domain" type="text"
                                            value="{{ $currentDomain }}" placeholder="example.com" dir="ltr"
                                            autocomplete="off" required>
                                        <div class="form-text">يُعاد التحقق من النطاق والسعر عند بدء الدفع.</div>
                                    </div>

                                    <div class="d-flex justify-content-end">
                                        <button class="btn btn-primary" type="submit">
                                            متابعة
                                            <i class="ti ti-arrow-left ms-1"></i>
                                        </button>
                                    </div>
                                </form>
                            @elseif ((int) $step === 2)
                                <div class="alert alert-info d-flex align-items-start gap-2" role="alert">
                                    <i class="ti ti-key fs-5"></i>
                                    <span>ستُخزَّن بيانات النقل بأمان لاستخدامها بعد التحقق من الدفع فقط.</span>
                                </div>

                                <dl class="row mb-4">
                                    <dt class="col-sm-4 text-muted">النطاق</dt>
                                    <dd class="col-sm-8 mb-0" dir="ltr">{{ $currentDomain }}</dd>
                                </dl>

                                <form method="POST" action="{{ tenant_route_name('domains.transfer-domain') }}">
                                    @csrf
                                    <input type="hidden" name="step" value="3">
                                    <input type="hidden" name="domain" value="{{ $currentDomain }}">

                                    <div class="mb-4">
                                        <label class="form-label" for="epp-code">كود EPP / Auth Code <span class="text-danger">*</span></label>
                                        <input class="form-control" id="epp-code" name="eppCode" type="text"
                                            value="{{ old('eppCode') }}" dir="ltr" autocomplete="off" minlength="6"
                                            required>
                                        <div class="form-text">اطلب الكود من مزود النطاق الحالي.</div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <a class="btn btn-secondary"
                                            href="{{ tenant_route_name('domains.transfer-domain') }}"><i class="ti ti-arrow-right me-1"></i>رجوع</a>
                                        <button class="btn btn-primary" type="submit">
                                            مراجعة الدفع
                                            <i class="ti ti-arrow-left ms-1"></i>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="alert alert-info d-flex align-items-start gap-2" role="alert">
                                    <i class="ti ti-shield-lock fs-5"></i>
                                    <span>سيتم إدخال بيانات البطاقة داخل نموذج Moyasar الآمن في الخطوة التالية.</span>
                                </div>

                                <dl class="row mb-4">
                                    <dt class="col-sm-4 text-muted">النطاق</dt>
                                    <dd class="col-sm-8 mb-0" dir="ltr">{{ $currentDomain }}</dd>
                                </dl>

                                <form method="POST" action="{{ tenant_route_name('domains.process-transfer-payment') }}">
                                    @csrf
                                    <input type="hidden" name="domain" value="{{ $currentDomain }}">
                                    <input type="hidden" name="eppCode" value="{{ old('eppCode', request('eppCode')) }}">

                                    <div class="d-flex justify-content-between">
                                        <a class="btn btn-secondary"
                                            href="{{ tenant_route_name('domains.transfer-domain', ['step' => 2, 'domain' => $currentDomain]) }}"><i class="ti ti-arrow-right me-1"></i>رجوع</a>
                                        <button class="btn btn-primary" type="submit">
                                            المتابعة للدفع الآمن
                                            <i class="ti ti-arrow-left ms-1"></i>
                                        </button>
                                    </div>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
