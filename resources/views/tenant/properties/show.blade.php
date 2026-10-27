@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل العقار - ' . $property->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-building me-2 text-primary"></i>تفاصيل العقار: {{ $property->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <code class="copyable-code fs-6"
                    onclick="navigator.clipboard.writeText('{{ $property->property_number }}'); toastr.success('تم نسخ رقم العقار');">
                    {{ $property->property_number }}
                </code>
                <span class="badge {{ $property->data_status_badge_class }}">{{ $property->data_status_label }}</span>
                @if ($property->assignment)
                    <span class="badge bg-primary"><i class="ti ti-link me-1"></i>مرتبط بإسناد:
                        {{ $property->assignment->name }}</span>
                @else
                    <span class="badge bg-secondary"><i class="ti ti-link-off me-1"></i>غير مرتبط بإسناد</span>
                @endif
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
        {{-- كارت معلومات العقار (العمود الأيسر) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <img src="{{ $property->main_image ? tasset($property->main_image) : asset('static/brand/logos/logo.svg') }}"
                            alt="{{ $property->name }}" class="rounded"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="mb-1">{{ $property->name }}</h5>
                    <span class="badge bg-primary mb-3">{{ $property->type_label }}</span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ number_format((float) $property->area) }} م²</h5>
                            <small class="text-muted">المساحة</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $property->auctions->count() }}</h5>
                            <small class="text-muted">المزادات</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled">
                            <li class="mb-2"><span class="fw-bold me-1">تصنيف الاستخدام:</span>
                                <span>{{ $property->usage_classification_label }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">المدينة:</span>
                                <span>{{ $property->city ?? '-' }}</span></li>
                            @if ($property->district)
                                <li class="mb-2"><span class="fw-bold me-1">الحي / النطاق:</span>
                                    <span>{{ $property->district }}</span></li>
                            @endif
                            @if ($property->administrative_region)
                                <li class="mb-2"><span class="fw-bold me-1">المنطقة الإدارية:</span>
                                    <span>{{ $property->administrative_region }}</span></li>
                            @endif
                            <li class="mb-2"><span class="fw-bold me-1">رقم المخطط:</span>
                                <span>{{ $property->plan_number ?? '-' }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">رقم القطعة:</span>
                                <span>{{ $property->parcel_number ?? '-' }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">رقم الصك:</span>
                                <span>{{ $property->deed_number ?? '-' }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">تاريخ الصك:</span>
                                <span>{{ $property->deed_date?->format('Y-m-d') ?? '-' }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">جهة الإصدار:</span>
                                <span>{{ $property->issuing_authority ?? '-' }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">نوع الملكية:</span>
                                <span>{{ $property->ownership_type_label }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">حالة العقار:</span>
                                <span
                                    class="badge {{ $property->is_active ? 'bg-success' : 'bg-danger' }}">{{ $property->is_active ? 'نشط' : 'متوقف' }}</span>
                            </li>
                            @if ($property->short_description)
                                <li class="mb-2"><span class="fw-bold me-1">الوصف:</span> <span
                                        class="text-muted">{{ $property->short_description }}</span></li>
                            @endif
                            @if ($property->agent_name)
                                <li class="mb-2 border-top pt-2"><span class="fw-bold me-1">الوكيل:</span>
                                    <span>{{ $property->agent_name }} (هوية:
                                        {{ $property->agent_national_id ?? '-' }})</span></li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            {{-- كارت صورة العقار المحددة --}}
            @if ($property->aerial_image)
                <div class="card mb-4">
                    <div class="card-header pb-2 d-flex justify-content-between align-items-center">
                        <h6 class="card-title mb-0"><i class="ti ti-photo-edit me-1 text-primary"></i> صورة العقار المحددة
                        </h6>
                        <a href="{{ tasset($property->aerial_image) }}"
                            download="property-{{ $property->property_number }}-boundary.png"
                            class="btn btn-xs btn-primary">
                            <i class="ti ti-download me-1"></i> تحميل
                        </a>
                    </div>
                    <div class="card-body text-center p-2">
                        <a href="{{ tasset($property->aerial_image) }}" target="_blank" title="عرض الصورة بالحجم الكامل">
                            <img src="{{ tasset($property->aerial_image) }}" alt="تحديد العقار على الصورة"
                                class="img-fluid rounded border shadow-sm" style="max-height: 280px; object-fit: contain;">
                        </a>
                    </div>
                </div>
            @endif

            {{-- كارت المستندات والمرفقات --}}
            @if ($property->attachments->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h6 class="card-title mb-0">المستندات والمرفقات</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach ($property->attachments as $doc)
                                <li class="d-flex align-items-center justify-content-between py-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                        <i class="ti ti-file text-primary fs-5"></i>
                                        <span class="text-truncate"
                                            title="{{ $doc->original_name }}">{{ $doc->original_name }}</span>
                                    </div>
                                    <a href="{{ tasset($doc->file_path) }}" target="_blank"
                                        class="btn btn-sm btn-icon btn-primary" title="عرض أو تحميل">
                                        <i class="ti ti-download"></i>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        {{-- تفاصيل الجداول المرتبطة (العمود الأيمن) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- حدود وأطوال العقار --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">حدود العقار وأطوالها ({{ $property->boundaries->count() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
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
                                    <td><span class="badge bg-primary">{{ $boundary->direction_label }}</span></td>
                                    <td>{{ $boundary->border_type ?: '-' }}</td>
                                    <td>{{ $boundary->border_description ?: '-' }}</td>
                                    <td class="fw-bold">{{ number_format((float) $boundary->length, 2) }} م</td>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">ملاك العقار ({{ $property->owners->count() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم المالك</th>
                                <th>رقم الهوية / السجل التجاري</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($property->owners as $owner)
                                <tr>
                                    <td class="fw-medium">{{ $owner->owner_name }}</td>
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
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">وحدات العقار ({{ $property->units->count() }})</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
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
                                        <td><span class="badge bg-info">{{ $unit->status?->label() ?? 'متاح' }}</span>
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
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">المزادات المرتبطة بهذا العقار ({{ $property->auctions->count() }})</h5>
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
                            @forelse($property->auctions as $auc)
                                <tr>
                                    <td>
                                        <a href="{{ tenant_route_name('auctions.show', $auc->id) }}"
                                            class="fw-medium text-body">
                                            {{ $auc->name }}
                                        </a>
                                    </td>
                                    <td>{{ $auc->start_date?->format('Y-m-d') }}</td>
                                    <td><span class="badge bg-secondary">{{ $auc->type_label }}</span></td>
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
