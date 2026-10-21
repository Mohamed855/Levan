@extends('layouts.dashboard')

@section('title', 'تفاصيل العملية #' . $log->id)
@section('dashboard-scope', 'tenant')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item"><a href="{{ route('tenant.activity-log.index') }}">سجل العمليات</a></li>
                <li class="breadcrumb-item active">عملية #{{ $log->id }}</li>
            </ol>
        </nav>
        <h4 class="mb-0 fw-bold">تفاصيل العملية <span class="text-primary font-monospace">#{{ $log->id }}</span></h4>
    </div>
    <div>
        <a href="{{ route('tenant.activity-log.index') }}" class="btn btn-label-secondary">
            <i class="ti ti-arrow-right me-1"></i> العودة للسجل
        </a>
    </div>
</div>

<div class="row">
    {{-- العمود الأيمن: التغييرات والتفاصيل --}}
    <div class="col-xl-8 col-lg-7 col-md-7 mb-4">
        <div class="card mb-4">
            <div class="card-header border-bottom py-3">
                <h5 class="card-title mb-0 fw-bold">
                    <i class="ti ti-adjustments-horizontal me-2 text-primary"></i> التغييرات والتفاصيل المسجلة
                </h5>
            </div>
            <div class="card-body pt-4">
                <div class="mb-4">
                    <h6 class="fw-bold text-muted mb-2">الوصف:</h6>
                    <p class="fs-6 p-3 bg-body-tertiary rounded">{{ $log->description }}</p>
                </div>

                @if($log->attribute_changes && count($log->attribute_changes) > 0)
                    <h6 class="fw-bold text-muted mb-3">الحقول المعدلة (قبل وبعد):</h6>
                    <div class="table-responsive border rounded">
                        <table class="table table-bordered mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>الحقل</th>
                                    <th>القيمة السابقة</th>
                                    <th>القيمة الجديدة</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($log->attribute_changes as $field => $changes)
                                    <tr>
                                        <td class="fw-bold font-monospace">{{ $field }}</td>
                                        <td class="text-danger bg-danger-subtle">{{ is_array($changes['old'] ?? null) ? json_encode($changes['old'], JSON_UNESCAPED_UNICODE) : ($changes['old'] ?? '—') }}</td>
                                        <td class="text-success bg-success-subtle">{{ is_array($changes['new'] ?? null) ? json_encode($changes['new'], JSON_UNESCAPED_UNICODE) : ($changes['new'] ?? '—') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @elseif($log->properties && count($log->properties) > 0)
                    <h6 class="fw-bold text-muted mb-3">الخصائص الإضافية:</h6>
                    <pre class="bg-body-tertiary p-3 rounded font-monospace fs-6"><code>{{ json_encode($log->properties, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                @else
                    <div class="alert alert-light border text-muted">
                        <i class="ti ti-info-circle me-1"></i> لا توجد بيانات تفصيلية إضافية مسجلة لهذه العملية.
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- العمود الأيسر: معلومات المنفذ والحدث --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3 fw-bold">بيانات الحدث</h5>
                <div class="text-center py-3">
                    <div class="avatar avatar-xl mx-auto mb-3 bg-label-secondary rounded-circle d-flex align-items-center justify-content-center">
                        <i class="ti ti-history fs-1 text-primary"></i>
                    </div>
                    {!! $log->getEventBadge() !!}
                </div>

                <div class="border-top pt-3 mt-2">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">المستخدم المنفذ:</span>
                        <span class="fw-semibold">{{ $log->causer?->name ?? 'النظام التلقائي' }}</span>
                    </div>
                    @if($log->causer?->email)
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">البريد الإلكتروني:</span>
                        <span>{{ $log->causer->email }}</span>
                    </div>
                    @endif
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">الكيان المتأثر:</span>
                        <span class="badge bg-label-info">{{ class_basename($log->subject_type ?? 'عام') }} #{{ $log->subject_id }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">تاريخ التسجيل:</span>
                        <span>{{ $log->created_at?->format('Y-m-d H:i:s') }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">منذ:</span>
                        <span>{{ $log->created_at?->diffForHumans() }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
