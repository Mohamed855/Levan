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
                @if ($property->is_linked)
                    <span class="badge bg-label-primary"><i class="ti ti-link me-1"></i>مرتبط بإسناد:
                        {{ $property->assignment?->name }}</span>
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
                <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- كارت الصورة والمعلومات الأساسية --}}
        <div class="col-xl-4 col-lg-5">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="{{ $property->main_image ? tasset($property->main_image) : asset('static/brand/logos/logo.svg') }}"
                            alt="Property Main" class="rounded w-100" style="max-height: 240px; object-fit: cover;">
                    </div>
                    <h5 class="card-title text-center mb-1">{{ $property->name }}</h5>
                    <p class="text-muted text-center small mb-3">{{ $property->short_description ?: 'لا يوجد وصف مختصر.' }}
                    </p>

                    <div class="d-flex justify-content-center gap-2 mb-3">
                        <span class="badge bg-label-info">{{ $property->type_label }}</span>
                        <span class="badge bg-label-primary">{{ $property->usage_classification_label }}</span>
                        <span class="badge {{ $property->is_active ? 'bg-label-success' : 'bg-label-danger' }}">
                            {{ $property->is_active ? 'نشط' : 'متوقف' }}
                        </span>
                    </div>

                    <ul class="list-unstyled border-top pt-3 mb-0">
                        <li class="mb-2"><span class="fw-bold me-1">المدينة / الحي:</span> <span>{{ $property->city }} -
                                {{ $property->district ?? '-' }}</span></li>
                        <li class="mb-2"><span class="fw-bold me-1">المنطقة الإدارية:</span>
                            <span>{{ $property->administrative_region ?? '-' }}</span></li>
                        <li class="mb-2"><span class="fw-bold me-1">المساحة:</span>
                            <span>{{ number_format((float) $property->area) }} م²</span></li>
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
                        @if ($property->google_maps_url)
                            <li class="mb-2">
                                <span class="fw-bold me-1">الموقع:</span>
                                <a href="{{ $property->google_maps_url }}" target="_blank"
                                    class="text-primary text-decoration-none">
                                    <i class="ti ti-map-pin me-1"></i> فتح على Google Maps
                                </a>
                            </li>
                        @endif
                    </ul>
                </div>
            </div>

            {{-- بيانات الوكيل إن وجدت --}}
            @if ($property->agent_name || $property->agency_number)
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h6 class="card-title mb-0"><i class="ti ti-user-check me-2 text-primary"></i>بيانات الوكيل</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2"><span class="fw-bold me-1">اسم الوكيل:</span>
                                <span>{{ $property->agent_name ?? '-' }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">رقم الهوية:</span>
                                <span>{{ $property->agent_national_id ?? '-' }}</span></li>
                            <li class="mb-2"><span class="fw-bold me-1">رقم الوكالة:</span>
                                <span>{{ $property->agency_number ?? '-' }}</span></li>
                            <li class="mb-0"><span class="fw-bold me-1">تاريخ الوكالة:</span>
                                <span>{{ $property->agency_date?->format('Y-m-d') ?? '-' }}</span></li>
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        {{-- تفاصيل الحدود والملاك والمرفقات والوحدات والمزادات --}}
        <div class="col-xl-8 col-lg-7">
            {{-- حدود وأطوال العقار --}}
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0"><i class="ti ti-ruler-2 me-2 text-primary"></i>حدود العقار وأطوالها</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
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
                                    <td class="fw-bold">{{ number_format((float) $boundary->length, 2) }} م</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">لم يتم إدخال حدود للعقار.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- ملاك العقار --}}
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0"><i class="ti ti-users me-2 text-primary"></i>ملاك العقار</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>#</th>
                                <th>اسم المالك</th>
                                <th>رقم الهوية / السجل التجاري</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($property->owners as $idx => $owner)
                                <tr>
                                    <td>{{ $idx + 1 }}</td>
                                    <td class="fw-medium">{{ $owner->owner_name }}</td>
                                    <td><code>{{ $owner->national_id_or_cr }}</code></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">لم يتم تسجيل ملاك للعقار.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- وحدات العقار إن وجدت --}}
            @if ($property->units->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h5 class="card-title mb-0"><i class="ti ti-layout-grid me-2 text-primary"></i>وحدات العقار</h5>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>رقم الوحدة</th>
                                    <th>نوع الوحدة</th>
                                    <th>المساحة (م²)</th>
                                    <th>الاستخدام الحالي</th>
                                    <th>الحالة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($property->units as $unit)
                                    <tr>
                                        <td>{{ $unit->unit_number }}</td>
                                        <td>{{ $unit->unit_type ?: '-' }}</td>
                                        <td>{{ number_format((float) $unit->area, 2) }} م²</td>
                                        <td>{{ $unit->current_use ?: '-' }}</td>
                                        <td><span class="badge bg-label-info">{{ $unit->status ?: 'متاح' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            {{-- المرفقات والمستندات --}}
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0"><i class="ti ti-files me-2 text-primary"></i>المستندات والمرفقات</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @forelse($property->attachments as $att)
                            <div class="col-md-6">
                                <div class="border rounded p-2 d-flex align-items-center justify-content-between">
                                    <div class="d-flex align-items-center gap-2 overflow-hidden">
                                        <i class="ti ti-file text-primary fs-3"></i>
                                        <div class="text-truncate">
                                            <span
                                                class="d-block small fw-bold text-truncate">{{ $att->original_name }}</span>
                                            <small class="text-muted">{{ $att->category }}</small>
                                        </div>
                                    </div>
                                    <a href="{{ tasset($att->file_path) }}" target="_blank"
                                        class="btn btn-icon btn-sm btn-label-primary ms-2" title="تحميل">
                                        <i class="ti ti-download"></i>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="col-12 text-center py-3 text-muted">لا توجد ملفات مرفقة لهذا العقار.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- المزادات المرتبطة --}}
            <div class="card">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0"><i class="ti ti-gavel me-2 text-primary"></i>المزادات التي أدرج بها هذا
                        العقار</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
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
