@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل المزاد - ' . $auction->name)

@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1"><i class="ti ti-gavel me-2 text-primary"></i>{{ $auction->name }}</h4>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge {{ $auction->status_badge_class }}">{{ $auction->status_label }}</span>
                <span class="badge bg-label-info">{{ $auction->type_label }}</span>
                <span class="badge bg-secondary">{{ $auction->city }}</span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('auctions.edit')
                <a href="{{ tenant_route_name('auctions.edit', $auction->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل المزاد
                </a>
            @endcan
            <a href="{{ tenant_route_name('auctions.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للمزادات
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت الغلاف والمعلومات الرئيسية --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body">
                    <div class="mb-3 text-center">
                        <img src="{{ $auction->cover_image ? tasset($auction->cover_image) : asset('assets/img/illustrations/page-pricing-enterprise.png') }}"
                            alt="Cover" class="rounded w-100" style="max-height: 220px; object-fit: cover;">
                    </div>
                    <h5>{{ $auction->name }}</h5>
                    <p class="text-muted small">{{ $auction->description ?? 'لا يوجد وصف مضاف.' }}</p>

                    <ul class="list-unstyled border-top pt-3">
                        <li class="mb-2"><span class="fw-bold me-1">الإسناد المرتبط:</span>
                            @if ($auction->assignment)
                                <a href="{{ tenant_route_name('assignments.show', $auction->assignment_id) }}"
                                    class="text-primary fw-medium">
                                    {{ $auction->assignment->name }} ({{ $auction->assignment->assignment_number }})
                                </a>
                            @else
                                -
                            @endif
                        </li>
                        <li class="mb-2"><span class="fw-bold me-1">الجهة المشرفة:</span>
                            <span>{{ $auction->supervising_entity ?? '-' }}</span></li>
                        <li class="mb-2"><span class="fw-bold me-1">منصة المزاد:</span>
                            <span>{{ $auction->platform_name ?? '-' }}</span></li>
                        @if ($auction->platform_url)
                            <li class="mb-2"><span class="fw-bold me-1">رابط المزاد:</span>
                                <a href="{{ $auction->platform_url }}" target="_blank" class="text-primary">زيارة المنصة <i
                                        class="ti ti-external-link"></i></a>
                            </li>
                        @endif
                        <li class="mb-2"><span class="fw-bold me-1">تاريخ البداية:</span>
                            <span>{{ $auction->start_date?->format('Y-m-d') }} {{ $auction->start_time }}</span></li>
                        <li class="mb-2"><span class="fw-bold me-1">تاريخ النهاية:</span>
                            <span>{{ $auction->end_date?->format('Y-m-d') }} {{ $auction->end_time }}</span></li>
                    </ul>
                </div>
            </div>
        </div>

        {{-- تفاصيل العقارات المشمولة والمرفقات --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- العقارات المدرجة --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">العقارات المدرجة في المزاد ({{ $auction->properties->count() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>رقم العقار</th>
                                <th>اسم العقار</th>
                                <th>النوع</th>
                                <th>المساحة</th>
                                <th>القيمة التقديرية</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auction->properties as $prop)
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
                                    <td>{{ number_format((float) $prop->estimated_value) }} ر.س</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">لم يتم إدراج عقارات في هذا المزاد
                                        بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- المرفقات --}}
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">وثائق ومرفقات المزاد ({{ $auction->attachments->count() }})</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>اسم الملف</th>
                                <th>التصنيف</th>
                                <th>الحجم</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auction->attachments as $att)
                                <tr>
                                    <td><i class="ti ti-file me-1"></i>{{ $att->file_name }}</td>
                                    <td><span class="badge bg-label-primary">{{ $att->category_label }}</span></td>
                                    <td>{{ number_format($att->file_size / 1024, 1) }} KB</td>
                                    <td>
                                        <a href="{{ tasset($att->file_path) }}" target="_blank"
                                            class="btn btn-sm btn-icon btn-secondary">
                                            <i class="ti ti-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">لا توجد ملفات مرفقة لهذا المزاد.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
