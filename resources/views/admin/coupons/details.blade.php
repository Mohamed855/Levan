@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل الكوبون')

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/css/shared/tab.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/js/tenant/settings/tab.js'])
@endsection

@section('content')
    <div class="row">
        {{-- شريط التبويبات الجانبي --}}
        <div class="col-12 col-md-3 col-xl-3">
            <div>
                <ul class="nav nav-tabs flex-column border-bottom-0">
                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold active m-0" data-bs-toggle="tab" href="#couponDetails">
                            <i class="ti ti-discount-2 ti-sm me-1"></i><span>تفاصيل الكوبون</span>
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#couponUsage">
                            <i class="ti ti-receipt ti-sm me-1"></i><span>سجل الاستخدام</span>
                            @if ($tenants->count() > 0)
                                <span class="badge bg-primary rounded-pill ms-auto">{{ $tenants->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- محتوى التبويبات --}}
        <div class="col-12 col-md-9 col-xl-9 order-1 order-md-0 mb-5">
            <div class="tab-content p-0">
                {{-- تبويب تفاصيل الكوبون --}}
                <div class="tab-pane fade show active" id="couponDetails">
                    <div class="card">
                        <div class="card-header py-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-discount-2 text-primary me-2"></i>
                                تفاصيل الكوبون
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                @can('coupons.edit')
                                    <button type="button"
                                        class="btn btn-sm {{ $couponDetails->is_active ? 'btn-warning' : 'btn-success' }} js-toggle-details-status"
                                        onclick="toggleDetailsStatus({{ $couponDetails->id }})">
                                        <i class="ti ti-power me-1"></i>
                                        <span>{{ $couponDetails->is_active ? 'تعطيل الكوبون' : 'تفعيل الكوبون' }}</span>
                                    </button>
                                @endcan
                                <a href="{{ route('admin.coupons.edit', $couponDetails->id) }}"
                                    class="btn btn-sm btn-primary">
                                    <i class="ti ti-edit me-1"></i>تعديل الكوبون
                                </a>
                                <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-arrow-right me-1"></i>رجوع
                                </a>
                            </div>
                        </div>

                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            {{-- بطاقة كود الكوبون الرئيسية --}}
                            <div class="card border mb-4">
                                <div class="card-body d-flex align-items-center flex-wrap gap-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="avatar avatar-xl bg-primary rounded p-2 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-discount-2 fs-1 text-white"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                            <h4 class="fw-bold mb-0 text-primary">{{ $couponDetails->code }}</h4>
                                            <button type="button" class="btn btn-xs btn-secondary copy-code-btn"
                                                data-code="{{ $couponDetails->code }}"
                                                onclick="copyCode('{{ $couponDetails->code }}', this)" title="نسخ الكود">
                                                <i class="ti ti-copy ti-xs me-1"></i><span>نسخ الكود</span>
                                            </button>
                                        </div>
                                        <p class="mb-0 text-muted fw-medium">{{ $couponDetails->name }}</p>
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        <div class="mb-1">
                                            @if ($couponDetails->is_active)
                                                @if ($couponDetails->expiry_date && \Carbon\Carbon::parse($couponDetails->expiry_date)->isPast())
                                                    <span class="badge bg-warning fs-6">
                                                        <i class="ti ti-clock-exclamation me-1"></i>منتهي الصلاحية
                                                    </span>
                                                @else
                                                    <span class="badge bg-success fs-6">
                                                        <i class="ti ti-circle-check me-1"></i>نشط
                                                    </span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary fs-6">
                                                    <i class="ti ti-circle-x me-1"></i>غير نشط
                                                </span>
                                            @endif
                                        </div>
                                        <div>
                                            <span class="badge bg-primary fs-6">
                                                @if ($couponDetails->discount_type === 'fixed')
                                                    {{ number_format($couponDetails->discount_value, 2) }} ر.س خصم
                                                @else
                                                    {{ number_format($couponDetails->discount_value, 0) }}% خصم
                                                @endif
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- بيانات الكوبون في بطاقات إحصائية --}}
                            <div class="row mb-4">
                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-percentage me-2 text-primary fs-4"></i>
                                                <div>
                                                    <h6 class="mb-2">نوع الخصم</h6>
                                                    <span
                                                        class="fw-bold">{{ $couponDetails->discount_type === 'fixed' ? 'خصم بقيمة ثابتة' : 'نسبة مئوية' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-currency-riyal me-2 text-success fs-4"></i>
                                                <div>
                                                    <h6 class="mb-2">قيمة الخصم</h6>
                                                    <span class="fw-bold fs-5 text-dark">
                                                        @if ($couponDetails->discount_type === 'fixed')
                                                            {{ number_format($couponDetails->discount_value, 2) }} ر.س
                                                        @else
                                                            {{ number_format($couponDetails->discount_value, 0) }}%
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-coin me-2 text-warning fs-4"></i>
                                                <div>
                                                    <h6 class="mb-2">الحد الأقصى للخصم</h6>
                                                    <span class="fw-bold">
                                                        {{ $couponDetails->max_discount ? number_format($couponDetails->max_discount, 2) . ' ر.س' : 'غير محدد' }}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-chart-arrows-vertical me-2 text-info fs-4"></i>
                                                <div>
                                                    <h6 class="mb-2">حد الاستخدام</h6>
                                                    <span
                                                        class="fw-bold">{{ $couponDetails->usage_limit ? $couponDetails->usage_limit . ' مرة' : 'غير محدود' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-users me-2 text-primary fs-4"></i>
                                                <div class="w-100">
                                                    <h6 class="mb-2">مرات الاستخدام</h6>
                                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                                        <span class="fw-bold">{{ $couponDetails->used_count }} مرة</span>
                                                        @if ($couponDetails->usage_limit)
                                                            @php
                                                                $usagePercent = min(
                                                                    100,
                                                                    round(
                                                                        ($couponDetails->used_count /
                                                                            $couponDetails->usage_limit) *
                                                                            100,
                                                                        1,
                                                                    ),
                                                                );
                                                            @endphp
                                                            <small
                                                                class="text-muted">{{ $couponDetails->used_count }}/{{ $couponDetails->usage_limit }}
                                                                ({{ $usagePercent }}%)</small>
                                                        @endif
                                                    </div>
                                                    @if ($couponDetails->usage_limit)
                                                        <div class="progress" style="height: 6px;">
                                                            <div class="progress-bar {{ $usagePercent >= 100 ? 'bg-danger' : ($usagePercent >= 75 ? 'bg-warning' : 'bg-primary') }}"
                                                                role="progressbar" style="width: {{ $usagePercent }}%"
                                                                aria-valuenow="{{ $usagePercent }}" aria-valuemin="0"
                                                                aria-valuemax="100"></div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-calendar-event me-2 text-danger fs-4"></i>
                                                <div>
                                                    <h6 class="mb-2">تاريخ الانتهاء</h6>
                                                    @if ($couponDetails->expiry_date)
                                                        @php
                                                            $expiryCarbon = \Carbon\Carbon::parse(
                                                                $couponDetails->expiry_date,
                                                            );
                                                            $isPast = $expiryCarbon->isPast();
                                                        @endphp
                                                        <span
                                                            class="fw-bold {{ $isPast ? 'text-danger' : 'text-dark' }}">{{ $expiryCarbon->format('Y-m-d') }}</span>
                                                        <small
                                                            class="d-block {{ $isPast ? 'text-danger' : 'text-muted' }}">
                                                            {{ $isPast ? 'منتهي الصلاحية' : $expiryCarbon->diffForHumans() }}
                                                        </small>
                                                    @else
                                                        <span class="fw-bold text-success">غير محدد (صالح دائماً)</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-calendar-plus me-2 text-secondary fs-4"></i>
                                                <div>
                                                    <h6 class="mb-2">تاريخ الإنشاء</h6>
                                                    <span
                                                        class="fw-bold">{{ \Carbon\Carbon::parse($couponDetails->created_at)->format('Y-m-d - h:i A') }}</span>
                                                    <small
                                                        class="d-block text-muted">{{ \Carbon\Carbon::parse($couponDetails->created_at)->diffForHumans() }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-calendar-check me-2 text-secondary fs-4"></i>
                                                <div>
                                                    <h6 class="mb-2">آخر تحديث</h6>
                                                    @if ($couponDetails->updated_at && $couponDetails->updated_at != $couponDetails->created_at)
                                                        <span
                                                            class="fw-bold">{{ \Carbon\Carbon::parse($couponDetails->updated_at)->format('Y-m-d - h:i A') }}</span>
                                                        <small
                                                            class="d-block text-muted">{{ \Carbon\Carbon::parse($couponDetails->updated_at)->diffForHumans() }}</small>
                                                    @else
                                                        <span class="fw-bold text-muted">لا يوجد تحديثات سابقة</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <div class="card border h-100">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="ti ti-toggle-right me-2 text-primary fs-4"></i>
                                                <div>
                                                    <h6 class="mb-2">حالة الكوبون</h6>
                                                    @if ($couponDetails->is_active)
                                                        <span class="badge bg-success">مفعل</span>
                                                    @else
                                                        <span class="badge bg-danger">معطل</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- بطاقة المستخدم المسند له الكوبون --}}
                            <div>
                                <h6 class="mb-3">
                                    <i class="ti ti-user-shield text-primary me-2"></i>
                                    المستخدم الإداري المسند له الكوبون
                                </h6>
                                @if ($couponDetails->admin_user)
                                    <div class="card border mb-0">
                                        <div class="card-body d-flex align-items-center flex-wrap gap-3">
                                            <div class="flex-shrink-0">
                                                @php
                                                    $adminAvatar =
                                                        $couponDetails->admin_user->profile_picture &&
                                                        \Illuminate\Support\Facades\Storage::disk('public')->exists(
                                                            $couponDetails->admin_user->profile_picture,
                                                        )
                                                            ? asset(
                                                                'storage/' .
                                                                    $couponDetails->admin_user->profile_picture,
                                                            )
                                                            : asset('static/brand/logos/logo.svg');
                                                @endphp
                                                <img src="{{ $adminAvatar }}" class="rounded-circle" width="60"
                                                    height="60" style="object-fit: cover;">
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="fw-bold mb-1">{{ $couponDetails->admin_user->name }}</h5>
                                                <div class="text-muted small d-flex align-items-center gap-3 flex-wrap">
                                                    <span><i
                                                            class="ti ti-mail me-1"></i>{{ $couponDetails->admin_user->email }}</span>
                                                    @if ($couponDetails->admin_user->phone)
                                                        <span><i
                                                                class="ti ti-phone me-1"></i>{{ $couponDetails->admin_user->phone }}</span>
                                                    @endif
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                @if ($couponDetails->admin_user->is_super_admin)
                                                    <span class="badge bg-primary">
                                                        <i class="ti ti-crown me-1"></i>مدير عام
                                                    </span>
                                                @else
                                                    <span class="badge bg-info">
                                                        <i class="ti ti-user-check me-1"></i>مستخدم إداري
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="card border mb-0 bg-secondary">
                                        <div class="card-body d-flex align-items-center py-3">
                                            <div
                                                class="avatar avatar-md me-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center">
                                                <i class="ti ti-user-off fs-4 text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1 fw-bold text-muted">غير مسند لمستخدم إداري</h6>
                                                <small class="text-muted">لم يتم تعيين مستخدم محدد لهذا الكوبون (كوبون
                                                    عام)</small>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- تبويب سجل الاستخدام --}}
                <div class="tab-pane fade" id="couponUsage">
                    <div class="card">
                        <div class="card-header py-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-receipt text-primary me-2"></i>
                                سجل استخدام الكوبون
                            </h6>
                            <span class="badge bg-primary">
                                {{ $tenants->count() }} {{ $tenants->count() == 1 ? 'عميل' : 'عملاء' }}
                            </span>
                        </div>

                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            @if ($tenants->count() > 0)
                                @php
                                    $totalBills = $tenants->sum(fn($t) => $t->bills->count());
                                    $totalAmount = $tenants->sum(fn($t) => $t->bills->sum('amount'));
                                @endphp

                                {{-- إحصائيات سريعة لسجل الاستخدام --}}
                                <div class="row mb-4">
                                    <div class="col-md-4 mb-3">
                                        <div class="card border h-100">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="avatar bg-primary rounded p-2 me-3">
                                                    <i class="ti ti-building fs-4 text-white"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">إجمالي العملاء</small>
                                                    <h5 class="mb-0 fw-bold">{{ $tenants->count() }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border h-100">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="avatar bg-info rounded p-2 me-3">
                                                    <i class="ti ti-file-invoice fs-4 text-white"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">إجمالي الفواتير</small>
                                                    <h5 class="mb-0 fw-bold">{{ $totalBills }}</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <div class="card border h-100">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="avatar bg-success rounded p-2 me-3">
                                                    <i class="ti ti-currency-riyal fs-4 text-white"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">إجمالي قيمة الفواتير</small>
                                                    <h5 class="mb-0 fw-bold">{{ number_format($totalAmount, 2) }} ر.س</h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- قائمة العملاء والفواتير --}}
                                @foreach ($tenants as $tenant)
                                    <div class="card border mb-4">
                                        <div
                                            class="card-header bg-secondary py-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                                            <div class="d-flex align-items-center gap-2">
                                                <div
                                                    class="avatar avatar-sm bg-primary rounded-circle d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-building fs-5 text-white"></i>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0 fw-bold">
                                                        {{ $tenant->company_name ?? 'عميل بدون اسم' }}</h6>
                                                    <small class="text-muted">معرف العميل: {{ $tenant->id }}</small>
                                                </div>
                                            </div>
                                            <span class="badge bg-info">
                                                <i class="ti ti-file-invoice me-1"></i>
                                                {{ $tenant->bills->count() }}
                                                {{ $tenant->bills->count() == 1 ? 'فاتورة' : 'فواتير' }}
                                            </span>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover mb-0">
                                                <thead class="table-light">
                                                    <tr>
                                                        <th>رقم الفاتورة</th>
                                                        <th>تاريخ الإنشاء</th>
                                                        <th>تاريخ الدفع</th>
                                                        <th>طريقة الدفع</th>
                                                        <th>المبلغ</th>
                                                        <th>الحالة</th>
                                                        <th class="text-center">إجراءات</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($tenant->bills as $bill)
                                                        <tr>
                                                            <td class="fw-bold">
                                                                <a href="{{ route('admin.bills.show', $bill->id) }}"
                                                                    class="text-primary">
                                                                    {{ $bill->bill_number }}
                                                                </a>
                                                            </td>
                                                            <td>{{ \Carbon\Carbon::parse($bill->created_at)->format('Y-m-d') }}
                                                            </td>
                                                            <td>
                                                                @if ($bill->paid_at)
                                                                    {{ \Carbon\Carbon::parse($bill->paid_at)->format('Y-m-d H:i') }}
                                                                @else
                                                                    <span class="text-muted">-</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if ($bill->payment_method == 'online')
                                                                    <span class="badge bg-success"><i
                                                                            class="ti ti-credit-card me-1"></i>دفع
                                                                        إلكتروني</span>
                                                                @else
                                                                    <span class="badge bg-info"><i
                                                                            class="ti ti-building-bank me-1"></i>تحويل
                                                                        بنكي</span>
                                                                @endif
                                                            </td>
                                                            <td class="fw-bold">{{ number_format($bill->amount, 2) }} ر.س
                                                            </td>
                                                            <td>
                                                                @if ($bill->status == 'paid')
                                                                    <span class="badge bg-success"><i
                                                                            class="ti ti-check me-1"></i>مدفوع</span>
                                                                @elseif ($bill->status == 'pending')
                                                                    <span class="badge bg-warning"><i
                                                                            class="ti ti-clock me-1"></i>قيد
                                                                        الانتظار</span>
                                                                @else
                                                                    <span class="badge bg-danger"><i
                                                                            class="ti ti-x me-1"></i>ملغي</span>
                                                                @endif
                                                            </td>
                                                            <td class="text-center">
                                                                <a href="{{ route('admin.bills.show', $bill->id) }}"
                                                                    class="btn btn-xs btn-primary">
                                                                    <i class="ti ti-eye me-1"></i>عرض الفاتورة
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                <div class="alert alert-info d-flex align-items-center mb-0" role="alert">
                                    <i class="ti ti-info-circle fs-4 me-2"></i>
                                    <div>
                                        لم يقم أي عميل باستخدام هذا الكوبون حتى الآن.
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('page-script')
    <script>
        function copyTextToClipboard(text, onSuccess, onError) {
            if (!text) return;

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(function() {
                    if (typeof onSuccess === 'function') onSuccess();
                }).catch(function() {
                    fallbackCopyText(text, onSuccess, onError);
                });
            } else {
                fallbackCopyText(text, onSuccess, onError);
            }
        }

        function fallbackCopyText(text, onSuccess, onError) {
            try {
                const textArea = document.createElement('textarea');
                textArea.value = text;
                textArea.style.position = 'fixed';
                textArea.style.top = '-9999px';
                textArea.style.left = '-9999px';
                textArea.setAttribute('readonly', '');
                document.body.appendChild(textArea);
                textArea.focus();
                textArea.select();
                textArea.setSelectionRange(0, textArea.value.length);
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);

                if (successful) {
                    if (typeof onSuccess === 'function') onSuccess();
                } else if (typeof onError === 'function') {
                    onError();
                }
            } catch (err) {
                if (typeof onError === 'function') onError();
            }
        }

        function copyCode(code, buttonElement) {
            const btn = buttonElement || (typeof event !== 'undefined' && event?.target ? event.target.closest('button') :
                null) || document.querySelector('.copy-code-btn');
            const originalHtml = btn ? btn.innerHTML : '';

            copyTextToClipboard(code, function() {
                if (btn) {
                    btn.innerHTML = '<i class="ti ti-check ti-xs me-1"></i><span>تم النسخ!</span>';
                    btn.classList.remove('btn-secondary');
                    btn.classList.add('btn-success');

                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-secondary');
                    }, 2000);
                }

                if (typeof toastr !== 'undefined') {
                    toastr.success('تم نسخ الكود: ' + code);
                }
            }, function() {
                if (typeof toastr !== 'undefined') {
                    toastr.error('تعذر نسخ الكود');
                }
            });
        }

        function toggleDetailsStatus(couponId) {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const btn = document.querySelector('.js-toggle-details-status');
            if (btn) btn.disabled = true;

            $.ajax({
                url: `/superadmin/coupons/${couponId}/toggle-status`,
                type: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': token
                },
                data: {
                    _token: token
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof toastr !== 'undefined') {
                            toastr.success(response.message || 'تم تحديث الحالة بنجاح');
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 1200);
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(response.message || 'تعذر تحديث الحالة');
                        }
                    }
                },
                error: function(xhr) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(xhr.responseJSON?.message || 'حدث خطأ أثناء تحديث الحالة');
                    }
                },
                complete: function() {
                    if (btn) btn.disabled = false;
                }
            });
        }
    </script>
@endsection
