@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل العقد')

@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss'
])
@endsection

@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
])
@endsection

@section('page-script')
<script>
    $(document).ready(function() {
        $('#commissions-table').DataTable({
            language: {
                url: "{{ asset('assets/json/ar.json') }}"
            }
            , responsive: true
            , order: []
            , paging: false
            , searching: false
            , info: false
        });

        // DataTable للتحصيلات
        if ($('#collections-table').length) {
            $('#collections-table').DataTable({
                language: {
                    url: "{{ asset('assets/json/ar.json') }}"
                }
                , responsive: true
                , order: [
                    [0, 'desc']
                ]
                , pageLength: 10
            , });
        }
    });

</script>
@endsection

@section('content')
<div class="row g-4">
    <!-- البطاقة الإحصائية العلوية -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-body py-4">
                <div class="row text-center g-4">
                    <div class="col-md-3">
                        <h6 class="text-muted fw-normal mb-2">تاريخ البداية</h6>
                        <div style="border-top: 1px dashed rgba(0,0,0,0.08); margin: 8px 0;"></div>
                        <p class="fs-5 mb-0 fw-semibold">
                            {{ $contract->formatted_start_date }}
                        </p>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-muted fw-normal mb-2">تاريخ الانتهاء</h6>
                        <div style="border-top: 1px dashed rgba(0,0,0,0.08); margin: 8px 0;"></div>
                        <p class="fs-6 mb-0 fw-medium">
                            {{ $contract->formatted_end_date }}
                        </p>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-muted fw-normal mb-2">مدة العقد</h6>
                        <div style="border-top: 1px dashed rgba(0,0,0,0.08); margin: 8px 0;"></div>
                        <p class="fs-5 mb-0 fw-semibold">
                            {{ $contract->duration_months }} شهر
                        </p>
                    </div>

                    <div class="col-md-3">
                        <h6 class="text-muted fw-normal mb-2">نوع العمولة</h6>
                        <div style="border-top: 1px dashed rgba(0,0,0,0.08); margin: 8px 0;"></div>
                        <p class="fs-6 mb-0">
                            {{ match($contract->commission_type) {
                                'procedure' => 'حسب الإجراء',
                                'duration' => 'حسب المدة',
                                'delinquency' => 'مدة التعثر',
                                default => $contract->commission_type
                            } }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- المعلومات الأساسية -->
    <div class="col-12 col-lg-6">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">المعلومات الأساسية</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>الحقل</th>
                                <th>القيمة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-medium">رقم العقد</td>
                                <td>
                                    <span class="badge bg-label-primary">
                                        {{ $contract->contract_number }}
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td class="fw-medium">اسم الشركة</td>
                                <td>{{ $contract->company->name ?? 'غير محدد' }}</td>
                            </tr>

                            @if($contract->creator)
                            <tr>
                                <td class="fw-medium">أنشئ بواسطة</td>
                                <td>{{ $contract->creator->name }}</td>
                            </tr>
                            @endif

                            @if($contract->updater)
                            <tr>
                                <td class="fw-medium">آخر تحديث بواسطة</td>
                                <td>{{ $contract->updater->name }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- التقرير المالي -->
@if(isset($financialReport))
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti ti-report-money text-success me-2"></i>
                    التقرير المالي للعقد
                </h5>
                <span class="badge bg-label-primary rounded-pill">{{ $financialReport['approved_collections_count'] }} تحصيل</span>
            </div>
            <div class="card-body">
                <!-- الملخص المالي -->
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center p-3 bg-label-success rounded">
                            <div class="avatar avatar-md bg-success rounded me-3 d-flex align-items-center justify-content-center">
                                <i class="ti ti-moneybag text-white"></i>
                            </div>
 
                            <div>
                                <small class="text-muted d-block">إجمالي المحصل</small>
                                <h5 class="mb-0 text-success">{{ number_format($financialReport['total_collected_amount'], 2) }}</h5>
                                <small class="text-muted">ريال</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex align-items-center p-3 bg-label-info rounded">
                            <div class="avatar avatar-md bg-info rounded me-3 d-flex align-items-center justify-content-center">
                                <i class="ti ti-coin text-white"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">عمولة الشركة</small>
                                <h5 class="mb-0 text-info">{{ number_format($financialReport['company_commission_amount'], 2) }}</h5>
                                <small class="text-muted">ريال</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="d-flex align-items-center p-3 bg-label-primary rounded">
                            <div class="avatar avatar-md bg-primary rounded me-3 d-flex align-items-center justify-content-center">
                                <i class="ti ti-calculator text-white"></i>
                            </div>
                            <div>
                                <small class="text-muted d-block">الصافي</small>
                                <h5 class="mb-0 text-primary">{{ number_format($financialReport['net_amount'], 2) }}</h5>
                                <small class="text-muted">ريال</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- جدول التحصيلات -->
                @if($financialReport['collections']->count() > 0)

                @if($contract->commissions->where('is_active', 1)->isEmpty())
                <div class="alert alert-warning mb-3" role="alert">
                    <div class="d-flex align-items-center">
                        <i class="ti ti-alert-triangle me-2"></i>
                        <div>
                            <strong>تنبيه:</strong> لا توجد شرائح عمولة نشطة في هذا العقد. لن يتم حساب أي عمولة على التحصيلات.
                        </div>
                    </div>
                </div>
                @endif

                <div class="table-responsive">
                    <table id="collections-table" class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>رقم الإيصال</th>
                                <th>العميل</th>
                                <th>المبلغ</th>
                                <th>نوع التحصيل</th>
                                <th>طريقة الدفع</th>
                                <th>تاريخ التحصيل</th>
                                <th>نسبة العمولة</th>
                                <th>عمولة الشركة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($financialReport['collections'] as $index => $collection)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <code class="bg-light px-2 py-1 rounded">{{ $collection->receipt_number ?? '-' }}</code>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="avatar avatar-xs bg-label-primary rounded-circle me-2">
                                            <i class="ti ti-user"></i>
                                        </div>
                                        <span class="fw-semibold">{{ $collection->customer->name ?? 'غير محدد' }}</span>
                                    </div>
                                </td>
                                <td>
                                    <span class="fw-bold text-success">{{ number_format($collection->collected_amount, 2) }}</span>
                                    <small class="text-muted d-block">ريال</small>
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
                                    <span class="badge bg-label-dark">{{ $collection->paymentMethod->name ?? 'غير محدد' }}</span>
                                </td>
                                <td>
                                    @if($collection->collection_date)
                                    <div class="d-flex align-items-center">
                                        <i class="ti ti-calendar me-1 text-primary"></i>
                                        <small>{{ $collection->collection_date->locale('ar')->isoFormat('D MMM YYYY') }}</small>
                                    </div>
                                    @else
                                    <span class="badge bg-label-secondary">
                                        <i class="ti ti-clock me-1"></i>
                                        غير محدد
                                    </span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($collection->commission_rate) && $collection->commission_rate > 0)
                                    <span class="badge bg-label-warning">{{ number_format($collection->commission_rate, 2) }}%</span>
                                    @else
                                    <span class="badge bg-label-secondary">0%</span>
                                    @endif
                                </td>
                                <td>
                                    @if(isset($collection->commission_amount) && $collection->commission_amount > 0)
                                    <span class="fw-bold text-warning">{{ number_format($collection->commission_amount, 2) }}</span>
                                    <small class="text-muted d-block">ريال</small>
                                    @else
                                    <span class="text-muted">0.00 ريال</span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="table-light">
                            <tr>
                                <th colspan="3" class="text-end">الإجمالي:</th>
                                <th class="text-success">{{ number_format($financialReport['total_collected_amount'], 2) }} ريال</th>
                                <th colspan="4"></th>
                                <th class="text-warning">{{ number_format($financialReport['company_commission_amount'], 2) }} ريال</th>
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
</div>
@endif
<!-- جدول شرائح العمولة التفصيلي -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                    <i class="ti ti-percentage text-primary me-2"></i>
                    شرائح العمولة التفصيلية
                </h5>
                <span class="badge bg-label-primary rounded-pill">{{ $contract->commissions->count() }} شريحة</span>
            </div>
            <div class="card-body">
                @if($contract->commissions && $contract->commissions->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-bordered">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center">#</th>
                                <th>نوع العمولة</th>
                                <th>نوع الإجراء</th>
                                <th class="text-center">المدة من (يوم)</th>
                                <th class="text-center">المدة إلى (يوم)</th>
                                <th class="text-center">نسبة العمولة</th>
                                <th class="text-center">الحالة</th>
                                <th class="text-center">تاريخ الإنشاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contract->commissions as $index => $commission)
                            <tr>
                                <td class="text-center">{{ $index + 1 }}</td>
                                <td>
                                    @if($commission->commission_type == 'duration')
                                    <span class="badge bg-label-info">
                                        <i class="ti ti-clock me-1"></i>
                                        حسب المدة
                                    </span>
                                    @elseif($commission->commission_type == 'procedure')
                                    <span class="badge bg-label-success">
                                        <i class="ti ti-file-check me-1"></i>
                                        حسب الإجراء
                                    </span>
                                    @elseif($commission->commission_type == 'delinquency')
                                    <span class="badge bg-label-warning">
                                        <i class="ti ti-alert-triangle me-1"></i>
                                        مدة التعثر
                                    </span>
                                    @else
                                    <span class="badge bg-label-secondary">{{ $commission->commission_type }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($commission->procedure_type)
                                    @if($commission->procedure_type == 'friendly')
                                    <span class="badge bg-label-success">
                                        <i class="ti ti-heart me-1"></i>
                                        ودي
                                    </span>
                                    @elseif($commission->procedure_type == 'legal')
                                    <span class="badge bg-label-danger">
                                        <i class="ti ti-gavel me-1"></i>
                                        قانوني
                                    </span>
                                    @else
                                    <span class="badge bg-label-dark">{{ $commission->procedure_type }}</span>
                                    @endif
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($commission->duration_from)
                                    <span class="badge bg-label-primary">{{ $commission->duration_from }}</span>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($commission->duration_to)
                                    <span class="badge bg-label-primary">{{ $commission->duration_to }}</span>
                                    @else
                                    <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-label-success fs-6 px-3 py-2">
                                        {{ number_format($commission->commission_rate, 2) }}%
                                    </span>
                                </td>
                                <td class="text-center">
                                    @if($commission->is_active)
                                    <span class="badge bg-success">
                                        <i class="ti ti-check me-1"></i>
                                        نشط
                                    </span>
                                    @else
                                    <span class="badge bg-secondary">
                                        <i class="ti ti-x me-1"></i>
                                        غير نشط
                                    </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($commission->created_at)
                                    <div class="d-flex align-items-center justify-content-center">
                                        <i class="ti ti-calendar me-1 text-muted"></i>
                                        <small>{{ $commission->created_at->locale('ar')->isoFormat('D MMM YYYY') }}</small>
                                    </div>
                                    @else
                                    <span class="badge bg-label-secondary">
                                        <i class="ti ti-minus"></i>
                                    </span>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <div class="mb-3">
                        <i class="ti ti-percentage text-muted" style="font-size: 60px; opacity: 0.3;"></i>
                    </div>
                    <h6 class="text-muted">لا توجد شرائح عمولة مسجلة</h6>
                    <p class="text-muted small">لم يتم إضافة أي شرائح عمولة لهذا العقد بعد</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- الملاحظات -->
@if($contract->notes)
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">الملاحظات</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info mb-0" style="border-left: 4px solid #28a745;">
                    <div class="d-flex align-items-start">
                        <i class="ti ti-note text-info me-2 mt-1"></i>
                        <div>{{ $contract->notes }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif
@endsection
