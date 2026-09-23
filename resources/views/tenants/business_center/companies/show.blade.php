@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل الشركة')

@section('vendor-style')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
        'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
        'resources/assets/vendor/libs/select2/select2.scss',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'
    ])
@endsection

@section('page-style')
    @vite(['resources/css/dataTable.css'])
@endsection

@section('vendor-script')
    @vite([
        'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js',
        'resources/assets/vendor/libs/select2/select2.js',
        'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'
    ])
    {!! $collectionTable->scripts() !!}
@endsection
@section('page-script')
    @vite('resources/assets/js/tenants/business-center/companies/show.js')
@endsection

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')

<!-- البطاقات الإحصائية -->
<div class="row g-4 mb-4">
    <!-- اسم الشركة -->
    {{-- <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-muted d-block mb-1">اسم الشركة</span>
                        <div class="d-flex align-items-center my-2">
                            @if(!empty($company->logo_url))
                            <img src="{{ tasset($company->logo_url) }}" alt="logo" class="rounded me-2" style="width:40px;height:40px;object-fit:cover;border:2px solid #e0e0e0">
                            @endif
                            <h4 class="mb-0">{{ $company->name }}</h4>
                        </div>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-building ti-28px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div> --}}

    <!-- عدد العقود -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-muted d-block mb-1">عدد العقود</span>
                        <div class="d-flex align-items-center my-2">
                            <h3 class="mb-0 text-primary">{{ $contractsCount }}</h3>
                        </div>
                        <small class="text-muted">العقود المسجلة</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-primary">
                            <i class="ti ti-file-text ti-28px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عدد العملاء -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-muted d-block mb-1">عدد العملاء</span>
                        <div class="d-flex align-items-center my-2">
                            <h3 class="mb-0 text-success">{{ $customersCount }}</h3>
                        </div>
                        <small class="text-muted">إجمالي قاعدة العملاء</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-user-check ti-28px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- عدد المحافظ -->
    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-muted d-block mb-1">عدد المحافظ</span>
                        <div class="d-flex align-items-center my-2">
                            <h3 class="mb-0 text-warning">{{ $portfoliosCount }}</h3>
                        </div>
                        <small class="text-muted">المحافظ النشطة</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-warning">
                            <i class="ti ti-briefcase ti-28px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <div class="col-sm-6 col-xl-3">
        <div class="card stat-card h-100">
            <div class="card-body">
                <div class="d-flex align-items-start justify-content-between">
                    <div class="content-left">
                        <span class="text-muted d-block mb-1">إجمالي التحصيلات</span>
                        <div class="d-flex align-items-center my-2">
                            <h3 class="mb-0 text-success">{{ number_format($totalCollected, 2) }}</h3>
                        </div>
                        <small class="text-muted">المبالغ المعتمدة</small>
                    </div>
                    <div class="avatar">
                        <span class="avatar-initial rounded bg-label-success">
                            <i class="ti ti-cash ti-28px"></i>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- بطاقات التحصيلات -->
 

<!-- معلومات الشركة -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card section-card">
            <div class="card-header d-flex justify-content-between align-items-center pb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-info-circle me-2"></i>معلومات الشركة
                </h5>
                <span class="badge {{ $company->status === 'active' ? 'bg-label-success' : 'bg-label-danger' }}">
                    {{ $company->status === 'active' ? 'نشط' : 'غير نشط' }}
                </span>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ti ti-building text-muted me-2" style="font-size: 1.5rem;"></i>
                            <div>
                                <small class="text-muted d-block">اسم الشركة</small>
                                <span class="fw-medium">{{ $company->name }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ti ti-calendar text-muted me-2" style="font-size: 1.5rem;"></i>
                            <div>
                                <small class="text-muted d-block">تاريخ الإضافة</small>
                                <span class="fw-medium">{{ $company->created_at?->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex align-items-center mb-3">
                            <i class="ti ti-clock text-muted me-2" style="font-size: 1.5rem;"></i>
                            <div>
                                <small class="text-muted d-block">آخر تحديث</small>
                                <span class="fw-medium">{{ $company->updated_at?->format('Y-m-d H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ جدول العقود - DataTable -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card section-card">
            <div class="card-header d-flex justify-content-between align-items-center pb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-file-text me-2"></i>العقود المرتبطة
                </h5>
                <span class="badge bg-label-primary">الإجمالي: {{ $contractsCount }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
<table id="contracts-table" class="table table-hover w-100">
                        <thead class="">
                            <tr>
                                <th>رقم العقد</th>
                                <th>تاريخ البداية</th>
                                <th>تاريخ النهاية</th>
                                <th>المدة (أشهر)</th>
                                <th>نوع العمولة</th>
                                <th>الحالة</th>
                                <th>الأيام المتبقية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contracts as $contract)
                            <tr>
                                <td class="fw-medium">
                                    <i class="ti ti-file-invoice text-primary me-1"></i>{{ $contract->contract_number ?? '—' }}
                                </td>
                                <td>{{ $contract->start_date ? \Carbon\Carbon::parse($contract->start_date)->format('Y-m-d') : '—' }}</td>
                                <td>{{ $contract->end_date ? \Carbon\Carbon::parse($contract->end_date)->format('Y-m-d') : '—' }}</td>
                                <td>
                                    <span class="badge bg-label-info">{{ $contract->duration_months ?? '—' }}</span>
                                </td>
                                <td>
                                    <span class="badge bg-label-secondary">
                                        @if($contract->commission_type === 'procedure') حسب الإجراء
                                        @elseif($contract->commission_type === 'duration') حسب المدة
                                        @elseif($contract->commission_type === 'delinquency') حسب التعثر
                                        @else —
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    <span class="badge @if($contract->status === 'active') bg-label-success @elseif($contract->status === 'expired') bg-label-danger @elseif($contract->status === 'suspended') bg-label-warning @else bg-label-secondary @endif">
                                        @if($contract->status === 'active') نشط
                                        @elseif($contract->status === 'expired') منتهي
                                        @elseif($contract->status === 'suspended') موقوف
                                        @else غير نشط
                                        @endif
                                    </span>
                                </td>
                                <td>
                                    @php
                                    $daysUntilExpiry = $contract->end_date ? \Carbon\Carbon::now()->diffInDays(\Carbon\Carbon::parse($contract->end_date), false) : null;
                                    @endphp
                                    @if($daysUntilExpiry !== null && $daysUntilExpiry >= 0)
                                        @if($daysUntilExpiry > 30)
                                            <span class="badge bg-success">{{ $daysUntilExpiry }} يوم</span>
                                        @elseif($daysUntilExpiry > 7)
                                            <span class="badge bg-warning">{{ $daysUntilExpiry }} يوم</span>
                                        @else
                                            <span class="badge bg-danger">{{ $daysUntilExpiry }} يوم</span>
                                        @endif
                                    @elseif($daysUntilExpiry !== null && $daysUntilExpiry < 0)
                                        <span class="badge bg-danger">منتهي</span>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ✅ جدول التحصيلات - من DataTable Class -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card section-card">
            <div class="card-header d-flex justify-content-between align-items-center pb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-cash me-2"></i>التحصيلات
                </h5>
            </div>
            <div class="card-body">
                {!! $collectionTable->table(['class' => 'table table-hover w-100', 'id' => 'company-collections-table'], false) !!}
            </div>
        </div>
    </div>
</div>




<!-- ✅ جداول المحافظ والمفوضين - DataTable -->
<div class="row g-4 mb-4">
    <!-- جدول المحافظ -->
    <div class="col-12 col-lg-6">
        <div class="card section-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center pb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-briefcase me-2"></i>المحافظ المرتبطة
                </h5>
                <span class="badge bg-label-warning">الإجمالي: {{ $portfoliosCount }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="portfolios-table" class="table table-hover w-100 mb-0">
                        <thead class="">
                            <tr>
                                <th>اسم المحفظة</th>
                                <th>تاريخ التخصيص</th>
                                <th>الحالة</th>
                                <th>عدد العملاء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($portfolios as $p)
                            <tr>
                                <td class="fw-medium">
                                    <i class="ti ti-briefcase text-warning me-1"></i>{{ $p->name }}
                                </td>
                                <td>{{ $p->assignment_date?->format('Y-m-d') ?? '—' }}</td>
                                <td>
                                    <span class="badge {{ $p->status === 'active' ? 'bg-label-success' : 'bg-label-danger' }}">
                                        {{ $p->status === 'active' ? 'نشط' : 'غير نشط' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-label-primary">{{ $p->customers_count }}</span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- جدول المفوضين -->
    <div class="col-12 col-lg-6">
        <div class="card section-card h-100">
            <div class="card-header d-flex justify-content-between align-items-center pb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-users me-2"></i>المفوّضون
                </h5>
                <span class="badge bg-label-info">الإجمالي: {{ $authorizedCount }}</span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="authorized-table" class="table table-hover w-100 mb-0">
                        <thead class="">
                            <tr>
                                <th>الاسم</th>
                                <th>المنصب</th>
                                <th>رقم الهاتف</th>
                                <th>البريد الإلكتروني</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($company->authorizedPeople as $person)
                            <tr>
                                <td class="fw-medium">
                                    <i class="ti ti-user text-info me-1"></i>{{ $person->name ?? '—' }}
                                </td>
                                <td>
                                    <span class="badge bg-label-primary">{{ $person->position ?? '—' }}</span>
                                </td>
                                <td>{{ $person->phone ?? '—' }}</td>
                                <td>{{ $person->email ?? '—' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

