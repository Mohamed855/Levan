@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل الباقة - ' . $plan->name)

@section('vendor-style')
    @vite(['resources/assets/vendor/libs/animate-css/animate.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss', 'resources/assets/css/tenants/tab.css'])
@endsection

@section('vendor-script')
    @vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js', 'resources/assets/js/tenants/settings/tab.js'])
@endsection

@section('content')
    <div class="row">
        {{-- شريط التبويبات الجانبي --}}
        <div class="col-12 col-md-3 col-xl-3">
            <div>
                <ul class="nav nav-tabs flex-column border-bottom-0">
                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold active m-0" data-bs-toggle="tab" href="#planDetails">
                            <i class="ti ti-cube ti-sm me-1"></i><span>تفاصيل الباقة</span>
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#planFeatures">
                            <i class="ti ti-list-check ti-sm me-1"></i><span>ميزات الباقة</span>
                            @if (!empty($plan->features) && count($plan->features) > 0)
                                <span
                                    class="badge bg-label-primary rounded-pill ms-auto">{{ count($plan->features) }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#planSubscriptions">
                            <i class="ti ti-building-store ti-sm me-1"></i><span>الاشتراكات والشركات</span>
                            @if ($subscriptions->count() > 0)
                                <span
                                    class="badge bg-label-primary rounded-pill ms-auto">{{ $subscriptions->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- محتوى التبويبات --}}
        <div class="col-12 col-md-9 col-xl-9 order-1 order-md-0 mb-5">
            <div class="tab-content p-0">
                {{-- تبويب تفاصيل الباقة --}}
                <div class="tab-pane fade show active" id="planDetails">
                    <div class="card">
                        <div class="card-header py-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-cube text-primary me-2"></i>
                                تفاصيل الباقة
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                @can('plans.edit')
                                    <button type="button"
                                        class="btn btn-sm {{ $plan->is_active ? 'btn-label-warning' : 'btn-label-success' }} js-toggle-details-status"
                                        onclick="togglePlanStatus({{ $plan->id }})">
                                        <i class="ti ti-power me-1"></i>
                                        <span>{{ $plan->is_active ? 'تعطيل الباقة' : 'تفعيل الباقة' }}</span>
                                    </button>
                                    <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-sm btn-label-primary">
                                        <i class="ti ti-edit me-1"></i>تعديل الباقة
                                    </a>
                                @endcan
                                <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-arrow-right me-1"></i>رجوع
                                </a>
                            </div>
                        </div>

                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            {{-- بطاقة الباقة الرئيسية --}}
                            <div class="card border mb-4">
                                <div class="card-body d-flex align-items-center flex-wrap gap-3">
                                    <div class="flex-shrink-0">
                                        <div class="avatar avatar-xl rounded p-2 d-flex align-items-center justify-content-center"
                                            style="background-color: {{ $plan->color?->hex() ?? '#7367f0' }}20; color: {{ $plan->color?->hex() ?? '#7367f0' }};">
                                            <i class="ti ti-cube fs-1"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                            <h4 class="fw-bold mb-0 text-heading">{{ $plan->name }}</h4>
                                            <span
                                                class="badge bg-label-dark font-monospace fw-bold fs-6">{{ $plan->code }}</span>

                                            <span
                                                class="badge bg-label-{{ $plan->plan_type?->color() ?? 'primary' }} fs-6">
                                                {{ $plan->plan_type?->label() ?? 'أساسي' }}
                                            </span>

                                            @if ($plan->is_active)
                                                <span class="badge bg-label-success fs-6">
                                                    <i class="ti ti-circle-check me-1"></i>نشط
                                                </span>
                                            @else
                                                <span class="badge bg-secondary fs-6">
                                                    <i class="ti ti-circle-x me-1"></i>غير نشط
                                                </span>
                                            @endif

                                            <span
                                                class="badge bg-label-{{ $plan->isUnlimited() ? 'primary' : 'info' }} fs-6">
                                                {{ $plan->isUnlimited() ? 'حدود غير محدودة (∞)' : 'حدود محددة' }}
                                            </span>

                                            <span class="badge bg-label-info fs-6">
                                                دورة: {{ $plan->subscription_billing_cycle?->label() ?? 'شهري' }}
                                            </span>

                                            @if (!empty($statistics['is_most_requested']))
                                                <span class="badge bg-gradient-danger text-white fs-6">
                                                    <i class="ti ti-flame me-1"></i>الأكثر طلباً
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mb-0 text-muted">
                                            {{ $plan->description ?: 'لا يوجد وصف مدخل لهذه الباقة.' }}
                                        </p>
                                    </div>
                                    <div class="flex-shrink-0 text-end">
                                        <div class="mb-1">
                                            <span
                                                class="fs-4 fw-bold text-success">{{ number_format($plan->price_after_discount, 2) }}
                                                ر.س</span>
                                            @if ($plan->discount_percentage > 0)
                                                <div class="text-muted small">
                                                    <del>{{ number_format((float) $plan->price, 2) }} ر.س</del>
                                                    <span
                                                        class="badge bg-label-danger ms-1">{{ $plan->discount_percentage }}%
                                                        خصم</span>
                                                </div>
                                            @endif
                                        </div>
                                        <small class="text-muted d-block">
                                            {{ $plan->subscription_billing_cycle?->label() ? 'لكل ' . $plan->subscription_billing_cycle->label() : 'لكل دورة' }}
                                        </small>
                                    </div>
                                </div>
                            </div>

                            {{-- بطاقات المؤشرات المالية والتشغيلية --}}
                            <div class="row g-4 mb-4">
                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-primary rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-currency-riyal fs-4 text-primary"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">السعر الأساسي للدورة</small>
                                                    <span class="fw-bold fs-6">{{ number_format((float) $plan->price, 2) }}
                                                        ر.س</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-warning rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-percentage fs-4 text-warning"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">نسبة الخصم</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->discount_percentage ?? 0 }}%</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-success rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-tag fs-4 text-success"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">السعر النهائي</small>
                                                    <span
                                                        class="fw-bold fs-6 text-success">{{ number_format($plan->price_after_discount, 2) }}
                                                        ر.س</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-info rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-calendar-event fs-4 text-info"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">سعر العرض السنوي</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->yearly_renewal_price ? number_format((float) $plan->yearly_renewal_price, 2) . ' ر.س' : 'غير متوفر' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-primary rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-users fs-4 text-primary"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">المستخدمين المشمولين</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->isUnlimited() ? 'غير محدود (∞)' : $plan->included_users . ' مستخدم' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-secondary rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-user-plus fs-4 text-secondary"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">ترخيص المستخدم الإضافي</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->isUnlimited() ? 'غير مطلوب' : ($plan->extra_user_license_price ? number_format((float) $plan->extra_user_license_price, 2) . ' ر.س' : '0.00 ر.س') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-info rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-building fs-4 text-info"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">حد العقارات</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->isUnlimited() ? 'غير محدود (∞)' : $plan->properties_limit ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-warning rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-server fs-4 text-warning"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">حصة التخزين</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->isUnlimited() ? 'غير محدود (∞)' : ($plan->storage_quota_gb ? $plan->storage_quota_gb . ' GB' : '-') }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-danger rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-gavel fs-4 text-danger"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">حد المزادات النشطة</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->isUnlimited() ? 'غير محدود (∞)' : $plan->active_auctions_limit ?? '-' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-label-primary rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-world fs-4 text-primary"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">إدارة الدومينات</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->domain_management_enabled ? 'مفعّلة' : 'غير مفعّلة' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3 col-sm-6">
                                    <div class="card border h-100 shadow-none">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center">
                                                <div
                                                    class="avatar bg-secondary rounded p-2 me-3 d-flex align-items-center justify-content-center">
                                                    <i class="ti ti-calendar fs-4 text-secondary"></i>
                                                </div>
                                                <div>
                                                    <small class="text-muted d-block">تاريخ بدء الإتاحة</small>
                                                    <span
                                                        class="fw-bold fs-6">{{ $plan->available_from ? $plan->available_from->format('Y-m-d') : 'متاحة فوراً' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if (!empty($plan->admin_notes))
                                <div class="card bg-secondary border-0 mb-4">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-1"><i class="ti ti-notes me-1"></i>ملاحظات إدارية داخلية:
                                        </h6>
                                        <p class="mb-0 text-muted">{{ $plan->admin_notes }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- تبويب ميزات الباقة --}}
                <div class="tab-pane fade" id="planFeatures">
                    <div class="card">
                        <div class="card-header py-4">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-list-check text-primary me-2"></i>
                                ميزات الباقة التسويقية
                            </h6>
                        </div>
                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            @if (!empty($plan->features) && count($plan->features) > 0)
                                <div class="list-group">
                                    @foreach ($plan->features as $feature)
                                        <div class="list-group-item d-flex align-items-center">
                                            <i class="ti ti-check text-success me-2 fs-5"></i>
                                            <span>{{ $feature }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-center py-5 text-muted">
                                    <i class="ti ti-file-text fs-1 mb-2"></i>
                                    <p>لا توجد ميزات مسجلة لهذه الباقة.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- تبويب الاشتراكات والشركات --}}
                <div class="tab-pane fade" id="planSubscriptions">
                    <div class="card">
                        <div class="card-header py-4">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-building-store text-primary me-2"></i>
                                الاشتراكات والشركات المرتبطة بالباقة
                            </h6>
                        </div>
                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            <div class="row g-4 mb-4">
                                <div class="col-md-3 col-sm-6">
                                    <div class="card border shadow-none">
                                        <div class="card-body p-3 d-flex align-items-center">
                                            <div class="avatar bg-label-primary rounded p-2 me-3">
                                                <i class="ti ti-receipt fs-4"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">إجمالي الاشتراكات</small>
                                                <span
                                                    class="fw-bold fs-5">{{ $statistics['total_subscriptions'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card border shadow-none">
                                        <div class="card-body p-3 d-flex align-items-center">
                                            <div class="avatar bg-label-info rounded p-2 me-3">
                                                <i class="ti ti-building fs-4"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">الشركات الفريدة</small>
                                                <span
                                                    class="fw-bold fs-5 text-info">{{ $statistics['unique_companies'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card border shadow-none">
                                        <div class="card-body p-3 d-flex align-items-center">
                                            <div class="avatar bg-label-success rounded p-2 me-3">
                                                <i class="ti ti-bolt fs-4"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">الاشتراكات النشطة</small>
                                                <span
                                                    class="fw-bold fs-5 text-success">{{ $statistics['active_subscriptions'] ?? 0 }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-sm-6">
                                    <div class="card border shadow-none">
                                        <div class="card-body p-3 d-flex align-items-center">
                                            <div class="avatar bg-label-warning rounded p-2 me-3">
                                                <i class="ti ti-cash fs-4"></i>
                                            </div>
                                            <div>
                                                <small class="text-muted d-block">إجمالي الإيرادات التعاقدية</small>
                                                <span
                                                    class="fw-bold fs-5 text-warning">{{ number_format($statistics['total_revenue'] ?? 0, 2) }}
                                                    ر.س</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            @if ($subscriptions->count() > 0)
                                <div class="table-responsive">
                                    <table class="table table-hover table-striped border">
                                        <thead class="table-light">
                                            <tr>
                                                <th>#</th>
                                                <th>رقم الاشتراك</th>
                                                <th>اسم الشركة</th>
                                                <th>معرف العميل</th>
                                                <th>الدورة التعاقدية</th>
                                                <th>تاريخ البداية</th>
                                                <th>تاريخ النهاية</th>
                                                <th>المبلغ التعاقدي</th>
                                                <th>الحالة</th>
                                                <th>الإجراءات</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($subscriptions as $index => $subscription)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        <span
                                                            class="font-monospace fw-bold">{{ $subscription->subscription_number ?? 'SUB-' . $subscription->id }}</span>
                                                    </td>
                                                    <td>
                                                        <strong>{{ $subscription->tenant?->company_name ?? '-' }}</strong>
                                                    </td>
                                                    <td>
                                                        <span
                                                            class="badge bg-secondary font-monospace">{{ $subscription->tenant_id }}</span>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-label-info">
                                                            {{ $subscription->billing_cycle?->label() ?? ($subscription->currentPeriod?->billing_cycle_snapshot ?? 'شهري') }}
                                                        </span>
                                                    </td>
                                                    <td dir="ltr">
                                                        {{ $subscription->starts_at?->format('Y-m-d') ?? '-' }}</td>
                                                    <td dir="ltr">
                                                        {{ $subscription->ends_at?->format('Y-m-d') ?? '-' }}</td>
                                                    <td>
                                                        <span
                                                            class="fw-bold">{{ number_format((float) $subscription->price, 2) }}
                                                            ر.س</span>
                                                    </td>
                                                    <td>
                                                        @if ($subscription->status)
                                                            <span class="badge bg-{{ $subscription->status->color() }}">
                                                                {{ $subscription->status->label() }}
                                                            </span>
                                                        @else
                                                            <span class="badge bg-secondary">-</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center gap-1">
                                                            {{-- رابط ملف الشركة --}}
                                                            <a href="{{ route('admin.companies.show', $subscription->tenant_id) }}"
                                                                class="btn btn-icon btn-label-primary btn-sm"
                                                                title="عرض ملف الشركة">
                                                                <i class="ti ti-building"></i>
                                                            </a>

                                                            {{-- رابط لوحة تحكم الشركة الخارجي --}}
                                                            @php
                                                                $domain =
                                                                    $subscription->tenant?->domain ??
                                                                    ($subscription->tenant?->domains?->first()
                                                                        ?->domain ??
                                                                        null);
                                                                $tenantUrl = $domain
                                                                    ? (str_starts_with($domain, 'http')
                                                                        ? $domain
                                                                        : 'https://' . $domain)
                                                                    : null;
                                                            @endphp
                                                            @if ($tenantUrl)
                                                                <a href="{{ $tenantUrl }}"
                                                                    class="btn btn-icon btn-secondary btn-sm"
                                                                    title="لوحة تحكم الشركة (رابط مباشر)" target="_blank">
                                                                    <i class="ti ti-external-link"></i>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="text-center py-5">
                                    <div
                                        class="avatar avatar-xl bg-secondary rounded p-3 mx-auto mb-3 d-flex align-items-center justify-content-center">
                                        <i class="ti ti-building-off fs-1 text-secondary"></i>
                                    </div>
                                    <h6 class="fw-bold mb-1">لا توجد شركات مشتركة</h6>
                                    <p class="text-muted mb-0">لم تسجل أي شركة اشتراكاً في هذه الباقة حتى الآن.</p>
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
        function togglePlanStatus(planId) {
            const token = document.querySelector('meta[name="csrf-token"]')?.content;
            const btn = document.querySelector('.js-toggle-details-status');
            if (btn) btn.disabled = true;

            $.ajax({
                url: `/admin/plans/${planId}/toggle-active`,
                type: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': token
                },
                data: {
                    _token: token
                },
                success: function(response) {
                    if (response.success) {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: response.message || 'تم تحديث حالة الباقة بنجاح',
                                showConfirmButton: false,
                                timer: 1500
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            window.location.reload();
                        }
                    } else {
                        if (typeof Swal !== 'undefined') {
                            Swal.fire({
                                icon: 'error',
                                title: 'خطأ',
                                text: response.message || 'تعذر تحديث حالة الباقة'
                            });
                        }
                    }
                },
                error: function(xhr) {
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'error',
                            title: 'خطأ',
                            text: xhr.responseJSON?.message || 'حدث خطأ أثناء تحديث حالة الباقة'
                        });
                    }
                },
                complete: function() {
                    if (btn) btn.disabled = false;
                }
            });
        }
    </script>
@endsection
