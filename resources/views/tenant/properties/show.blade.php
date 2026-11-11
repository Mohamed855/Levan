@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل العقار - ' . $property->name)

@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ tenant_route_name('properties.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
            @can('properties.edit')
                <a href="{{ tenant_route_name('properties.edit', $property->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل العقار
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        {{-- كارت معلومات العقار (العمود الأيمن) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <img src="{{ $property->main_image ? tasset($property->main_image) : asset('static/brand/logos/logo.svg') }}"
                            alt="{{ $property->name }}" class="rounded-circle border shadow-sm"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h4 class="mb-1 fw-bold text-primary">{{ $property->name }}</h4>
                    <p class="mb-2 text-muted fw-medium">{{ $property->property_number }}</p>
                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                        <span class="badge bg-label-primary fs-6">{{ $property->type_label }}</span>
                        <span class="badge {{ $property->data_status_badge_class }} fs-6">{{ $property->data_status_label }}</span>
                    </div>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ number_format((float) $property->area) }} م²</h5>
                            <small class="text-muted">المساحة</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success">{{ $property->auctions->count() }}</h5>
                            <small class="text-muted">المزادات</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-hash text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">رقم الأصل:</span>
                                <span>{{ $property->property_number }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-link text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الإسناد المرتبط:</span>
                                @if ($property->assignment)
                                    <a href="{{ tenant_route_name('assignments.show', $property->assignment->id) }}"
                                        class="text-primary fw-medium">
                                        {{ $property->assignment->name }}
                                    </a>
                                @else
                                    <span class="text-muted">غير مرتبط بإسناد</span>
                                @endif
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-category text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تصنيف الاستخدام:</span>
                                <span>{{ $property->usage_classification_label }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-map-pin text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المدينة:</span>
                                <span>{{ $property->city ?? '-' }}</span>
                            </li>
                            @if ($property->district)
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="ti ti-map text-primary me-2 fs-5"></i>
                                    <span class="fw-bold me-2">الحي / النطاق:</span>
                                    <span>{{ $property->district }}</span>
                                </li>
                            @endif
                            @if ($property->administrative_region)
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="ti ti-compass text-primary me-2 fs-5"></i>
                                    <span class="fw-bold me-2">المنطقة الإدارية:</span>
                                    <span>{{ $property->administrative_region ?? '-' }}</span>
                                </li>
                            @endif
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-map-2 text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">رقم المخطط:</span>
                                <span>{{ $property->plan_number ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-box text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">رقم القطعة:</span>
                                <span>{{ $property->parcel_number ?? '-' }}</span>
                            </li>
                            @if ($property->google_maps_url)
                                <li class="mb-3 d-flex align-items-center">
                                    <i class="ti ti-map-search text-primary me-2 fs-5"></i>
                                    <span class="fw-bold me-2">رابط الموقع:</span>
                                    <a href="{{ $property->google_maps_url }}" target="_blank" rel="noopener noreferrer"
                                        class="text-primary text-decoration-none">
                                        فتح على Google Maps
                                    </a>
                                </li>
                            @endif
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-file-certificate text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">رقم الصك:</span>
                                <span>{{ $property->deed_number ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ الصك:</span>
                                <span>@date($property->deed_date)</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-building-bank text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">جهة الإصدار:</span>
                                <span>{{ $property->issuing_authority ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-key text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">نوع الملكية:</span>
                                <span>{{ $property->ownership_type_label }}</span>
                            </li>
                            <li
                                class="{{ $property->short_description || $property->agent_name ? 'mb-3' : 'mb-2' }} d-flex align-items-center">
                                <i class="ti ti-status-change text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">حالة العقار:</span>
                                <span class="badge bg-label-{{ $property->is_active ? 'success' : 'danger' }}">
                                    {{ $property->is_active ? 'نشط' : 'متوقف' }}
                                </span>
                            </li>
                            @if ($property->short_description)
                                <li class="{{ $property->agent_name ? 'mb-3' : 'mb-2' }} d-flex align-items-start">
                                    <i class="ti ti-notes text-primary me-2 fs-5"></i>
                                    <div>
                                        <span class="fw-bold me-2">الوصف:</span>
                                        <span class="text-muted">{{ $property->short_description }}</span>
                                    </div>
                                </li>
                            @endif
                            @if ($property->agent_name)
                                <li class="mb-2 d-flex align-items-center border-top pt-2">
                                    <i class="ti ti-user-check text-primary me-2 fs-5"></i>
                                    <span class="fw-bold me-2">الوكيل:</span>
                                    <span>{{ $property->agent_name }} (هوية:
                                        {{ $property->agent_national_id ?? '-' }})</span>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            {{-- كارت صورة العقار المحددة --}}
            @if ($property->aerial_image)
                <div class="card mb-4">
                    <div class="card-header pb-2 mb-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-photo-edit text-primary me-2"></i>صورة العقار المحددة
                        </h6>
                        <a href="{{ tasset($property->aerial_image) }}"
                            download="property-{{ $property->property_number }}-boundary.png"
                            class="btn btn-xs btn-primary">
                            <i class="ti ti-download me-1"></i> تحميل
                        </a>
                    </div>
                    <div class="card-body text-center">
                        <a href="{{ tasset($property->aerial_image) }}" target="_blank" title="عرض الصورة بالحجم الكامل">
                            <img src="{{ tasset($property->aerial_image) }}" alt="تحديد العقار على الصورة"
                                class="img-fluid rounded border" style="max-height: 280px; object-fit: contain;">
                        </a>
                    </div>
                </div>
            @endif

            {{-- كارت المستندات والمرفقات --}}
            @if ($property->attachments->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header pb-2 mb-3 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0">
                            <i class="ti ti-paperclip text-primary me-2"></i>المستندات والمرفقات
                        </h6>
                        <span class="badge bg-label-primary">{{ $property->attachments->count() }}</span>
                    </div>
                    <div class="card-body">
                        <div class="assignment-file-list">
                            @foreach ($property->attachments as $doc)
                                <x-ui.file-card :file="$doc" layout="card" />
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>

        {{-- تفاصيل الجداول المرتبطة (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- حدود وأطوال العقار --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-ruler-measure text-primary me-2"></i>حدود العقار وأطوالها ({{ $property->boundaries->count() }})
                    </h5>
                    @if ($property->boundaries->count() > 0)
                        <span class="badge bg-label-primary">{{ $property->boundaries->count() }} حد</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>الجهة</th>
                                <th>نوع الحد</th>
                                <th>وصف الحد</th>
                                <th>الطول (م)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($property->boundaries as $boundary)
                                <tr>
                                    <td><span class="badge bg-label-primary">{{ $boundary->direction_label }}</span></td>
                                    <td>{{ $boundary->border_type ?: '-' }}</td>
                                    <td>{{ $boundary->border_description ?: '-' }}</td>
                                    <td>{{ number_format((float) $boundary->length, 2) }} م</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">لم يتم إدخال حدود للعقار.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ملاك العقار --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-users text-primary me-2"></i>ملاك العقار ({{ $property->owners->count() }})
                    </h5>
                    @if ($property->owners->count() > 0)
                        <span class="badge bg-label-primary">{{ $property->owners->count() }} مالك</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>اسم المالك</th>
                                <th>رقم الهوية / السجل التجاري</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($property->owners as $owner)
                                <tr>
                                    <td>{{ $owner->owner_name }}</td>
                                    <td><code>{{ $owner->national_id_or_cr }}</code></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center py-4 text-muted">لم يتم تسجيل ملاك للعقار.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- وحدات العقار إن وجدت --}}
            @if ($property->units->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center mb-3">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-layout-grid text-primary me-2"></i>وحدات العقار ({{ $property->units->count() }})
                        </h5>
                        <span class="badge bg-label-primary">{{ $property->units->count() }} وحدة</span>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th>رقم الوحدة</th>
                                    <th>نوع الوحدة</th>
                                    <th>المساحة</th>
                                    <th>الاستخدام الحالي</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($property->units as $unit)
                                    <tr>
                                        <td><code>{{ $unit->unit_number }}</code></td>
                                        <td>{{ $unit->unit_type ?: '-' }}</td>
                                        <td>{{ number_format((float) $unit->area, 2) }} م²</td>
                                        <td>{{ $unit->current_use ?: '-' }}</td>
                                        <td><span class="badge bg-label-info">{{ $unit->status?->label() ?? 'متاح' }}</span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- المزادات المرتبطة --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-gavel text-primary me-2"></i>المزادات المرتبطة بهذا العقار ({{ $property->auctions->count() }})
                    </h5>
                    @if ($property->auctions->count() > 0)
                        <span class="badge bg-label-primary">{{ $property->auctions->count() }} مزاد</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>اسم المزاد</th>
                                <th>تاريخ البداية</th>
                                <th>النوع</th>
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
                                    <td>@date($auc->start_date)</td>
                                    <td><span class="badge bg-label-secondary">{{ $auc->type_label }}</span></td>
                                    <td><span
                                            class="badge {{ $auc->status_badge_class }}">{{ $auc->status_label }}</span>
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
