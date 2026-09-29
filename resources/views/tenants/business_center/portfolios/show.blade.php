@extends('tenants.layouts.layoutMaster')

@section('title', $pageTitle)

@section('vendor-style')
@vite(['resources/assets/vendor/libs/apex-charts/apex-charts.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/apex-charts/apexcharts.js'])
@endsection

@section('content')
{{-- الإحصائيات الرئيسية --}}
<div class="row g-4 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-heading fw-medium">عدد العملاء</span>
                    <h4 class="mb-0 mt-2 text-primary fw-bold">{{ number_format($customersCount) }}</h4>
                </div>
                <div class="avatar avatar-lg">
                    <span class="avatar-initial rounded bg-label-primary">
                        <i class="ti ti-users ti-28px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-heading fw-medium">إجمالي المديونيات</span>
                    <h4 class="mb-0 mt-2 text-info fw-bold">{{ $totalDebtFormatted }}</h4>
                    <small class="text-muted">ريال سعودي</small>
                </div>
                <div class="avatar avatar-lg">
                    <span class="avatar-initial rounded bg-label-info">
                        <i class="ti ti-wallet ti-28px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-heading fw-medium">إجمالي المتحصل</span>
                    <h4 class="mb-0 mt-2 text-success fw-bold">{{ $totalCollectedFormatted }}</h4>
                    <small class="text-muted">ريال سعودي</small>
                </div>
                <div class="avatar avatar-lg">
                    <span class="avatar-initial rounded bg-label-success">
                        <i class="ti ti-coin ti-28px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-6 col-xl-3">
        <div class="card h-100">
            <div class="card-body d-flex align-items-center justify-content-between">
                <div>
                    <span class="text-heading fw-medium">المتبقي</span>
                    <h4 class="mb-0 mt-2 text-warning fw-bold">{{ $remainingDebtFormatted }}</h4>
                    <small class="text-muted">ريال سعودي</small>
                </div>
                <div class="avatar avatar-lg">
                    <span class="avatar-initial rounded bg-label-warning">
                        <i class="ti ti-clock ti-28px"></i>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- معلومات المحفظة الأساسية --}}
<div class="row g-4">
    <div class="col-12 col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex align-items-center justify-content-between py-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-info-circle text-primary me-2"></i>
                    تفاصيل المحفظة
                </h5>
                <span class="badge {{ $statusClass }} fs-6">{{ $statusText }}</span>
            </div>
            <div class="border-1 border-light border-dashed"></div>
            <div class="card-body">
                <div class="row g-3">
                    {{-- اسم المحفظة --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="ti ti-briefcase text-muted me-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">اسم المحفظة</small>
                                <span>{{ $portfolioName }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- الشركة --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="ti ti-building text-muted me-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">الشركة</small>
                                <span>{{ $companyName }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- تاريخ الإسناد --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="ti ti-calendar text-muted me-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">تاريخ الإسناد</small>
                                <span>{{ $assignmentDateLong }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- تقسيم الأسماء --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="ti ti-toggle-left text-muted me-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">تقسيم الأسماء</small>
                                <span>{{ $hasSplitNamesText }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ وقت بدء الاتصال --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="ti ti-clock-play text-muted me-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">وقت بدء الاتصال</small>
                                <span>{{ $callStartTimeFormatted }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ وقت نهاية الاتصال --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="ti ti-clock-stop text-muted me-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">وقت نهاية الاتصال</small>
                                <span>{{ $callEndTimeFormatted }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- تاريخ الإنشاء --}}
                    <div class="col-md-6">
                        <div class="d-flex align-items-center mb-3">
                            <div class="flex-shrink-0">
                                <i class="ti ti-clock text-muted me-3"></i>
                            </div>
                            <div class="flex-grow-1">
                                <small class="text-muted d-block">تاريخ الإنشاء</small>
                                <span>{{ $createdAt }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- ✅ الفترة المسموحة (إذا كانت محددة) --}}
                    @if($callTimeRange !== 'غير محدد')
                    <div class="col-12">
                        <div class="alert alert-info d-flex align-items-center mb-0" role="alert">
                            <i class="ti ti-info-circle me-2"></i>
                            <div>
                                <strong>الفترة المسموحة للاتصال:</strong> {{ $callTimeRange }}
                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-lg-4">
        <div class="card h-100">
            <div class="card-header py-2">
                <h6 class="card-title mb-0">
                    <i class="ti ti-chart-pie text-primary me-2"></i>
                    معدل التحصيل
                </h6>
            </div>
            <div class="card-body p-2">
                <div class="border-1 border-light border-dashed"></div>
                <div class="text-center mb-2">
                    <div id="collectionChart" style="height: 150px;"></div>
                </div>

                {{-- معدل التحصيل --}}
                <div class="text-center mb-2">
                    <h6 class="text-primary fw-bold mb-0">{{ $collectionRateFormatted }}%</h6>
                    <small class="text-muted">معدل التحصيل</small>
                </div>

                {{-- تفاصيل المبالغ في صف واحد --}}
                <div class="d-flex justify-content-between text-center">
                    <div class="flex-fill">
                        <div class="legend-item" data-series="0" style="cursor: pointer;">
                            <div class="color-indicator bg-info rounded-circle mx-auto mb-1"
                                style="width: 12px; height: 12px;"></div>
                            <small class="text-muted d-block" style="font-size: 10px;">إجمالي المديونيات</small>
                            <small style="font-size: 11px;">{{ $totalDebtFormatted }}</small>
                        </div>
                    </div>
                    <div class="flex-fill">
                        <div class="legend-item" data-series="1" style="cursor: pointer;">
                            <div class="color-indicator bg-success rounded-circle mx-auto mb-1"
                                style="width: 12px; height: 12px;"></div>
                            <small class="text-muted d-block" style="font-size: 10px;">المتحصل</small>
                            <small class="text-success" style="font-size: 11px;">{{ $totalCollectedFormatted }}</small>
                        </div>
                    </div>
                    <div class="flex-fill">
                        <div class="legend-item" data-series="2" style="cursor: pointer;">
                            <div class="color-indicator bg-warning rounded-circle mx-auto mb-1"
                                style="width: 12px; height: 12px;"></div>
                            <small class="text-muted d-block" style="font-size: 10px;">المتبقي</small>
                            <small class="text-warning" style="font-size: 11px;">{{ $remainingDebtFormatted }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
    // بيانات الرسم البياني
    const collectedAmount = {{ str_replace(',', '', $totalCollectedFormatted) }};
    const remainingAmount = {{ str_replace(',', '', $remainingDebtFormatted) }};
    
    // إعداد الرسم البياني
    const chartOptions = {
        series: [collectedAmount, remainingAmount],
        chart: {
            height: 180,
            type: 'donut',
            fontFamily: 'inherit'
        },
        labels: ['المتحصل', 'المتبقي'],
        colors: ['#28c76f', '#ff9f43'],
        plotOptions: {
            pie: {
                startAngle: -90,
                endAngle: 270,
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        name: {
                            fontSize: '14px',
                            fontWeight: 600
                        },
                        value: {
                            fontSize: '16px',
                            fontWeight: 700,
                            formatter: function (val) {
                                return new Intl.NumberFormat('ar-SA').format(val) + ' ريال';
                            }
                        },
                        total: {
                            show: true,
                            fontSize: '14px',
                            fontWeight: 600,
                            label: 'الإجمالي',
                            formatter: function (w) {
                                const total = w.globals.seriesTotals.reduce((a, b) => {
                                    return a + b;
                                }, 0);
                                return new Intl.NumberFormat('ar-SA').format(total) + ' ريال';
                            }
                        }
                    }
                }
            }
        },
        dataLabels: {
            enabled: false
        },
        legend: {
            position: 'bottom',
            offsetY: 5,
            fontSize: '12px',
            fontWeight: 500,
            labels: {
                colors: '#6f6b7d'
            },
            markers: {
                width: 6,
                height: 6
            }
        },
        stroke: {
            show: false
        },
        grid: {
            padding: {
                bottom: 15
            }
        },
        responsive: [{
            breakpoint: 480,
            options: {
                chart: {
                    height: 160
                },
                legend: {
                    fontSize: '11px'
                }
            }
        }]
    };

    // إنشاء الرسم البياني
    const chart = new ApexCharts(
        document.querySelector("#collectionChart"),
        chartOptions
    );
    chart.render();
});
</script>
@endsection