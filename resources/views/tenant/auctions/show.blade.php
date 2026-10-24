@extends('layouts.dashboard')
@section('dashboard-scope', 'tenant')

@section('title', 'تفاصيل المزاد - ' . $auction->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
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
                            alt="{{ $auction->name }}" class="rounded" style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="text-center">{{ $auction->name }}</h5>
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

            {{-- كارت مخرجات ومعاينة المزاد التسويقية --}}
            @php
                $hasTemplates = !empty($auction->template_snapshot);
                $hasBrochure = !empty($auction->template_snapshot['brochure']);
                $hasBillboard = !empty($auction->template_snapshot['billboard']);
                $hasSocial = !empty($auction->template_snapshot['social']);
            @endphp
            @if($hasTemplates)
                <div class="card mb-4 shadow-sm border-primary">
                    <div class="card-header bg-label-primary py-3">
                        <h6 class="card-title mb-0 text-primary fw-bold">
                            <i class="ti ti-palette me-1"></i>مخرجات ومعاينة القوالب المعتمدة
                        </h6>
                    </div>
                    <div class="card-body pt-3">
                        <div class="d-grid gap-2">
                            @if($hasBrochure)
                                <div class="d-flex gap-2">
                                    <a href="{{ tenant_route_name('auctions.preview', ['auction' => $auction->id, 'type' => 'brochure']) }}"
                                       class="btn btn-primary flex-grow-1 d-flex align-items-center justify-content-between" target="_blank">
                                        <span><i class="ti ti-eye me-2"></i>معاينة كتيب البروشور</span>
                                        <i class="ti ti-external-link"></i>
                                    </a>
                                    <a href="{{ tenant_route_name('auctions.download', ['auction' => $auction->id, 'type' => 'brochure']) }}" class="btn btn-outline-primary" title="تحميل PDF">
                                        <i class="ti ti-download"></i>
                                    </a>
                                </div>
                            @endif

                            @if($hasBillboard)
                                <div class="d-flex gap-2">
                                    <a href="{{ tenant_route_name('auctions.preview', ['auction' => $auction->id, 'type' => 'billboard']) }}"
                                       class="btn btn-warning flex-grow-1 d-flex align-items-center justify-content-between" target="_blank">
                                        <span><i class="ti ti-eye me-2"></i>معاينة اللوحة الإعلانية</span>
                                        <i class="ti ti-external-link"></i>
                                    </a>
                                    <a href="{{ tenant_route_name('auctions.download', ['auction' => $auction->id, 'type' => 'billboard']) }}" class="btn btn-outline-warning" title="تحميل PDF">
                                        <i class="ti ti-download"></i>
                                    </a>
                                </div>
                            @endif

                            @if($hasSocial)
                                <div class="d-flex gap-2">
                                    <a href="{{ tenant_route_name('auctions.preview', ['auction' => $auction->id, 'type' => 'social']) }}"
                                       class="btn btn-info flex-grow-1 d-flex align-items-center justify-content-between" target="_blank">
                                        <span><i class="ti ti-eye me-2"></i>معاينة منشورات التواصل</span>
                                        <i class="ti ti-external-link"></i>
                                    </a>
                                    <a href="{{ tenant_route_name('auctions.download', ['auction' => $auction->id, 'type' => 'social']) }}" class="btn btn-outline-info" title="تحميل PDF">
                                        <i class="ti ti-download"></i>
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            @endif

            @if ($auction->attachments->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h6 class="card-title mb-0">وثائق ومرفقات المزاد</h6>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            @foreach ($auction->attachments as $attachment)
                                <li class="d-flex align-items-center justify-content-between gap-2 py-2 border-bottom">
                                    <div class="d-flex align-items-center gap-2 text-truncate">
                                        <i class="ti ti-file text-primary fs-5" aria-hidden="true"></i>
                                        <span class="text-truncate" title="{{ $attachment->file_name }}">{{ $attachment->file_name }}</span>
                                    </div>
                                    <a href="{{ tasset($attachment->file_path) }}" target="_blank"
                                        class="btn btn-sm btn-icon btn-label-primary" title="تنزيل {{ $attachment->file_name }}">
                                        <i class="ti ti-download" aria-hidden="true"></i>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>

        {{-- تفاصيل العقارات المشمولة --}}
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
        </div>
    </div>
@endsection
