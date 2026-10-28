@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل المزاد - ' . $auction->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-gavel me-2 text-primary"></i>تفاصيل المزاد: {{ $auction->name }}
            </h4>
            <div class="d-flex align-items-center gap-2">
                <code class="copyable-code fs-6"
                    onclick="navigator.clipboard.writeText('AUC-{{ str_pad((string) $auction->id, 5, '0', STR_PAD_LEFT) }}'); toastr.success('تم نسخ رقم المزاد');">
                    AUC-{{ str_pad((string) $auction->id, 5, '0', STR_PAD_LEFT) }}
                </code>
                <span class="badge {{ $auction->status_badge_class }}">{{ $auction->status_label }}</span>
                <span class="badge bg-info">{{ $auction->type_label }}</span>
                @if ($auction->city)
                    <span class="badge bg-secondary"><i class="ti ti-map-pin me-1"></i>{{ $auction->city }}</span>
                @endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @can('auctions.edit')
                <a href="{{ tenant_route_name('auctions.edit', $auction->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل المزاد
                </a>
            @endcan
            <a href="{{ tenant_route_name('auctions.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت الغلاف والمعلومات الرئيسية --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mb-3 text-center">
                        <img src="{{ $auction->cover_image ? tasset($auction->cover_image) : asset('assets/img/illustrations/page-pricing-enterprise.png') }}"
                            alt="{{ $auction->name }}" class="rounded"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="mb-1">{{ $auction->name }}</h5>
                    <span class="badge bg-primary mb-3">{{ $auction->category_label }}</span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ $auction->properties->count() }}</h5>
                            <small class="text-muted">العقارات</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ number_format((float) $auction->properties->sum('estimated_value')) }}
                            </h5>
                            <small class="text-muted">إجمالي التقدير (ر.س)</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            @if ($auction->description)
                                <li class="mb-2"><span class="fw-bold me-1">الوصف:</span>
                                    <span class="text-muted small">{{ $auction->description }}</span>
                                </li>
                            @endif
                            <li class="mb-2"><span class="fw-bold me-1">الإسناد المرتبط:</span>
                                @if ($auction->assignment)
                                    <a href="{{ tenant_route_name('assignments.show', $auction->assignment_id) }}"
                                        class="text-primary fw-medium">
                                        {{ $auction->assignment->name }} ({{ $auction->assignment->assignment_number }})
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </li>
                            <li class="mb-2"><span class="fw-bold me-1">الجهة المشرفة:</span>
                                <span>{{ $auction->supervising_entity ?? '-' }}</span>
                            </li>
                            <li class="mb-2"><span class="fw-bold me-1">مدير المشروع:</span>
                                <span>{{ $auction->project_manager_name ?? '-' }}</span>
                            </li>
                            <li class="mb-2"><span class="fw-bold me-1">منصة المزاد:</span>
                                <span>{{ $auction->platform_name ?? '-' }}</span>
                            </li>
                            @if ($auction->platform_url)
                                <li class="mb-2"><span class="fw-bold me-1">رابط المزاد:</span>
                                    <a href="{{ $auction->platform_url }}" target="_blank" class="text-primary">زيارة
                                        المنصة <i class="ti ti-external-link"></i></a>
                                </li>
                            @endif
                            <li class="mb-2"><span class="fw-bold me-1">تاريخ البداية:</span>
                                <span>{{ $auction->start_date?->format('Y-m-d') }} {{ $auction->start_time }}</span>
                            </li>
                            <li class="mb-0"><span class="fw-bold me-1">تاريخ النهاية:</span>
                                <span>{{ $auction->end_date?->format('Y-m-d') }} {{ $auction->end_time }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- كارت ملفات وتقارير المزاد --}}
            @php
                $gen = $auction->latestReportGeneration;
                $isReady = $gen?->isReady();
                $isProcessing = $gen?->isProcessing();
                $isFailed = $gen?->isFailed();
            @endphp
            <div class="card mb-4 shadow-sm border-primary">
                <div class="card-header bg-primary py-3 d-flex justify-content-between align-items-center">
                    <h6 class="card-title mb-0 text-primary fw-bold">
                        <i class="ti ti-file-analytics me-1"></i>ملفات وتقارير المزاد
                    </h6>
                    @if ($isReady)
                        <span class="badge bg-success">جاهز للتحميل</span>
                    @elseif($isProcessing)
                        <span class="badge bg-warning"><i class="ti ti-loader ti-spin me-1"></i>قيد الإعداد</span>
                    @elseif($isFailed)
                        <span class="badge bg-danger">فشل الإعداد</span>
                    @else
                        <span class="badge bg-secondary">غير مجهز بعد</span>
                    @endif
                </div>
                <div class="card-body pt-3">
                    @if ($isFailed)
                        <div class="alert alert-danger p-2 small mb-3">
                            <i class="ti ti-alert-circle me-1"></i>
                            {{ $gen->error_message ?? 'تعذر تجهيز الملفات تلقائياً.' }}
                        </div>
                    @endif

                    <div class="d-grid gap-2">
                        <a href="{{ $isReady && filled($gen->outputs_path) ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'outputs']) : 'javascript:void(0)' }}"
                            class="btn btn-primary d-flex align-items-center justify-content-between {{ !$isReady || blank($gen->outputs_path) ? 'disabled' : '' }}"
                            @if (!$isReady || blank($gen->outputs_path)) disabled aria-disabled="true" @endif>
                            <span><i class="ti ti-file-text me-2"></i>تحميل مخرجات المزاد</span>
                            <i class="ti ti-download"></i>
                        </a>

                        <a href="{{ $isReady && filled($gen->report_path) ? tenant_route_name('auctions.reports.download', ['auction' => $auction->id, 'type' => 'report']) : 'javascript:void(0)' }}"
                            class="btn btn-success d-flex align-items-center justify-content-between {{ !$isReady || blank($gen->report_path) ? 'disabled' : '' }}"
                            @if (!$isReady || blank($gen->report_path)) disabled aria-disabled="true" @endif>
                            <span><i class="ti ti-file-analytics me-2"></i>تحميل تقرير المزاد</span>
                            <i class="ti ti-download"></i>
                        </a>

                        @can('auctions.edit')
                            @if ($isReady)
                                <button type="button" id="btn-retry-reports" class="btn btn-outline-secondary btn-sm mt-1">
                                    <i class="ti ti-refresh me-1"></i> إعادة توليد الملفات
                                </button>
                            @elseif ($isProcessing)
                                <button type="button" id="btn-retry-reports" class="btn btn-outline-warning mt-2">
                                    <i class="ti ti-bolt me-1"></i> توليد الملفات الآن فوراً
                                </button>
                            @elseif ($isFailed)
                                <button type="button" id="btn-retry-reports" class="btn btn-outline-danger mt-2">
                                    <i class="ti ti-refresh me-1"></i> إعادة محاولة الإعداد
                                </button>
                            @else
                                <button type="button" id="btn-retry-reports" class="btn btn-primary mt-2">
                                    <i class="ti ti-player-play me-1"></i> بدء تجهيز ملفات المزاد
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            </div>

            @if ($auction->attachments->isNotEmpty())
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h6 class="card-title mb-0">وثائق ومرفقات المزاد</h6>
                    </div>
                    <div class="card-body">
                        <div class="mb-0">
                            @foreach ($auction->attachments as $attachment)
                                <x-ui.file-card :file="$attachment" layout="list" />
                            @endforeach
                        </div>
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
                                    <td><span class="badge bg-info">{{ $prop->type_label }}</span></td>
                                    <td>{{ number_format((float) $prop->area) }} م²</td>
                                    <td>{{ number_format((float) $prop->estimated_value) }} ر.س</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">لم يتم إدراج عقارات في هذا
                                        المزاد
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

@section('page-script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const statusUrl = @json(tenant_route_name('auctions.reports.status', $auction->id));
            const retryUrl = @json(tenant_route_name('auctions.reports.retry', $auction->id));
            const csrfToken = '{{ csrf_token() }}';

            const retryBtn = document.getElementById('btn-retry-reports');
            if (retryBtn) {
                retryBtn.addEventListener('click', function() {
                    retryBtn.disabled = true;
                    retryBtn.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> جاري تجهيز الملفات...';

                    fetch(retryUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.success) {
                                if (window.toastr) {
                                    toastr.success(data.message);
                                }
                                setTimeout(() => location.reload(), 1000);
                            } else {
                                if (window.toastr) {
                                    toastr.error(data.message || 'حدث خطأ');
                                }
                                retryBtn.disabled = false;
                            }
                        })
                        .catch(() => {
                            if (window.toastr) {
                                toastr.error('حدث خطأ في الاتصال');
                            }
                            retryBtn.disabled = false;
                        });
                });
            }

            @if ($auction->latestReportGeneration && in_array($auction->latestReportGeneration->status, ['queued', 'processing']))
                const pollInterval = setInterval(() => {
                    fetch(statusUrl, {
                            headers: {
                                'Accept': 'application/json'
                            }
                        })
                        .then(res => res.json())
                        .then(data => {
                            if (data.status === 'ready' || data.status === 'failed') {
                                clearInterval(pollInterval);
                                location.reload();
                            }
                        })
                        .catch(() => {});
                }, 3000);
            @endif
        });
    </script>
@endsection
