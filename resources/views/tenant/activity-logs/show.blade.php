@extends('tenant.layouts.layoutMaster')

@section('title', 'تفاصيل النشاط')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/animate-css/animate.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'])
@endsection

@section('content')
<div class="row">
    <div class="col-12 col-md-3 col-xl-3">
        <div>
            <ul class="nav nav-tabs flex-column border-bottom-0">
                <!-- تبويب تفاصيل النشاط -->
                <li class="card rounded-0 mb-3 nav-item shadow-none">
                    <a class="py-3 nav-link fw-bold active m-0" data-bs-toggle="tab" href="#activityDetails">
                        <i class="ti ti-info-circle ti-sm me-1"></i><span>تفاصيل النشاط</span>
                    </a>
                </li>
                <!-- تبويب البيانات التفصيلية -->
                @if($activity->properties && count($activity->properties) > 0)
                <li class="card rounded-0 mb-3 nav-item shadow-none">
                    <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#dataDetails">
                        <i class="ti ti-database ti-sm me-1"></i><span>البيانات التفصيلية</span>
                    </a>
                </li>
                @endif
            </ul>
        </div>
    </div>
    {{-- المحتوى --}}
    <div class="col-12 col-md-9 col-xl-9 order-1 order-md-0 mb-5">
        <div class="tab-content p-0">
            {{-- تفاصيل النشاط --}}
            <div class="tab-pane fade show active" id="activityDetails">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0">تفاصيل النشاط</h5>
                    </div>
                    <div class="card-body">
                        {{-- بطاقة المستخدم --}}
                        <div class="card border mb-4">
                            <div class="card-body d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    @php
                                    $avatar = $activity->causer && method_exists($activity->causer,
                                    'getProfilePhotoUrlAttribute')
                                    ? $activity->causer->profile_photo_url
                                    : asset('static/brand/logos/logo.svg');
                                    @endphp
                                    <img src="{{ $avatar }}" class="rounded-circle" width="70" height="70">
                                </div>
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-0">{{ $activityData['user_name'] }}</h5>
                                    <small>{{ $activityData['formatted_date'] }}</small>
                                </div>
                                <div class="flex-shrink-0">
                                    <span class="badge bg-primary fs-6">{{ $activityData['log_name_arabic'] }}</span>
                                </div>
                            </div>
                        </div>

                        {{-- بيانات النشاط في بطاقات --}}
                        <div class="row mb-4">
                            <!-- نوع الحدث -->
                            <div class="col-md-4 mb-3">
                                <div class="card border h-100">
                                    <div class="card-body">
                                        <div class="d-flex align-items-start">
                                            <i class="ti ti-activity me-2"></i>
                                            <div>
                                                <h6 class="mb-2">نوع الحدث</h6>
                                                <span class="fw-bold">{{ $activityData['event_arabic'] }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- الموضوع -->
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

                            <!-- معرف النشاط -->
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

                        {{-- وصف النشاط --}}
                        <div class="mb-4">
                            <h6><i class="ti ti-message-circle me-1"></i>وصف النشاط:</h6>
                            <div class="border rounded p-3">
                                <pre class="mb-0"
                                    style="white-space: pre-wrap; font-family: inherit;">{{ $activity->description }}</pre>
                            </div>
                        </div>

                        {{-- معلومات الاتصال (الجهاز و IP) --}}
                        @if($activityData['connection_info'])
                        <div>
                            <h6><i class="ti ti-device-desktop me-1"></i>معلومات الاتصال:</h6>
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
                                @if($activityData['connection_info']['reason'])
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
            @if($activity->properties && count($activity->properties) > 0)
            <div class="tab-pane fade" id="dataDetails">
                <div class="card">
                    <div class="card-header">
                        <h5 class="fw-bold mb-0">البيانات التفصيلية</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($activity->properties['old']) || isset($activity->properties['attributes']))
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th width="200">الحقل</th>
                                        @if(isset($activity->properties['old']))
                                        <th>القيمة السابقة</th>
                                        @endif
                                        <th>القيمة الجديدة</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                    $oldData = $activity->properties['old'] ?? [];
                                    $newData = $activity->properties['attributes'] ?? $activity->properties;

                                    // تحويل Collection إلى array إذا لزم الأمر
                                    if ($oldData instanceof \Illuminate\Support\Collection) {
                                    $oldData = $oldData->toArray();
                                    }
                                    if ($newData instanceof \Illuminate\Support\Collection) {
                                    $newData = $newData->toArray();
                                    }

                                    $allFields = array_unique(array_merge(array_keys($oldData), array_keys($newData)));
                                    @endphp
                                    @foreach($allFields as $field)
                                    <tr>
                                        <td class="fw-medium">{{ $activityData['field_translations'][$field] ?? $field
                                            }}</td>
                                        @if(isset($activity->properties['old']))
                                        <td>
                                            @if(isset($oldData[$field]))
                                            @if(is_array($oldData[$field]))
                                            <code>{{ json_encode($oldData[$field], JSON_UNESCAPED_UNICODE) }}</code>
                                            @else
                                            {{ $oldData[$field] ?? 'فارغ' }}
                                            @endif
                                            @else
                                            <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        @endif
                                        <td>
                                            @if(isset($newData[$field]))
                                            @if(is_array($newData[$field]))
                                            <code>{{ json_encode($newData[$field], JSON_UNESCAPED_UNICODE) }}</code>
                                            @else
                                            {{ $newData[$field] ?? 'فارغ' }}
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

<script>
    $(document).ready(function() {
            if (window.location.hash) {
                $('.nav-tabs a[href="' + window.location.hash + '"]').tab('show');
            }
            $('.nav-tabs a').on('shown.bs.tab', function(e) {
                history.pushState(null, null, e.target.hash);
            });
        });
</script>
@endsection
