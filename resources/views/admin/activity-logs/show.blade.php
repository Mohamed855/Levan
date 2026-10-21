@extends('layouts.dashboard')

@section('title', 'تفاصيل سجل النشاط #' . $activityLog->id)
@section('dashboard-scope', 'admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-1">
                <i class="ti ti-activity text-primary me-2"></i>
                تفاصيل سجل النشاط #{{ $activityLog->id }}
            </h4>
            <p class="text-muted mb-0">توثيق الحدث وبيانات الاتصال والتغييرات التي تمت.</p>
        </div>
        <div>
            <a href="{{ route('admin.activity-logs.index') }}" class="btn btn-label-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة للقائمة
            </a>
        </div>
    </div>

    <div class="row g-4">
        {{-- كرت ملخص الحدث --}}
        <div class="col-lg-4">
            <div class="card h-100 shadow-sm">
                <div class="card-header border-bottom">
                    <h5 class="card-title mb-0">بيانات الحدث</h5>
                </div>
                <div class="card-body pt-4">
                    <div class="d-flex flex-column gap-3">
                        <div>
                            <span class="text-muted d-block small mb-1">نوع السجل:</span>
                            <span class="badge bg-label-primary fs-6">{{ $activityData['log_name_arabic'] }}</span>
                        </div>

                        <div>
                            <span class="text-muted d-block small mb-1">نوع العملية:</span>
                            <span class="badge bg-label-info fs-6">{{ $activityData['event_arabic'] }}</span>
                        </div>

                        <div>
                            <span class="text-muted d-block small mb-1">وصف العملية:</span>
                            <span class="fw-bold text-heading">{{ $activityLog->description }}</span>
                        </div>

                        <div>
                            <span class="text-muted d-block small mb-1">المستخدم الفاعل:</span>
                            @if ($activityLog->causer)
                                <div class="d-flex align-items-center gap-2 mt-1">
                                    <span class="avatar avatar-sm bg-label-secondary rounded-circle">
                                        <i class="ti ti-user"></i>
                                    </span>
                                    <div>
                                        <span class="fw-bold d-block">{{ $activityLog->causer->name ?? '-' }}</span>
                                        <small class="text-muted">{{ $activityLog->causer->email ?? '' }}</small>
                                    </div>
                                </div>
                            @else
                                <span class="badge bg-label-secondary">النظام الآلي</span>
                            @endif
                        </div>

                        <div>
                            <span class="text-muted d-block small mb-1">العنصر المتأثر:</span>
                            <span class="font-monospace text-heading">{{ $activityData['subject_display'] }}</span>
                        </div>

                        <div>
                            <span class="text-muted d-block small mb-1">تاريخ ووقت العملية:</span>
                            <span class="font-monospace">{{ $activityLog->created_at?->format('Y-m-d H:i:s') ?? '-' }}</span>
                            <small class="text-muted d-block">{{ $activityLog->created_at?->locale('ar')->diffForHumans() }}</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- كرت بيانات الاتصال والخصائص --}}
        <div class="col-lg-8">
            <div class="d-flex flex-column gap-4">
                {{-- معلومات الاتصال والجهاز --}}
                @if ($activityData['connection_info'])
                    <div class="card shadow-sm">
                        <div class="card-header border-bottom">
                            <h5 class="card-title mb-0">
                                <i class="ti ti-device-laptop text-primary me-2"></i>
                                معلومات الاتصال والجهاز
                            </h5>
                        </div>
                        <div class="card-body pt-4">
                            <div class="row g-3">
                                <div class="col-sm-6 col-md-4">
                                    <span class="text-muted d-block small mb-1">عنوان IP:</span>
                                    <span class="font-monospace fw-bold">{{ $activityData['connection_info']['ip'] }}</span>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <span class="text-muted d-block small mb-1">نوع الجهاز:</span>
                                    <span>{{ $activityData['connection_info']['device'] }}</span>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <span class="text-muted d-block small mb-1">نظام التشغيل:</span>
                                    <span>{{ $activityData['connection_info']['os'] }}</span>
                                </div>
                                <div class="col-sm-6 col-md-4">
                                    <span class="text-muted d-block small mb-1">المتصفح:</span>
                                    <span>{{ $activityData['connection_info']['browser'] }}</span>
                                </div>
                                @if (! empty($activityData['connection_info']['identifier']))
                                    <div class="col-sm-6 col-md-8">
                                        <span class="text-muted d-block small mb-1">المعرف المستخدم:</span>
                                        <span class="font-monospace">{{ $activityData['connection_info']['identifier'] }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- الخصائص والتغييرات التفصيلية --}}
                <div class="card shadow-sm">
                    <div class="card-header border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-code text-primary me-2"></i>
                            تفاصيل البيانات والتغييرات (Properties)
                        </h5>
                    </div>
                    <div class="card-body pt-4">
                        @if ($activityLog->properties && count($activityLog->properties) > 0)
                            <pre class="bg-dark text-white p-3 rounded font-monospace" dir="ltr" style="max-height: 400px; overflow-y: auto;"><code>{{ json_encode($activityLog->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</code></pre>
                        @else
                            <div class="text-center py-4 text-muted">
                                <i class="ti ti-info-circle fs-3 d-block mb-2"></i>
                                لا توجد خصائص إضافية مسجلة لهذه العملية.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
