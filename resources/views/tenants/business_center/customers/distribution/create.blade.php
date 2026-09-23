@extends('tenants.layouts.layoutMaster')

@section('title', 'توزيع جديد للعملاء')


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
    @vite(['resources/assets/js/shared/cdn/sweetalert2.js'])

    <script>
        window.routes = {
            distributionPreview: "{{ tenant_route_name('customers.distribution.preview') }}",
            distributionExecute: "{{ tenant_route_name('customers.distribution.execute') }}",
            distributionIndex: "{{ tenant_route_name('customers.distribution.index') }}",
            portfoliosByCompany: "{{ tenant_route_name('customers.portfolios-by-company') }}",
            calculateTargets: "{{ tenant_route_name('customers.distribution.calculate-targets') }}",
            portfoliosWithUnassignedCustomers: "{{ tenant_route_name('customers.distribution.portfolios-with-unassigned-customers') }}",
            teamsByBranch: "{{ tenant_route_name('customers.distribution.teams-by-branch') }}",
            collectorsByTeam: "{{ tenant_route_name('customers.distribution.collectors-by-team') }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
        window.distributionMethods = @json($distributionMethods);
    </script>
    @vite(['resources/assets/js/tenants/business-center/customers/distribution/customer-distribution-create.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 mb-6">
            <div id="distribution-wizard" class="bs-stepper mt-2">
                <div class="bs-stepper-header">
                    <div class="step" data-target="#step-method">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">1</span>
                            <span class="bs-stepper-label mt-1">
                                <span class="bs-stepper-title">طريقة التوزيع</span>
                                <span class="bs-stepper-subtitle">اختر أسلوب التوزيع</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#step-criteria">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">2</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">معايير الاختيار</span>
                                <span class="bs-stepper-subtitle">تحديد العملاء والمحصلين</span>
                            </span>
                        </button>
                    </div>
                    <div class="line">
                        <i class="ti ti-chevron-right"></i>
                    </div>
                    <div class="step" data-target="#step-preview">
                        <button type="button" class="step-trigger">
                            <span class="bs-stepper-circle">3</span>
                            <span class="bs-stepper-label">
                                <span class="bs-stepper-title">معاينة التوزيع</span>
                                <span class="bs-stepper-subtitle">مراجعة وتأكيد</span>
                            </span>
                        </button>
                    </div>
                </div>

                <div class="bs-stepper-content">
                    <form id="distribution-form" onSubmit="return false">
                        @csrf
                        <div id="step-method" class="content">
                            <div class="row g-6 p-8">
                                @foreach ($distributionMethods as $methodKey => $method)
                                    <div class="col-md-6 mb-4">
                                        <div class="form-check distribution-method-card h-100">
                                            <input class="form-check-input" type="radio" name="distribution_method"
                                                id="{{ $methodKey }}" value="{{ $methodKey }}"
                                                {{ $method['recommended'] ? 'checked' : '' }}>
                                            <label
                                                class="form-check-label w-100 p-4 border rounded cursor-pointer h-100 d-flex flex-column"
                                                for="{{ $methodKey }}">
                                                <div class="d-flex align-items-center mb-3">
                                                    <i class="ti {{ $method['icon'] }} ti-26px me-3"></i>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-0">{{ $method['name'] }}</h6>
                                                    </div>
                                                </div>
                                                <p class="text-muted small mb-0">{{ $method['description'] }}</p>
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                                <div class="col-12 d-flex justify-content-between">
                                    <a href="{{ tenant_route_name('customers.distribution.index') }}"
                                        class="btn btn-label-secondary">
                                        <i class="ti ti-arrow-left ti-xs me-sm-2 me-0"></i>
                                        <span class="align-middle d-sm-inline-block d-none">السابق</span>
                                    </a>
                                    <button type="button" class="btn btn-primary btn-next">
                                        <span class="align-middle d-sm-inline-block d-none me-sm-2">التالي</span>
                                        <i class="ti ti-arrow-right ti-xs"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div id="step-criteria" class="content">
                            <div class="card-body">
                                <div class="row g-4">
                                    {{-- حقول الشركة والمحفظة --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="company_id">الشركة <span
                                                class="text-danger">*</span></label>
                                        <select name="company_id" id="company_id" class="form-control select2"
                                            data-placeholder="اختر الشركة">
                                            <option value=""></option>
                                            @foreach ($companies as $company)
                                                <option value="{{ $company->id }}">{{ $company->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="form-label" for="portfolio_id">المحفظة <span
                                                class="text-danger">*</span></label>
                                        <select name="portfolio_id" id="portfolio_id" class="form-control select2"
                                            data-placeholder="اختر المحفظة" disabled>
                                            <option value=""></option>
                                            @foreach ($portfolios as $portfolio)
                                                <option value="{{ $portfolio->id }}"
                                                    data-company="{{ $portfolio->company_id }}">{{ $portfolio->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- إجمالي المحفظة --}}
                                    <div class="col-12">
                                        <div class="alert alert-info d-flex justify-content-between align-items-center"
                                            id="portfolio-total-alert" style="display: none !important;">
                                            <div>
                                                <i class="ti ti-wallet me-2"></i>
                                                <strong>إجمالي مبلغ المحفظة :</strong>
                                            </div>
                                            <h6 class="mb-0 text-primary" id="portfolio-total-display">0.00</h6>
                                        </div>
                                    </div>

                                    {{-- بعد حقل portfolio_id مباشرة --}}



                                    {{-- إجمالي المحفظة - انقله للعرض الكامل --}}
                                    <div class="col-12">
                                        <div class="alert alert-info d-flex justify-content-between align-items-center"
                                            id="portfolio-total-alert" style="display: none !important;">
                                            <div>
                                                <i class="ti ti-wallet me-2"></i>
                                                <strong>إجمالي مبلغ المحفظة:</strong>
                                            </div>
                                            <h6 class="mb-0 text-primary" id="portfolio-total-display">0.00</h6>
                                        </div>
                                    </div>

                                    {{-- حاسبة الأهداف المستقلة --}}
                                    <div class="col-12">
                                        <div class="card border-primary">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <div>
                                                    <h5 class="mb-0">
                                                        <i class="ti ti-calculator me-2"></i>
                                                        حاسبة الأهداف
                                                    </h5>
                                                    <small class="text-muted">أدخل أي قيمتين وسيتم حساب الثالثة
                                                        تلقائياً</small>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-outline-danger"
                                                    id="reset-calculator-btn" title="مسح جميع القيم والبدء من جديد">
                                                    <i class="ti ti-refresh"></i>
                                                    مسح
                                                </button>
                                            </div>
                                            <div class="card-body">
                                                {{-- حقول الحاسبة --}}
                                                <div class="row g-3">
                                                    <div class="col-md-4">
                                                        <label class="form-label">الهدف الشهري (ريال)</label>
                                                        <input type="number" id="monthly-target" class="form-control"
                                                            placeholder="أدخل الهدف الشهري" min="0"
                                                            step="0.01">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">عدد الموظفين</label>
                                                        <input type="number" id="employees-count" class="form-control"
                                                            placeholder="أدخل عدد الموظفين" min="1"
                                                            step="1">
                                                    </div>
                                                    <div class="col-md-4">
                                                        <label class="form-label">المدة (بالأشهر)</label>
                                                        <input type="number" id="duration-months" class="form-control"
                                                            placeholder="أدخل المدة" min="1" max="12"
                                                            step="1">
                                                    </div>
                                                </div>

                                                {{-- رسالة التحقق --}}
                                                <div id="calculator-validation" class="mt-3" style="display: none;">
                                                </div>

                                                {{-- خيارات التطبيق --}}
                                                <div class="mt-4">
                                                    <h6 class="mb-3">تطبيق الأهداف:</h6>
                                                    <div class="form-check form-switch">
                                                        <input class="form-check-input" type="checkbox"
                                                            id="apply-targets" name="apply_targets" value="1">
                                                        <label class="form-check-label" for="apply-targets">
                                                            <strong>تطبيق الأهداف المحسوبة على الموظفين المختارين</strong>
                                                            <small class="d-block text-muted mt-1">
                                                                سيتم إنشاء أهداف شهرية لكل محصل بناءً على الحاسبة أعلاه
                                                            </small>
                                                        </label>
                                                    </div>

                                                    <div id="apply-targets-warning" class="alert alert-info mt-3"
                                                        style="display: none;">
                                                        <div class="d-flex align-items-start">
                                                            <i class="ti ti-info-circle me-2 mt-1"></i>
                                                            <div>
                                                                <h6 class="alert-heading mb-2">ℹ️ معلومة مهمة</h6>
                                                                <p class="mb-0">
                                                                    عند تفعيل هذا الخيار، يجب اختيار عدد محصلين
                                                                    <strong>يطابق بالضبط</strong> عدد الموظفين المدخل في
                                                                    الحاسبة.
                                                                </p>
                                                                <small class="text-muted d-block mt-2">
                                                                    مثال: إذا أدخلت 3 موظفين في الحاسبة، يجب اختيار 3 محصلين
                                                                    بالضبط.
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                {{-- الحقول المخفية --}}
                                                <input type="hidden" name="calculator_enabled"
                                                    id="calculator-enabled-hidden" value="0">
                                                <input type="hidden" name="calculator_monthly_target"
                                                    id="calculator-monthly-target-hidden">
                                                <input type="hidden" name="calculator_employees_count"
                                                    id="calculator-employees-count-hidden">
                                                <input type="hidden" name="calculator_duration"
                                                    id="calculator-duration-hidden">
                                            </div>
                                        </div>
                                    </div>


                                    {{-- فلتر الفرع --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="branch_id">الفرع <span
                                                class="text-danger">*</span></label>
                                        <select name="branch_id" id="branch_id" class="form-control select2"
                                            data-placeholder="اختر الفرع">
                                            <option value=""></option>
                                            @foreach ($branches as $id => $name)
                                                <option value="{{ $id }}">{{ $name }}</option>
                                            @endforeach
                                        </select>
                                    </div>


                                    {{-- فلتر المجموعة - Multiple Select --}}
                                    <div class="col-md-6">
                                        <label class="form-label" for="team_ids">المجموعات <span
                                                class="text-danger">*</span></label>
                                        <select name="team_ids[]" id="team_ids" class="form-control select2"
                                            data-placeholder="اختر المجموعات" multiple disabled>
                                            <option value=""></option>
                                        </select>
                                    </div>

                                    {{-- اختيار المحصلين --}}
                                    <div class="col-12">
                                        <label class="form-label" for="collector_ids">اختيار المحصلين <span
                                                class="text-danger">*</span></label>
                                        <select name="collector_ids[]" id="collector_ids" class="form-control select2"
                                            multiple data-placeholder="اختر المحصلين">
                                            @foreach ($collectors as $collector)
                                                <option value="{{ $collector->id }}">{{ $collector->name }}</option>
                                            @endforeach
                                        </select>
                                        <div id="collector-count-info" class="form-text" style="display: none;">
                                            <i class="ti ti-info-circle me-1"></i>
                                            <span id="collector-count-text"></span>
                                        </div>
                                    </div>

                                    {{-- ملاحظات --}}
                                    <div class="col-12">
                                        <label class="form-label" for="notes">ملاحظات التوزيع</label>
                                        <textarea name="notes" id="notes" class="form-control" rows="3"
                                            placeholder="أضف أي ملاحظات حول هذا التوزيع" maxlength="1000"></textarea>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 d-flex justify-content-between mt-4">
                                <button type="button" class="btn btn-label-secondary btn-prev">
                                    <i class="ti ti-arrow-left me-1"></i> السابق
                                </button>
                                <button type="button" class="btn btn-primary btn-preview">
                                    معاينة <i class="ti ti-arrow-left me-1"></i>
                                </button>
                            </div>
                        </div>
                        <div id="step-preview" class="content p-8">
                            <div id="preview-loading" class="text-center py-5">
                                <div class="spinner-border text-primary" style="width: 3rem; height: 3rem;"
                                    role="status">
                                </div>
                                <h5 class="text-muted mt-3">جاري حساب أفضل توزيع...</h5>
                            </div>
                            <div id="preview-container" style="display: none;">
                                <div class="card-header border-bottom">
                                    <div class="row text-center mt-4">
                                        <div class="col-6 col-md-3 mb-3">
                                            <h6 class="mb-0" id="summary-method">-</h6>
                                            <small class="text-muted">طريقة التوزيع</small>
                                        </div>
                                        <div class="col-6 col-md-3 mb-3">
                                            <h6 class="mb-0" id="summary-collectors">-</h6>
                                            <small class="text-muted">عدد المحصلين</small>
                                        </div>
                                        <div class="col-6 col-md-3 mb-3">
                                            <h6 class="mb-0" id="summary-customers">-</h6>
                                            <small class="text-muted">إجمالي العملاء</small>
                                        </div>
                                        <div class="col-6 col-md-3 mb-3">
                                            <h6 class="mb-0" id="summary-debt">-</h6>
                                            <small class="text-muted">إجمالي المديونية</small>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0" id="distribution-table">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>المحصل</th>
                                                    <th class="text-center">عدد العملاء</th>
                                                    <th class="text-end">إجمالي المديونيات</th>
                                                    <th class="text-center" style="width: 20%;">نسبة الحمل</th>
                                                </tr>
                                            </thead>
                                            <tbody></tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-4">
                                    <button type="button" class="btn btn-label-secondary btn-prev">
                                        <i class="ti ti-arrow-left me-1"></i> السابق
                                    </button>
                                    <button type="button" class="btn btn-primary btn-execute" disabled>
                                        تنفيذ التوزيع
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
