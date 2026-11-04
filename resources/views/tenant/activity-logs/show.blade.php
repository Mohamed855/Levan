@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل النشاط')

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">
                <i class="ti ti-activity me-2 text-primary"></i>تفاصيل النشاط
            </h4>
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <code class="copyable-code fs-6"
                    onclick="navigator.clipboard.writeText('#{{ $activity->id }}'); toastr.success('تم نسخ معرف النشاط');"
                    style="cursor: pointer;">
                    #{{ $activity->id }}
                </code>
                <span class="badge {{ $activityData['event_badge_class'] ?? 'bg-primary' }}">
                    {{ $activityData['event_arabic'] ?? 'نشاط' }}
                </span>
                <span class="badge bg-primary">
                    {{ $activityData['log_name_arabic'] ?? $activity->log_name }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ tenant_route_name('activity-logs.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للسجل
            </a>
        </div>
    </div>

    <div class="row">
        {{-- العمود الأيسر: بطاقة هوية الفاعل وبيانات السياق --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        @php
                            $avatar = $activity->causer && method_exists($activity->causer, 'getProfilePhotoUrlAttribute')
                                ? $activity->causer->profile_photo_url
                                : asset('static/brand/logos/logo.svg');
                        @endphp
                        <img src="{{ $avatar }}" alt="{{ $activityData['user_name'] }}" class="rounded-circle"
                            style="width: 100px; height: 100px; object-fit: cover;">
                    </div>
                    <h5 class="mb-1">{{ $activityData['user_name'] }}</h5>
                    <span class="badge {{ $activityData['event_badge_class'] ?? 'bg-primary' }} mb-3">
                        {{ $activityData['event_arabic'] }}
                    </span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">#{{ $activity->id }}</h5>
                            <small class="text-muted">معرف النشاط</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ count($changes['fields']) }}</h5>
                            <small class="text-muted">الحقول المتأثرة</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted">نوع النشاط:</span>
                                <span class="badge bg-label-primary">{{ $activityData['log_name_arabic'] }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted">الموضوع:</span>
                                <span class="fw-semibold text-break">{{ $activityData['subject_display'] }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted">نوع الكيان:</span>
                                <span class="text-break">{{ class_basename($activity->subject_type ?? 'غير محدد') }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted">معرف الكيان:</span>
                                <span>#{{ $activity->subject_id ?? '-' }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted">التاريخ:</span>
                                <span>{{ $activityData['formatted_date'] }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted">الوقت النسبي:</span>
                                <span class="badge bg-label-info">{{ $activity->created_at ? $activity->created_at->locale('ar')->diffForHumans() : '-' }}</span>
                            </li>
                            @if ($activity->batch_uuid)
                                <li class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-muted">معرف الدفعة:</span>
                                    <code class="small text-break">{{ $activity->batch_uuid }}</code>
                                </li>
                            @endif
                        </ul>
                    </div>
                </div>
            </div>

            {{-- بطاقة معلومات الاتصال والبيئة --}}
            @if ($activityData['connection_info'])
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-device-desktop text-primary me-2"></i>معلومات الاتصال
                        </h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted"><i class="ti ti-world me-1"></i>عنوان IP:</span>
                                <code class="fs-6">{{ $activityData['connection_info']['ip'] }}</code>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted"><i class="ti ti-device-laptop me-1"></i>نوع الجهاز:</span>
                                <span>{{ $activityData['connection_info']['device'] }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted"><i class="ti ti-brand-windows me-1"></i>نظام التشغيل:</span>
                                <span>{{ $activityData['connection_info']['os'] }}</span>
                            </li>
                            <li class="mb-2 d-flex justify-content-between align-items-center">
                                <span class="fw-bold text-muted"><i class="ti ti-browser me-1"></i>المتصفح:</span>
                                <span>{{ $activityData['connection_info']['browser'] }}</span>
                            </li>
                            @if (!empty($activityData['connection_info']['reason']))
                                <li class="mb-2 d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-danger"><i class="ti ti-alert-triangle me-1"></i>السبب:</span>
                                    <span class="text-danger fw-semibold">{{ $activityData['connection_info']['reason'] }}</span>
                                </li>
                            @endif
                        </ul>

                        @if (!empty($activityData['connection_info']['user_agent']))
                            <div class="mt-3 pt-2 border-top">
                                <small class="text-muted d-block mb-1">User Agent:</small>
                                <div class="bg-light p-2 rounded text-break small font-monospace text-muted" style="font-size: 0.75rem;">
                                    {{ $activityData['connection_info']['user_agent'] }}
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif
        </div>

        {{-- العمود الأيمن: وصف النشاط والتغييرات --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- وصف النشاط --}}
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-file-description text-primary me-2"></i>وصف النشاط
                    </h5>
                </div>
                <div class="card-body">
                    <div class="border rounded p-3 bg-light">
                        <p class="mb-0 text-break" style="white-space: pre-wrap; font-family: inherit;">{{ $activity->description ?: 'لا يوجد وصف مخصص لهذا النشاط.' }}</p>
                    </div>
                </div>
            </div>

            {{-- تفاصيل التغييرات والبيانات --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center pb-2">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-list-details text-primary me-2"></i>تفاصيل التغييرات والبيانات
                    </h5>
                    @if ($changes['has_changes'])
                        <span class="badge bg-label-primary">{{ count($changes['fields']) }} حقل</span>
                    @endif
                </div>
                <div class="card-body">
                    @if ($changes['has_changes'])
                        <div class="table-responsive">
                            <table class="table table-hover table-bordered text-center align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center align-middle" style="width: 25%;">الحقل</th>
                                        @if ($changes['has_old'])
                                            <th class="text-center align-middle" style="width: 37.5%;">القيمة السابقة</th>
                                        @endif
                                        <th class="text-center align-middle" style="width: {{ $changes['has_old'] ? '37.5%' : '75%' }};">
                                            {{ $changes['has_old'] ? 'القيمة الجديدة' : 'القيمة' }}
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($changes['fields'] as $field)
                                        <tr>
                                            <td class="fw-bold text-center align-middle">
                                                <div>{{ $field['label'] }}</div>
                                                <small class="text-muted font-monospace" style="font-size: 0.75rem;">{{ $field['key'] }}</small>
                                            </td>

                                            @if ($changes['has_old'])
                                                <td class="text-center align-middle">
                                                    @if (!$field['has_old'])
                                                        <span class="text-muted">-</span>
                                                    @elseif ($field['old']['type'] === 'sensitive')
                                                        <span class="badge bg-danger text-truncate">{{ $field['old']['display'] }}</span>
                                                    @elseif ($field['old']['type'] === 'file')
                                                        @if ($field['old']['is_image'] && $field['old']['url'])
                                                            <div class="d-flex justify-content-center">
                                                                <x-ui.file-card :url="$field['old']['url']" :name="$field['old']['display']" :isImage="true" />
                                                            </div>
                                                        @else
                                                            <div class="d-flex justify-content-center">
                                                                <x-ui.file-card :url="$field['old']['url']" :name="$field['old']['display']" />
                                                            </div>
                                                        @endif
                                                    @elseif ($field['old']['type'] === 'bool')
                                                        <span class="badge {{ $field['old']['badge'] }}">{{ $field['old']['display'] }}</span>
                                                    @elseif ($field['old']['type'] === 'array')
                                                        <pre class="mb-0 text-start bg-light p-2 rounded small font-monospace" style="white-space: pre-wrap; font-size: 0.8rem; direction: ltr;">{{ $field['old']['display'] }}</pre>
                                                    @elseif ($field['old']['type'] === 'null')
                                                        <span class="text-muted">-</span>
                                                    @else
                                                        <span class="text-break">{{ $field['old']['display'] }}</span>
                                                    @endif
                                                </td>
                                            @endif

                                            <td class="text-center align-middle">
                                                @if (!$field['has_new'])
                                                    <span class="text-muted">-</span>
                                                @elseif ($field['new']['type'] === 'sensitive')
                                                    <span class="badge bg-danger text-truncate">{{ $field['new']['display'] }}</span>
                                                @elseif ($field['new']['type'] === 'file')
                                                    @if ($field['new']['is_image'] && $field['new']['url'])
                                                        <div class="d-flex justify-content-center">
                                                            <x-ui.file-card :url="$field['new']['url']" :name="$field['new']['display']" :isImage="true" />
                                                        </div>
                                                    @else
                                                        <div class="d-flex justify-content-center">
                                                            <x-ui.file-card :url="$field['new']['url']" :name="$field['new']['display']" />
                                                        </div>
                                                    @endif
                                                @elseif ($field['new']['type'] === 'bool')
                                                    <span class="badge {{ $field['new']['badge'] }}">{{ $field['new']['display'] }}</span>
                                                @elseif ($field['new']['type'] === 'array')
                                                    <pre class="mb-0 text-start bg-light p-2 rounded small font-monospace" style="white-space: pre-wrap; font-size: 0.8rem; direction: ltr;">{{ $field['new']['display'] }}</pre>
                                                @elseif ($field['new']['type'] === 'null')
                                                    <span class="text-muted">-</span>
                                                @else
                                                    <span class="text-break">{{ $field['new']['display'] }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info mb-0 d-flex align-items-center">
                            <i class="ti ti-info-circle me-2 fs-5"></i>
                            <div>لا توجد تغييرات حقول مسجلة في هذا النشاط.</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- البيانات الخام (JSON) --}}
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-code text-secondary me-2"></i>البيانات الخام (JSON)
                        </h5>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#rawJsonCollapse" aria-expanded="false" aria-controls="rawJsonCollapse">
                            <i class="ti ti-eye me-1"></i> إظهار / إخفاء
                        </button>
                    </div>
                </div>
                <div class="collapse" id="rawJsonCollapse">
                    <div class="card-body pt-0">
                        <pre class="bg-dark text-light p-3 rounded text-start mb-0 font-monospace" style="white-space: pre-wrap; font-size: 0.8rem; direction: ltr; max-height: 400px; overflow-y: auto;">{{ $changes['raw_json'] }}</pre>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
