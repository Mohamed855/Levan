@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل العميل والفاتورة')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">

        <!-- Header Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h4 class="text-white mb-2">
                                    <i class="bx bx-buildings me-2"></i>
                                    {{ $tenant->company_name }}
                                </h4>
                                <p class="mb-1 opacity-75">
                                    <i class="bx bx-id-card me-1"></i>
                                    رقم السجل التجاري: {{ $tenant->commercial_number }}
                                </p>
                                <p class="mb-0 opacity-75">
                                    <i class="bx bx-hash me-1"></i>
                                    معرف العميل: {{ $tenant->id }}
                                </p>
                            </div>
                            <div class="text-end">
                                @if ($lastSubscription)
                                    <div class="badge bg-white text-primary px-3 py-2 mb-2">
                                        <i class="bx bx-check-circle me-1"></i>
                                        {{ $lastSubscription->status == 'active' ? 'اشتراك نشط' : 'اشتراك غير نشط' }}
                                    </div>
                                @else
                                    <div class="badge bg-white text-danger px-3 py-2 mb-2">
                                        <i class="bx bx-x-circle me-1"></i>
                                        لا يوجد اشتراك
                                    </div>
                                @endif
                                <p class="mb-0 opacity-75 small">
                                    تاريخ الانضمام: {{ \Carbon\Carbon::parse($tenant->created_at)->format('Y/m/d') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (!$lastSubscription || $bills->isEmpty())
            <!-- No Data Available -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <i class="bx bx-info-circle display-1 text-muted mb-3"></i>
                            <h4 class="mb-2">لا توجد بيانات متاحة</h4>
                            <p class="text-muted">
                                @if (!$lastSubscription)
                                    هذا العميل ليس لديه اشتراك حالياً.
                                @elseif($bills->isEmpty())
                                    لا توجد فواتير متاحة لهذا العميل.
                                @endif
                            </p>

                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="row">
                <!-- Main Invoice Section -->
                <div class="col-lg-8 mb-4">

                    <!-- Subscription Plan Card -->
                    @if ($plan)
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="bx bx-package me-2"></i>
                                    تفاصيل الباقة المشترك بها
                                </h5>
                                <span class="badge bg-primary">{{ $plan->name }}</span>
                            </div>
                            <div class="card-body mt-3">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-sm bg-label-primary rounded me-3">
                                                <i class="bx bx-dollar"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">سعر الباقة</small>
                                                <h6 class="mb-0">{{ number_format($plan->price, 2) }} ﷼</h6>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="avatar avatar-sm bg-label-info rounded me-3">
                                                <i class="bx bx-user"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">عدد المستخدمين</small>
                                                <h6 class="mb-0">{{ $lastSubscription->used_users }} من
                                                    {{ $lastSubscription->total_users }}</h6>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($plan->features && is_array($plan->features))
                                        <div class="col-12">
                                            <hr class="my-3">
                                            <h6 class="mb-3">مميزات الباقة:</h6>
                                            <div class="row">
                                                @foreach ($plan->features as $feature)
                                                    <div class="col-md-6 mb-2">
                                                        <div class="d-flex align-items-center">
                                                            <i class="bx bx-check-circle text-success me-2"></i>
                                                            <span>{{ $feature }}</span>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Invoice Card -->
                    <div class="card">
                        <div class="card-header bg-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h4 class="mb-1">فاتورة #{{ $bills->first()->bill_number }}</h4>
                                    <small class="text-muted">
                                        <i class="bx bx-calendar me-1"></i>
                                        تاريخ الإصدار:
                                        {{ \Carbon\Carbon::parse($bills->first()->created_at)->format('Y/m/d') }}
                                    </small>
                                </div>
                                <div class="text-end">
                                    @if ($bills->first()->status == 'paid')
                                        <span class="badge bg-success px-3 py-2">
                                            <i class="bx bx-check me-1"></i>
                                            مدفوعة
                                        </span>
                                    @else
                                        <span class="badge bg-warning px-3 py-2">
                                            <i class="bx bx-time me-1"></i>
                                            غير مدفوعة
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="card-body">

                            <!-- Subscription Period -->
                            <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                                <i class="bx bx-info-circle me-2"></i>
                                <div>
                                    <strong>فترة الاشتراك:</strong>
                                    من {{ \Carbon\Carbon::parse($lastSubscription->starts_at)->format('Y/m/d') }}
                                    إلى {{ \Carbon\Carbon::parse($lastSubscription->next_payment_date)->format('Y/m/d') }}
                                    <span class="ms-2 badge bg-primary">
                                        {{ \Carbon\Carbon::parse($lastSubscription->starts_at)->diffInDays(\Carbon\Carbon::parse($lastSubscription->next_payment_date)) }}
                                        يوم
                                    </span>
                                </div>
                            </div>

                            <!-- Invoice Items -->
                            <div class="table-responsive mb-4">
                                <table class="table table-borderless">
                                    <thead class="border-bottom">
                                        <tr>
                                            <th class="text-end">البند</th>
                                            <th class="text-center">الكمية</th>
                                            <th class="text-start">المبلغ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="text-end">
                                                <div>
                                                    <h6 class="mb-1">
                                                        {{ $bills->first()->type == 'new_subscription' ? 'اشتراك جديد' : 'تجديد اشتراك' }}
                                                    </h6>
                                                    <small class="text-muted">{{ $plan->name ?? 'باقة' }}</small>
                                                </div>
                                            </td>
                                            <td class="text-center">1</td>
                                            <td class="text-start">{{ number_format($lastSubscription->price, 2) }} ﷼</td>
                                        </tr>
                                        @if ($plan && $lastSubscription->total_users > $plan->included_users)
                                            <tr>
                                                <td class="text-end">
                                                    <div>
                                                        <h6 class="mb-1">مستخدمين إضافيين</h6>
                                                        <small
                                                            class="text-muted">{{ $lastSubscription->total_users - $plan->included_users }}
                                                            مستخدم</small>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    {{ $lastSubscription->total_users - $plan->included_users }}</td>
                                                <td class="text-start">
                                                    {{ number_format($lastSubscription->price - $plan->price, 2) }} ﷼
                                                </td>
                                            </tr>
                                        @endif
                                    </tbody>
                                </table>
                            </div>

                            <hr class="my-4">

                            <!-- Pricing Breakdown -->
                            @php
                                $billAmount = $bills->first()->amount;
                                $amountBeforeVAT = $billAmount / 1.15; // المبلغ قبل الضريبة
                                $vatAmount = $billAmount - $amountBeforeVAT; // قيمة الضريبة 15%
                            @endphp

                            <div class="row justify-content-start">
                                <div class="col-md-6">

                                    <!-- Subtotal (Before VAT) -->
                                    <div class="d-flex justify-content-between align-items-center py-2">
                                        <span class="text-muted">المجموع الفرعي (قبل الضريبة)</span>
                                        <span class="fw-semibold">{{ number_format($amountBeforeVAT, 2) }} ﷼</span>
                                    </div>

                                    <!-- Tax -->
                                    <div class="d-flex justify-content-between align-items-center py-2">
                                        <span class="text-muted">ضريبة القيمة المضافة (15%)</span>
                                        <span class="fw-semibold">{{ number_format($vatAmount, 2) }} ﷼</span>
                                    </div>

                                    <!-- Discount -->
                                    @if ($bills->first()->discount_amount > 0)
                                        <div class="d-flex justify-content-between align-items-center py-2">
                                            <span class="text-muted">الخصم</span>
                                            <span class="fw-semibold text-success">-
                                                {{ number_format($bills->first()->discount_amount, 2) }} ﷼</span>
                                        </div>
                                    @endif

                                    <hr class="my-3">

                                    <!-- Total -->
                                    <div class="d-flex justify-content-between align-items-center py-2">
                                        <h5 class="mb-0">الإجمالي النهائي</h5>
                                        <h4 class="mb-0 text-primary fw-bold">{{ number_format($billAmount, 2) }} ﷼</h4>
                                    </div>

                                </div>
                            </div>

                            <!-- Footer Note -->
                            <div class="alert alert-info mt-4" role="alert">
                                <div class="d-flex align-items-center">
                                    <i class="bx bx-info-circle me-2"></i>
                                    <div>
                                        <strong>ملاحظة:</strong>
                                        @if ($lastSubscription->auto_renew)
                                            التجديد التلقائي مفعّل. سيتم تجديد الاشتراك تلقائياً في
                                            {{ \Carbon\Carbon::parse($lastSubscription->next_payment_date)->format('Y/m/d') }}
                                        @else
                                            التجديد التلقائي غير مفعّل. يرجى تجديد الاشتراك يدوياً قبل
                                            {{ \Carbon\Carbon::parse($lastSubscription->next_payment_date)->format('Y/m/d') }}
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Thank You Message -->
                            <div class="text-center mt-4 pt-4 border-top">
                                <p class="text-muted mb-0">نتطلع دائماً لخدمتكم عبر منصة ليفـان LEVAN</p>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">

                    <!-- Bill Information -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-file me-2"></i>
                                معلومات الفاتورة
                            </h6>
                        </div>
                        <div class="card-body mt-3">
                            <ul class="list-unstyled mb-0">
                                <li class="mb-3">
                                    <small class="text-muted d-block mb-1">رقم الفاتورة</small>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-receipt text-primary me-2"></i>
                                        <strong>{{ $bills->first()->bill_number }}</strong>
                                    </div>
                                </li>

                                <li class="mb-3">
                                    <small class="text-muted d-block mb-1">رقم الاشتراك</small>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-hash text-info me-2"></i>
                                        <strong>{{ $lastSubscription->subscription_number }}</strong>
                                    </div>
                                </li>

                                <li class="mb-3">
                                    <small class="text-muted d-block mb-1">تاريخ الإصدار</small>
                                    <div class="d-flex align-items-center">
                                        <i class="bx bx-calendar text-warning me-2"></i>
                                        <strong>{{ \Carbon\Carbon::parse($bills->first()->created_at)->format('Y/m/d') }}</strong>
                                    </div>
                                </li>

                                <li class="mb-3">
                                    <small class="text-muted d-block mb-1">نوع الفاتورة</small>
                                    <span class="badge bg-label-info">
                                        {{ $bills->first()->type == 'new_subscription' ? 'اشتراك جديد' : 'تجديد' }}
                                    </span>
                                </li>

                                <li class="mb-3">
                                    <small class="text-muted d-block mb-1">طريقة الدفع</small>
                                    <div class="d-flex align-items-center">
                                        @if ($bills->first()->payment_method == 'bank_transfer')
                                            <i class="bx bx-transfer text-success me-2"></i>
                                            <strong>تحويل بنكي</strong>
                                        @elseif($bills->first()->payment_method == 'credit_card')
                                            <i class="bx bx-credit-card text-primary me-2"></i>
                                            <strong>بطاقة ائتمان</strong>
                                        @else
                                            <i class="bx bx-question-mark text-secondary me-2"></i>
                                            <strong>غير محدد</strong>
                                        @endif
                                    </div>
                                </li>

                                @if ($bills->first()->gateway_payment_id)
                                    <li class="mb-3">
                                        <small class="text-muted d-block mb-1">معرف الدفع</small>
                                        <div class="d-flex align-items-center">
                                            <i class="bx bx-id-card text-secondary me-2"></i>
                                            <code class="small">{{ $bills->first()->gateway_payment_id }}</code>
                                        </div>
                                    </li>
                                @endif

                                <li class="mb-0">
                                    <small class="text-muted d-block mb-1">حالة الفاتورة</small>
                                    @if ($bills->first()->status == 'paid')
                                        <span class="badge bg-success w-100">
                                            <i class="bx bx-check-circle me-1"></i>
                                            مدفوعة
                                        </span>
                                    @else
                                        <span class="badge bg-warning w-100">
                                            <i class="bx bx-time me-1"></i>
                                            غير مدفوعة
                                        </span>
                                    @endif
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Subscription Statistics -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">
                                <i class="bx bx-bar-chart me-2"></i>
                                إحصائيات الاشتراك
                            </h6>
                        </div>
                        <div class="card-body mt-2">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">المستخدمون</span>
                                <span
                                    class="badge bg-label-primary">{{ $lastSubscription->used_users }}/{{ $lastSubscription->total_users }}</span>
                            </div>
                            <div class="progress mb-3" style="height: 8px;">
                                <div class="progress-bar bg-primary" role="progressbar"
                                    style="width: {{ $lastSubscription->total_users > 0 ? ($lastSubscription->used_users / $lastSubscription->total_users) * 100 : 0 }}%">
                                </div>
                            </div>

                            @php
                                $daysRemaining = \Carbon\Carbon::now()->diffInDays(
                                    \Carbon\Carbon::parse($lastSubscription->next_payment_date),
                                    false,
                                );
                                $daysRemaining = max(0, $daysRemaining);
                            @endphp

                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-muted">الأيام المتبقية</span>
                                <span class="badge bg-label-warning">
                                    {{ $daysRemaining }} يوم
                                </span>
                            </div>

                            <div class="d-flex justify-content-between align-items-center">
                                <span class="text-muted">التجديد التلقائي</span>
                                @if ($lastSubscription->auto_renew)
                                    <span class="badge bg-success">
                                        <i class="bx bx-check"></i> مفعّل
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bx bx-x"></i> غير مفعّل
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        @endif

    </div>


@endsection
