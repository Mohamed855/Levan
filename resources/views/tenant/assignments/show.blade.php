@extends('layouts.dashboard')
@section('dashboard-scope', 'tenant')

@section('title', 'تفاصيل الإسناد - ' . $assignment->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-file-certificate me-2 text-primary"></i>تفاصيل الإسناد: {{ $assignment->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <code class="copyable-code fs-6"
                    onclick="navigator.clipboard.writeText('{{ $assignment->assignment_number }}'); toastr.success('تم نسخ الرقم');">
                    {{ $assignment->assignment_number }}
                </code>
                <span class="badge {{ $assignment->status_badge_class }}">{{ $assignment->status_label }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('assignments.edit')
                <a href="{{ tenant_route_name('assignments.edit', $assignment->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل الإسناد
                </a>
            @endcan
            <a href="{{ tenant_route_name('assignments.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت معلومات الإسناد --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <img src="{{ $assignment->assignmentImage ? tasset($assignment->assignmentImage->file_path) : asset('assets/img/illustrations/page-pricing-enterprise.png') }}"
                            alt="Logo" class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="mb-1">{{ $assignment->name }}</h5>
                    <span class="badge bg-label-primary mb-3">{{ $assignment->category_label }}</span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ $assignment->properties->count() }}</h5>
                            <small class="text-muted">العقارات</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $assignment->auctions->count() }}</h5>
                            <small class="text-muted">المزادات</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled">
                            <li class="mb-2"><span class="fw-bold me-1">الجهة المسندة:</span>
                                <span>{{ $assignment->issuing_entity }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">المدينة:</span>
                                <span>{{ $assignment->city ?? '-' }}</span></li>
                            @if ($assignment->district)
                                <li class="mb-2"><span class="fw-bold me-1">النطاق/الحي:</span>
                                    <span>{{ $assignment->district }}</span></li>
                            @endif
                            <li class="mb-2"><span class="fw-bold me-1">تاريخ البداية:</span>
                                <span>{{ $assignment->start_date?->format('Y-m-d') ?? '-' }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">تاريخ الانتهاء:</span>
                                <span>{{ $assignment->end_date?->format('Y-m-d') ?? '-' }}</span></li>
                        </ul>
                    </div>
                </div>
            </div>

            @php
                $assignmentDocuments = $assignment->attachments->where(
                    'category',
                    '!=',
                    \App\Enums\Tenants\Assignments\AssignmentAttachmentCategory::IMAGE->value,
                );
            @endphp
            @if ($assignmentDocuments->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h6 class="card-title mb-0">المستندات والمرفقات</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach ($assignmentDocuments as $doc)
                                <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                        <i class="ti ti-file text-primary fs-5"></i>
                                        <span class="text-truncate" title="{{ $doc->original_name }}">{{ $doc->original_name }}</span>
                                    </div>
                                    <a href="{{ tasset($doc->file_path) }}" target="_blank" class="btn btn-sm btn-icon btn-label-primary" title="عرض أو تحميل">
                                        <i class="ti ti-download"></i>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        {{-- تفاصيل العقارات والمزادات --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- العقارات التابعة --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">العقارات المسجلة ضمن هذا الإسناد ({{ $assignment->properties->count() }})
                    </h5>
                    @can('properties.create')
                        <a href="{{ tenant_route_name('properties.create') }}?assignment_id={{ $assignment->id }}"
                            class="btn btn-sm btn-primary">
                            <i class="ti ti-plus me-1"></i> إضافة عقار
                        </a>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم العقار</th>
                                <th>اسم العقار</th>
                                <th>النوع</th>
                                <th>المساحة</th>
                                <th>حالة البيانات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignment->properties as $prop)
                                <tr>
                                    <td><code>{{ $prop->property_number }}</code></td>
                                    <td>
                                        <a href="{{ tenant_route_name('properties.show', $prop->id) }}"
                                            class="fw-medium text-body">
                                            {{ $prop->name }}
                                        </a>
                                    </td>
                                    <td><span class="badge bg-label-info">{{ $prop->type_label }}</span></td>
                                    <td>{{ number_format((float) $prop->area) }} م²</td>
                                    <td><span
                                            class="badge {{ $prop->data_status_badge_class }}">{{ $prop->data_status_label }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">لا توجد عقارات مضافة لهذا الإسناد
                                        حتى الآن.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- المزادات التابعة --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">المزادات المرتبطة ({{ $assignment->auctions->count() }})</h5>
                    @can('auctions.create')
                        <a href="{{ tenant_route_name('auctions.create') }}" class="btn btn-sm btn-primary">
                            <i class="ti ti-plus me-1"></i> إنشاء مزاد
                        </a>
                    @endcan
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم المزاد</th>
                                <th>تاريخ البداية</th>
                                <th>النوع</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($assignment->auctions as $auc)
                                <tr>
                                    <td>
                                        <a href="{{ tenant_route_name('auctions.show', $auc->id) }}"
                                            class="fw-medium text-body">
                                            {{ $auc->name }}
                                        </a>
                                    </td>
                                    <td>{{ $auc->start_date?->format('Y-m-d') }}</td>
                                    <td><span class="badge bg-secondary">{{ $auc->type_label }}</span></td>
                                    <td><span class="badge {{ $auc->status_badge_class }}">{{ $auc->status_label }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">لم يتم إنشاء أي مزاد مرتبط بهذا
                                        الإسناد بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
