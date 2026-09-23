@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل النشاط')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/@form-validation/form-validation.scss', 'resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/css/tenants/tab.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/@form-validation/popular.js', 'resources/assets/vendor/libs/@form-validation/bootstrap5.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/js/tenants/settings/tab.js', 'resources/assets/js/tenants/settings/system-settings.js'])
@endsection

@section('content')
    <div class="row">
        <div class="col-12 col-md-3 col-xl-3">
            <div>
                <ul class="nav nav-tabs flex-column border-bottom-0">
                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold active m-0" data-bs-toggle="tab" href="#activityDetails">
                            <i class="ti ti-activity ti-sm me-1"></i><span>تفاصيل النشاط</span>
                        </a>
                    </li>

                    @if ($activity->properties && count($activity->properties) > 0)
                        <li class="card rounded-0 mb-3 nav-item shadow-none">
                            <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#dataDetails">
                                <i class="ti ti-list-details ti-sm me-1"></i><span>البيانات التفصيلية</span>
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        </div>

        <div class="col-12 col-md-9 col-xl-9 order-1 order-md-0 mb-5">
            <div class="tab-content p-0">
                {{-- تفاصيل النشاط --}}
                <div class="tab-pane fade show active" id="activityDetails">
                    <div class="card">
                        <div class="card-header py-4">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-activity text-primary me-2"></i>
                                تفاصيل النشاط
                            </h6>
                        </div>

                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            {{-- بطاقة المستخدم --}}
                            <div class="card border mb-4">
                                <div class="card-body d-flex align-items-center">
                                    <div class="flex-shrink-0 me-3">
                                        @php
                                            $avatar =
                                                $activity->causer &&
                                                method_exists($activity->causer, 'getProfilePhotoUrlAttribute')
                                                    ? $activity->causer->profile_photo_url
                                                    : asset('assets/img/branding/logo.svg');
                                        @endphp
                                        <img src="{{ $avatar }}" class="rounded-circle" width="70" height="70">
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="fw-bold mb-0">{{ $activity->causer->name ?? 'تلقائيا من النظام' }}</h5>
                                        <small>{{ $activity->created_at->locale('ar')->translatedFormat('d F Y - h:i A') }}</small>
                                    </div>
                                    <div class="flex-shrink-0">
                                        <span class="badge bg-primary fs-6">{{ $activity->log_name }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- بيانات النشاط في بطاقات --}}
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-activity me-2"></i>
                                                <div>
                                                    <h6 class="mb-2">نوع الحدث</h6>
                                                    <span class="fw-bold">{{ $arabicEvent }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-target me-2"></i>
                                                <div>
                                                    <h6 class="mb-2">الموضوع</h6>
                                                    <span class="fw-bold">{{ $activityData['subject_display'] }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-hash me-2"></i>
                                                <div>
                                                    <h6 class="mb-2">معرف النشاط</h6>
                                                    <span class="fw-bold">#{{ $activity->id }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <h6>وصف النشاط</h6>
                                <div class="border rounded p-3">
                                    <pre class="mb-0" style="white-space: pre-wrap; font-family: inherit;">{{ $activity->description }}</pre>
                                </div>
                            </div>

                            {{-- معلومات الاتصال (الجهاز و IP) --}}
                            @if ($activityData['connection_info'])
                                <div>
                                    <h6><i class="ti ti-device-desktop me-1"></i>معلومات الاتصال</h6>
                                    <div class="row">
                                        <div class="col-md-3 col-6 mb-3">
                                            <div class="card border h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-world me-2"></i>
                                                        <div>
                                                            <h6 class="mb-2">عنوان IP</h6>
                                                            <span class="fw-bold">{{ $activityData['connection_info']['ip'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-3">
                                            <div class="card border h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-device-laptop me-2"></i>
                                                        <div>
                                                            <h6 class="mb-2">نوع الجهاز</h6>
                                                            <span class="fw-bold">{{ $activityData['connection_info']['device'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-3">
                                            <div class="card border h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-brand-windows me-2"></i>
                                                        <div>
                                                            <h6 class="mb-2">نظام التشغيل</h6>
                                                            <span class="fw-bold">{{ $activityData['connection_info']['os'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-3 col-6 mb-3">
                                            <div class="card border h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-browser me-2"></i>
                                                        <div>
                                                            <h6 class="mb-2">المتصفح</h6>
                                                            <span class="fw-bold">{{ $activityData['connection_info']['browser'] }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        @if ($activityData['connection_info']['reason'])
                                            <div class="col-md-6 mb-3">
                                                <div class="card border h-100">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-start">
                                                            <i class="ti ti-alert-triangle me-2"></i>
                                                            <div>
                                                                <h6 class="mb-2">سبب الفشل</h6>
                                                                <span class="fw-bold">{{ $activityData['connection_info']['reason'] }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif
                                        <div class="col-md-6 mb-3">
                                            <div class="card border h-100">
                                                <div class="card-body">
                                                    <div class="d-flex align-items-start">
                                                        <i class="ti ti-file-info me-2"></i>
                                                        <div class="w-100">
                                                            <h6 class="mb-2">User Agent</h6>
                                                            <span class="text-muted small text-break">{{ $activityData['connection_info']['user_agent'] ?? 'غير معروف' }}</span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- البيانات التفصيلية --}}
                @if ($activity->properties && count($activity->properties) > 0)
                    <div class="tab-pane fade" id="dataDetails">
                        <div class="card">
                            <div class="card-header py-4">
                                <h6 class="card-title mb-0">
                                    <i class="ti ti-list-details text-primary me-2"></i>
                                    البيانات التفصيلية
                                </h6>
                            </div>

                            <div class="border-1 border-light border-dashed mb-2"></div>
                            <div class="card-body">
                                @if (isset($activity->properties['old']) || isset($activity->properties['attributes']))
                                    <div class="table-responsive">
                                        <table class="table table-striped table-bordered">
                                            <thead>
                                                <tr>
                                                    <th width="200">الحقل</th>
                                                    @if (isset($activity->properties['old']))
                                                        <th>القيمة السابقة</th>
                                                    @endif
                                                    <th>القيمة الجديدة</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @php
                                                    $oldData = $activity->properties['old'] ?? [];
                                                    $newData =
                                                        $activity->properties['attributes'] ?? $activity->properties;

                                                    if ($oldData instanceof \Illuminate\Support\Collection) {
                                                        $oldData = $oldData->toArray();
                                                    }
                                                    if ($newData instanceof \Illuminate\Support\Collection) {
                                                        $newData = $newData->toArray();
                                                    }

                                                    $allFields = array_unique(
                                                        array_merge(array_keys($oldData), array_keys($newData)),
                                                    );
                                                    $modelClass = $activity->subject_type;

                                                    // حذف old من القائمة
                                                    $allFields = array_filter(
                                                        $allFields,
                                                        fn($field) => !in_array($field, ['old', 'attributes']),
                                                    );
                                                @endphp

                                                @foreach ($allFields as $field)
                                                    <tr>
                                                        <td class="fw-medium">
                                                            {{ $modelClass::getFieldLabel($field) }}
                                                        </td>

                                                        @if (isset($activity->properties['old']))
                                                            <td>
                                                                @if (isset($oldData[$field]))
                                                                    @php $oldValue = $oldData[$field]; @endphp

                                                                    @if (is_array($oldValue))
                                                                        <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.875rem;">{{ json_encode($oldValue, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                                                    @elseif (is_bool($oldValue))
                                                                        <span
                                                                            class="badge bg-{{ $oldValue ? 'success' : 'danger' }}">
                                                                            {{ $modelClass::getValueLabel($field, $oldValue) }}
                                                                        </span>
                                                                    @else
                                                                        {{ $modelClass::getValueLabel($field, $oldValue) }}
                                                                    @endif
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                        @endif

                                                        <td>
                                                            @if (isset($newData[$field]))
                                                                @php $newValue = $newData[$field]; @endphp

                                                                @if (is_array($newValue))
                                                                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.875rem;">{{ json_encode($newValue, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}</pre>
                                                                @elseif (is_bool($newValue))
                                                                    <span
                                                                        class="badge bg-{{ $newValue ? 'success' : 'danger' }}">
                                                                        {{ $modelClass::getValueLabel($field, $newValue) }}
                                                                    </span>
                                                                @else
                                                                    {{ $modelClass::getValueLabel($field, $newValue) }}
                                                                @endif
                                                            @else
                                                                <span class="text-muted">-</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @else
                                    <div class="alert alert-info mb-0">
                                        <i class="ti ti-info-circle me-2"></i>لا توجد بيانات تفصيلية متاحة.
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection
