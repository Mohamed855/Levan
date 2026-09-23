@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل التحصيل')

{{-- CSS Styles --}}
@section('vendor-style')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables.bootstrap5.scss',
'resources/assets/vendor/libs/shepherd/shepherd.scss',
'resources/assets/vendor/libs/datatables-responsive-bs5/responsive.bootstrap5.scss',
'resources/assets/vendor/libs/datatables-buttons-bs5/buttons.bootstrap5.scss',
'resources/assets/vendor/libs/select2/select2.scss',
'resources/assets/vendor/libs/@form-validation/form-validation.scss',
'resources/assets/vendor/libs/animate-css/animate.scss',
'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss',
'resources/assets/vendor/libs/flatpickr/flatpickr.scss'
])
@endsection

{{-- Scripts --}}
@section('vendor-script')
@vite([
'resources/assets/vendor/libs/datatables-bs5/datatables-bootstrap5.js'
])
@endsection

@section('content')

{{-- 1. بطاقة الملخص الرئيسية --}}
<div class="card mb-4">
    <div class="card-body">
        <div class="row text-center gy-3">
            <div class="col-md-3">
                <h6 class="text-muted fw-normal">حالة التحصيل</h6>
                <div class="border-top border-light border-dashed my-2"></div>
                <p class="fs-5 mb-0">
                    <span class="badge fs-6 {{ $collection->status_class }}">
                        {{ $collection->status_label }}
                    </span>
                </p>
            </div>
            <div class="col-md-3">
                <h6 class="text-muted fw-normal">مبلغ التحصيل</h6>
                <div class="border-top border-light border-dashed my-2"></div>
                <p class="fs-5 mb-0 fw-bold text-primary">{!! $collection->formatted_collected_amount !!}</p>
            </div>
            <div class="col-md-3">
                <h6 class="text-muted fw-normal">رقم السند</h6>
                <div class="border-top border-light border-dashed my-2"></div>
                <p class="fs-5 mb-0">{{ $collection->receipt_number }}</p>
            </div>
            <div class="col-md-3">
                <h6 class="text-muted fw-normal">تاريخ التحصيل</h6>
                <div class="border-top border-light border-dashed my-2"></div>
                <p class="fs-5 mb-0">{{ $collection->formatted_collection_date }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- العمود الأيمن: معلومات التحصيل والعميل -->
    <div class="col-12 col-lg-7">
        <!-- بطاقة معلومات التحصيل -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">معلومات التحصيل</h5>
            </div>
            <div class="border-1 border-light border-dashed"></div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped table-bordered">
                        <tbody class="table-border-bottom-0">
                            <tr>
                                <td class="fw-medium">نوع التحصيل</td>
                                <td><span
                                        class="badge {{ $collection->collection_type === 'cash' ? 'bg-label-success' : 'bg-label-info' }}">{{
                                        $collection->collection_type_label }}</span></td>
                            </tr>
                            <tr>
                                <td class="fw-medium">نوع السداد</td>
                                <td><span
                                        class="badge {{ $collection->payment_type === 'settlement' ? 'bg-label-primary' : 'bg-label-secondary' }}">{{
                                        $collection->payment_type_label }}</span></td>
                            </tr>
                            <tr>
                                <td class="fw-medium">طريقة التحصيل</td>
                                <td>{{ $collection->paymentMethod->name }}</td>
                            </tr>
                            @if ($collection->closing_code)
                            <tr>
                                <td class="fw-medium">رمز الإقفال</td>
                                <td class="fw-bold">{{ $collection->closing_code }}</td>
                            </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- بطاقة معلومات العميل -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">بيانات العميل</h5>
            </div>
            <div class="border-1 border-light border-dashed"></div>
            <div class="card-body">
                <div class="table-responsive text-nowrap">
                    <table class="table table-striped table-bordered">
                        <tbody class="table-border-bottom-0">
                            <tr>
                                <td class="fw-medium">اسم العميل</td>
                                <td>{{ $collection->customer->name }}</td>
                            </tr>
                            <tr>
                                <td class="fw-medium">رقم الهاتف</td>
                                <td>{{ $collection->customer->phone }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- العمود الأيسر: سجل النشاطات (تصميم Timeline الاحترافي) -->
    <div class="col-12 col-lg-5">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">سجل النشاطات</h5>
            </div>
            <div class="border-1 border-light border-dashed"></div>
            <div class="card-body">
                <ul class="list-unstyled" style="position: relative;">
                    <!-- الخط الزمني العمودي -->
                    <div
                        style="position: absolute; right: 20px; top: 0; bottom: 0; width: 2px; background-color: #e9ecef; z-index: 1;">
                    </div>

                    <!-- 1. حدث إنشاء التحصيل -->
                    <li class="d-flex mb-4" style="position: relative; z-index: 2;">
                        <!-- الأيقونة -->
                        <div
                            class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center bg-label-primary me-3 flex-shrink-0">
                            <i class="ti ti-plus text-primary ti-sm"></i>
                        </div>
                        <!-- المحتوى المقسم -->
                        <div class="d-flex justify-content-between align-items-start w-100">
                            <div>
                                <h6 class="mb-0">إنشاء التحصيل</h6>
                                <small class="text-muted">
                                    {{ $collection->addedBy ? $collection->addedBy->name : 'غير محدد' }}
                                </small>
                            </div>
                            <small class="text-muted text-nowrap">{{
                                $collection->created_at->locale('ar')->translatedFormat('d M, H:i A') }}</small>
                        </div>
                    </li>

                    <!-- 2. حدث المراجعة (اعتماد أو رفض) -->
                    @if ($collection->status === 'approved' && $collection->approvedBy)
                    <li class="d-flex mb-4" style="position: relative; z-index: 2;">
                        <!-- الأيقونة -->
                        <div
                            class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center bg-label-success me-3 flex-shrink-0">
                            <i class="ti ti-check text-success ti-sm"></i>
                        </div>
                        <!-- المحتوى المقسم -->
                        <div class="d-flex justify-content-between align-items-start w-100">
                            <div>
                                <h6 class="mb-0">تم اعتماد التحصيل</h6>
                                <small class="text-muted">
                                    {{ $collection->approvedBy->name }}
                                </small>
                            </div>
                            <small class="text-muted text-nowrap">{{
                                $collection->approved_at->locale('ar')->translatedFormat('d M, H:i A') }}</small>
                        </div>
                    </li>
                    @elseif ($collection->status === 'rejected' && $collection->approvedBy)
                    <li class="d-flex" style="position: relative; z-index: 2;">
                        <!-- الأيقونة -->
                        <div
                            class="avatar avatar-md rounded-circle d-flex align-items-center justify-content-center bg-label-danger me-3 flex-shrink-0">
                            <i class="ti ti-x text-danger ti-sm"></i>
                        </div>
                        <!-- المحتوى المقسم -->
                        <div class="d-flex justify-content-between align-items-start w-100">
                            <div>
                                <h6 class="mb-0">تم رفض التحصيل</h6>
                                <small class="text-muted">
                                    {{ $collection->approvedBy->name }}
                                </small>
                            </div>
                            <small class="text-muted text-nowrap">{{
                                $collection->approved_at->locale('ar')->translatedFormat('d M, H:i A') }}</small>
                        </div>
                    </li>
                    @endif
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- بطاقة سبب الرفض (تظهر فقط في حالة الرفض) -->
@if ($collection->status === 'rejected' && $collection->rejection_reason)
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0 text-danger"><i class="ti ti-message-circle me-1"></i>سبب الرفض</h5>
    </div>
    <div class="card-body">
        <div class="bg-light-danger border-danger border rounded p-3">
            <p class="mb-0 fw-medium text-danger">{{ $collection->rejection_reason }}</p>
        </div>
    </div>
</div>
@endif


<!-- بطاقة المرفقات (تصميم احترافي ومبسط) -->
@if ($collection->attachment)
<div class="card mt-4">
    <div class="card-header">
        <h5 class="card-title mb-0">المرفقات</h5>
    </div>
    <div class="card-body text-center">
        <div class="avatar avatar-xl mx-auto mb-3">
            <span class="avatar-initial rounded bg-label-primary">
                <i class="ti ti-file-text ti-26px"></i>
            </span>
        </div>
        <p class="mb-3 text-muted">انقر على الأيقونة أدناه لعرض المرفق</p>
        <a href="{{ tasset($collection->attachment) }}" target="_blank" class="btn btn-primary btn-icon"
            aria-label="عرض المرفق">
            <i class="ti ti-eye"></i>
        </a>
    </div>
</div>
@endif

@endsection