@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل تقرير العقد')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    
    <!-- رأس الصفحة -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-file-analytics text-primary me-2"></i>
                تقرير العقد رقم: {{ $contract->contract_number ?? 'غير محدد' }}
            </h4>
            <p class="text-muted mb-0">تفاصيل المبالغ المحصلة ونسبة الشركة</p>
        </div>
        <div>
            <a href="{{ tenant_route_name('reports.contracts.index') }}" class="btn btn-secondary me-2">
                <i class="ti ti-arrow-right me-1"></i>
                رجوع
            </a>
            <button class="btn btn-primary" onclick="window.print()">
                <i class="ti ti-printer me-1"></i>
                طباعة
            </button>
        </div>
    </div>

    <!-- معلومات العقد الأساسية -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0">
                        <i class="ti ti-file-text me-2"></i>
                        معلومات العقد
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-hash text-primary me-2 fs-5"></i>
                            <span class="text-muted">رقم العقد:</span>
                            <span class="fw-bold ms-auto">{{ $contract->contract_number ?? 'غير محدد' }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-building text-primary me-2 fs-5"></i>
                            <span class="text-muted">الشركة:</span>
                            <span class="fw-bold ms-auto">{{ $contract->company->name ?? 'غير محدد' }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-calendar text-primary me-2 fs-5"></i>
                            <span class="text-muted">تاريخ البداية:</span>
                            <span class="fw-bold ms-auto">{{ $contract->start_date ? $contract->start_date->locale('ar')->isoFormat('D MMMM YYYY') : 'غير محدد' }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-calendar-event text-primary me-2 fs-5"></i>
                            <span class="text-muted">تاريخ النهاية:</span>
                            <span class="fw-bold ms-auto">{{ $contract->end_date ? $contract->end_date->locale('ar')->isoFormat('D MMMM YYYY') : 'غير محدد' }}</span>
                        </li>
                        <li class="mb-0 d-flex align-items-center">
                            <i class="ti ti-circle-dot text-primary me-2 fs-5"></i>
                            <span class="text-muted">الحالة:</span>
                            <span class="badge {{ $contract->status_class }} ms-auto">{{ $contract->status_label }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h6 class="mb-0">
                        <i class="ti ti-cash me-2"></i>
                        ملخص مالي
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-moneybag text-success me-2 fs-5"></i>
                            <span class="text-muted">إجمالي المحصل:</span>
                            <span class="fw-bold text-success ms-auto">{{ number_format($totalCollectedAmount, 2) }} جنيه</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-percentage text-warning me-2 fs-5"></i>
                            <span class="text-muted">نسبة العمولة:</span>
                            <span class="fw-bold text-warning ms-auto">{{ number_format($averageCommissionRate, 2) }}%</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-coin text-warning me-2 fs-5"></i>
                            <span class="text-muted">عمولة الشركة:</span>
                            <span class="fw-bold text-warning ms-auto">{{ number_format($companyCommissionAmount, 2) }} جنيه</span>
                        </li>
                        <li class="mb-0 d-flex align-items-center">
                            <i class="ti ti-calculator text-info me-2 fs-5"></i>
                            <span class="text-muted">الصافي:</span>
                            <span class="fw-bold text-info ms-auto">{{ number_format($netAmount, 2) }} جنيه</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <!-- نسب العمولات -->
    @if($commissions->count() > 0)
    <div class="card mb-4">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="ti ti-percentage text-warning me-2"></i>
                نسب العمولات
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>نوع العمولة</th>
                            <th>نوع الإجراء</th>
                            <th>المدة من</th>
                            <th>المدة إلى</th>
                            <th>نسبة العمولة</th>
                            <th>الحالة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($commissions as $commission)
                        <tr>
                            <td>
                                <span class="badge bg-label-primary">{{ $commission->commission_type ?? 'غير محدد' }}</span>
                            </td>
                            <td>{{ $commission->procedure_type ?? 'غير محدد' }}</td>
                            <td>{{ $commission->duration_from ?? '-' }}</td>
                            <td>{{ $commission->duration_to ?? '-' }}</td>
                            <td>
                                <span class="fw-bold text-warning">{{ number_format($commission->commission_rate, 2) }}%</span>
                            </td>
                            <td>
                                @if($commission->is_active)
                                    <span class="badge bg-success">نشط</span>
                                @else
                                    <span class="badge bg-secondary">غير نشط</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    @endif

    <!-- التحصيلات المعتمدة -->
    <div class="card">
        <div class="card-header">
            <h6 class="card-title mb-0">
                <i class="ti ti-checks text-success me-2"></i>
                التحصيلات المعتمدة
                <span class="badge bg-label-primary ms-2">{{ $approvedCollections->count() }}</span>
            </h6>
        </div>
        <div class="card-body">
            @if($approvedCollections->count() > 0)
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>رقم الإيصال</th>
                            <th>المبلغ المحصل</th>
                            <th>نوع التحصيل</th>
                            <th>نوع السداد</th>
                            <th>طريقة الدفع</th>
                            <th>تاريخ التحصيل</th>
                            <th>العميل</th>
                            <th>عمولة الشركة</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($approvedCollections as $index => $collection)
                        @php
                            $collectionCommission = ($collection->collected_amount * $averageCommissionRate) / 100;
                        @endphp
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <code>{{ $collection->receipt_number ?? 'غير محدد' }}</code>
                            </td>
                            <td>
                                <span class="fw-bold text-success">{{ number_format($collection->collected_amount, 2) }}</span>
                                <small class="text-muted d-block">جنيه</small>
                            </td>
                            <td>
                                @if($collection->collection_type == 'cash')
                                    <span class="badge bg-label-success">
                                        <i class="ti ti-cash me-1"></i>
                                        نقدي
                                    </span>
                                @else
                                    <span class="badge bg-label-info">
                                        <i class="ti ti-calendar-event me-1"></i>
                                        مجدول
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if($collection->payment_type == 'settlement')
                                    <span class="badge bg-label-primary">تسوية</span>
                                @else
                                    <span class="badge bg-label-secondary">بدون تسوية</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-label-dark">{{ $collection->paymentMethod->name ?? 'غير محدد' }}</span>
                            </td>
                            <td>
                                @if($collection->collection_date)
                                    <i class="ti ti-calendar me-1 text-primary"></i>
                                    {{ $collection->collection_date->locale('ar')->isoFormat('D MMMM YYYY') }}
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <small class="d-block text-muted mb-1">العميل:</small>
                                <span class="fw-semibold small">{{ $collection->customer->name ?? 'غير محدد' }}</span>
                            </td>
                            <td>
                                <span class="fw-bold text-warning">{{ number_format($collectionCommission, 2) }}</span>
                                <small class="text-muted d-block">جنيه</small>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="table-light">
                        <tr>
                            <th colspan="2" class="text-end">الإجمالي:</th>
                            <th class="text-success">{{ number_format($totalCollectedAmount, 2) }} جنيه</th>
                            <th colspan="5"></th>
                            <th class="text-warning">{{ number_format($companyCommissionAmount, 2) }} جنيه</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="text-center py-5">
                <div class="mb-3">
                    <i class="ti ti-file-off text-muted" style="font-size: 60px; opacity: 0.3;"></i>
                </div>
                <h6 class="text-muted">لا توجد تحصيلات معتمدة</h6>
            </div>
            @endif
        </div>
    </div>

</div>

<style>
    @media print {
        .btn, .card-header .badge {
            display: none !important;
        }
        
        .card {
            break-inside: avoid;
            page-break-inside: avoid;
        }
    }
</style>
@endsection