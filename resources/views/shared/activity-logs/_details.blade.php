<div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap">
        <a href="{{ $backUrl }}" class="btn btn-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة
        </a>
    </div>
</div>

<div class="row">
    {{-- بطاقة هوية الفاعل وبيانات السياق (العمود الأيمن) --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mx-auto mb-3">
                    @php
                        $causer = $activity->causer;
                        $avatarUrl = null;
                        if ($causer) {
                            $rawImage = $causer->image ?: $causer->profile_picture ?? null;
                            if ($rawImage) {
                                if (\Illuminate\Support\Str::startsWith($rawImage, ['http://', 'https://'])) {
                                    $avatarUrl = $rawImage;
                                } elseif (function_exists('tenant') && tenant()) {
                                    $normalized = ltrim($rawImage, '/');
                                    if (
                                        \Illuminate\Support\Facades\Storage::disk('tenant_public')->exists($normalized) ||
                                        \Illuminate\Support\Facades\Storage::disk('public')->exists($normalized)
                                    ) {
                                        $avatarUrl = tasset($rawImage);
                                    }
                                } else {
                                    $cleanPath = ltrim(\Illuminate\Support\Str::after($rawImage, 'storage/'), '/');
                                    if (\Illuminate\Support\Facades\Storage::disk('public')->exists($cleanPath)) {
                                        $avatarUrl = asset('storage/' . $cleanPath);
                                    }
                                }
                            }
                        }
                    @endphp
                    <div class="avatar avatar-xl mx-auto shadow-sm rounded-circle" style="width: 100px; height: 100px;">
                        @if (!$causer)
                            <span
                                class="avatar-initial rounded-circle bg-label-info w-100 h-100 d-flex align-items-center justify-content-center"
                                title="النظام">
                                <i class="ti ti-settings fs-1"></i>
                            </span>
                        @elseif ($avatarUrl)
                            <img src="{{ $avatarUrl }}" alt="{{ $activityData['user_name'] }}"
                                class="rounded-circle object-fit-cover w-100 h-100 border">
                        @else
                            <span
                                class="avatar-initial rounded-circle bg-label-primary w-100 h-100 d-flex align-items-center justify-content-center">
                                <i class="ti ti-user fs-1"></i>
                            </span>
                        @endif
                    </div>
                </div>
                <h4 class="mb-1 fw-bold text-primary">{{ $activityData['user_name'] }}</h4>
                <p class="mb-2 text-muted fw-medium">{{ $activityData['log_name_arabic'] }}</p>
                <span class="badge {{ $activityData['event_badge_class'] ?? 'bg-label-primary' }} mb-3 fs-6">
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
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-tag text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">نوع النشاط:</span>
                            <span class="badge bg-label-primary">{{ $activityData['log_name_arabic'] }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-file-info text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الموضوع:</span>
                            <span class="text-break">{{ $activityData['subject_display'] }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-box text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">نوع الكيان:</span>
                            <span class="text-break">{{ class_basename($activity->subject_type ?? 'غير محدد') }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-hash text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">معرف الكيان:</span>
                            <span>#{{ $activity->subject_id ?? '-' }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-calendar text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">التاريخ:</span>
                            <span>{{ $activityData['formatted_date'] }}</span>
                        </li>
                        <li class="{{ $activity->batch_uuid ? 'mb-3' : 'mb-2' }} d-flex align-items-center">
                            <i class="ti ti-clock text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الوقت النسبي:</span>
                            <span
                                class="badge bg-label-info">{{ $activity->created_at ? $activity->created_at->locale('ar')->diffForHumans() : '-' }}</span>
                        </li>
                        @if ($activity->batch_uuid)
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-layers-intersect text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">معرف الدفعة:</span>
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
                <div class="card-header pb-2 mb-3">
                    <h6 class="card-title mb-0">
                        <i class="ti ti-device-desktop text-primary me-2"></i>معلومات الاتصال
                    </h6>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-world text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">عنوان IP:</span>
                            <code>{{ $activityData['connection_info']['ip'] }}</code>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-device-laptop text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">نوع الجهاز:</span>
                            <span>{{ $activityData['connection_info']['device'] }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-brand-windows text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">نظام التشغيل:</span>
                            <span>{{ $activityData['connection_info']['os'] }}</span>
                        </li>
                        <li
                            class="{{ !empty($activityData['connection_info']['reason']) ? 'mb-3' : 'mb-2' }} d-flex align-items-center">
                            <i class="ti ti-browser text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">المتصفح:</span>
                            <span>{{ $activityData['connection_info']['browser'] }}</span>
                        </li>
                        @if (!empty($activityData['connection_info']['reason']))
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-alert-triangle text-danger me-2 fs-5"></i>
                                <span class="fw-bold text-danger me-2">السبب:</span>
                                <span
                                    class="text-danger fw-semibold">{{ $activityData['connection_info']['reason'] }}</span>
                            </li>
                        @endif
                    </ul>

                    @if (!empty($activityData['connection_info']['user_agent']))
                        <div class="mt-3 pt-2 border-top">
                            <small class="text-muted d-block mb-1">User Agent:</small>
                            <div class="bg-light p-2 rounded text-break small font-monospace text-muted">
                                {{ $activityData['connection_info']['user_agent'] }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- وصف النشاط والتغييرات (العمود الأيسر) --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        {{-- وصف النشاط --}}
        <div class="card mb-4">
            <div class="card-header pb-2 mb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-file-description text-primary me-2"></i>وصف النشاط
                </h5>
            </div>
            <div class="card-body">
                <div class="alert alert-primary mb-0">
                    <p class="mb-0 text-break">
                        {{ $activity->description ?: 'لا يوجد وصف مخصص لهذا النشاط.' }}</p>
                </div>
            </div>
        </div>

        {{-- تفاصيل التغييرات والبيانات --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-list-details text-primary me-2"></i>تفاصيل التغييرات والبيانات
                </h5>
                @if ($changes['has_changes'])
                    <span class="badge bg-label-primary">{{ count($changes['fields']) }} حقل</span>
                @endif
            </div>
            @if ($changes['has_changes'])
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th style="width: 25%;">الحقل</th>
                                @if ($changes['has_old'])
                                    <th style="width: 37.5%;">القيمة السابقة</th>
                                @endif
                                <th style="width: {{ $changes['has_old'] ? '37.5%' : '75%' }};">
                                    {{ $changes['has_old'] ? 'القيمة الجديدة' : 'القيمة' }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($changes['fields'] as $field)
                                <tr>
                                    <td>
                                        <div class="fw-semibold">{{ $field['label'] }}</div>
                                        <small class="text-muted font-monospace">{{ $field['key'] }}</small>
                                    </td>

                                    @if ($changes['has_old'])
                                        <td>
                                            @include('shared.activity-logs._change-value', [
                                                'hasValue' => $field['has_old'],
                                                'value' => $field['old'],
                                            ])
                                        </td>
                                    @endif

                                    <td>
                                        @include('shared.activity-logs._change-value', [
                                            'hasValue' => $field['has_new'],
                                            'value' => $field['new'],
                                        ])
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-body">
                    <div class="text-center py-4 text-muted">
                        <i class="ti ti-info-circle fs-2 d-block mb-2"></i>
                        لا توجد تغييرات حقول مسجلة في هذا النشاط.
                    </div>
                </div>
            @endif
        </div>

        {{-- البيانات الخام (JSON) --}}
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center mb-3">
                <h5 class="card-title mb-0">
                    <i class="ti ti-code text-primary me-2"></i>البيانات الخام (JSON)
                </h5>
                <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="collapse"
                    data-bs-target="#rawJsonCollapse" aria-expanded="false" aria-controls="rawJsonCollapse">
                    <i class="ti ti-eye me-1"></i> إظهار / إخفاء
                </button>
            </div>
            <div class="collapse" id="rawJsonCollapse">
                <div class="card-body pt-0">
                    <pre class="bg-dark text-light p-3 rounded text-start mb-0 font-monospace"
                        style="white-space: pre-wrap; font-size: 0.8rem; direction: ltr; max-height: 400px; overflow-y: auto;">{{ $changes['raw_json'] }}</pre>
                </div>
            </div>
        </div>
    </div>
</div>
