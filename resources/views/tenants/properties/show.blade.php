@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل العقار - ' . $property->name)

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-building me-2 text-primary"></i>تفاصيل العقار: {{ $property->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <code class="copyable-code fs-6" style="cursor: pointer;"
                    onclick="navigator.clipboard.writeText('{{ $property->property_number }}'); toastr.success('تم نسخ رقم العقار');">
                    رقم العقار: {{ $property->property_number }}
                </code>
                <span class="badge {{ $property->data_status_badge_class }}">{{ $property->data_status_label }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('properties.edit')
                <a href="{{ tenant_route_name('properties.edit', $property->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل العقار
                </a>
            @endcan
            <a href="{{ tenant_route_name('properties.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-5 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="{{ $property->main_image ? tasset($property->main_image) : asset('assets/img/illustrations/page-pricing-standard.png') }}"
                            alt="Property" class="rounded w-100" style="max-height: 240px; object-fit: cover;">
                    </div>
                    <h5 class="card-title text-center">{{ $property->name }}</h5>
                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge bg-label-info">{{ $property->type_label }}</span>
                        <span class="badge bg-label-primary">{{ $property->city }}</span>
                    </div>

                    <ul class="list-unstyled border-top pt-3">
                        <li class="mb-2"><span class="fw-bold me-1">الإسناد التابع له:</span>
                            @if ($property->assignment)
                                <a href="{{ tenant_route_name('assignments.show', $property->assignment_id) }}"
                                    class="text-primary fw-medium">
                                    {{ $property->assignment->name }} ({{ $property->assignment->assignment_number }})
                                </a>
                            @else
                                -
                            @endif
                        </li>
                        <li class="mb-2"><span class="fw-bold me-1">الحي:</span>
                            <span>{{ $property->district ?? '-' }}</span></li>
                        <li class="mb-2"><span class="fw-bold me-1">المساحة:</span>
                            <span>{{ number_format((float) $property->area) }} م²</span></li>
                        <li class="mb-2"><span class="fw-bold me-1">رقم الصك:</span>
                            <span>{{ $property->deed_number ?? '-' }}</span></li>
                        <li class="mb-2"><span class="fw-bold me-1">القيمة التقديرية:</span>
                            <span>{{ number_format((float) $property->estimated_value) }} ر.س</span></li>
                        <li class="mb-2"><span class="fw-bold me-1">حالة الإدراج:</span>
                            <span>{{ $property->status_label }}</span></li>
                    </ul>

                    @if ($property->notes)
                        <div class="border-top pt-2">
                            <small class="text-muted d-block mb-1 fw-bold">ملاحظات:</small>
                            <p class="text-muted small mb-0">{{ $property->notes }}</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-xl-7 col-lg-7 col-md-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">المزادات التي أدرج بها هذا العقار</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم المزاد</th>
                                <th>النوع</th>
                                <th>تاريخ البداية</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($property->auctions as $auc)
                                <tr>
                                    <td>
                                        <a href="{{ tenant_route_name('auctions.show', $auc->id) }}"
                                            class="fw-medium text-body">
                                            {{ $auc->name }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-secondary">{{ $auc->type_label }}</span></td>
                                    <td>{{ $auc->start_date?->format('Y-m-d') }}</td>
                                    <td><span class="badge {{ $auc->status_badge_class }}">{{ $auc->status_label }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">لم يتم إدراج هذا العقار في أي
                                        مزاد حتى الآن.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
