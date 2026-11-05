@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل الباقة - ' . $plan->name)

@section('toastr')
    @vite(['resources/assets/css/shared/toastr.css', 'resources/assets/js/shared/toastr.js'])
@endsection

@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.plans.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
            @can('plans.edit')
                <a href="{{ route('admin.plans.edit', $plan->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل الباقة
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        {{-- كارت معلومات الباقة (العمود الأيمن) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <div class="avatar avatar-xl rounded-circle p-3 d-flex align-items-center justify-content-center mx-auto shadow-sm"
                            style="width: 100px; height: 100px; background-color: {{ $plan->color?->hex() ?? '#7367f0' }}20; color: {{ $plan->color?->hex() ?? '#7367f0' }};">
                            <i class="ti ti-cube fs-1"></i>
                        </div>
                    </div>
                    <h5 class="mb-1 fw-bold">{{ $plan->name }}</h5>
                    <span class="badge bg-label-dark text-dark mb-2">{{ $plan->code }}</span>
                    <span class="badge bg-{{ $plan->plan_type?->color() ?? 'primary' }} mb-3">
                        {{ $plan->plan_type?->label() ?? 'أساسي' }}
                    </span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ $statistics['total_subscriptions'] ?? 0 }}</h5>
                            <small class="text-muted">الاشتراكات</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success">{{ $statistics['active_subscriptions'] ?? 0 }}</h5>
                            <small class="text-muted">النشطة</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ count($plan->features ?? []) }}</h5>
                            <small class="text-muted">الميزات</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-currency-riyal text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">السعر الأساسي:</span>
                                <span>{{ number_format((float) $plan->price, 2) }} ر.س</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-tag text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">السعر بعد الخصم:</span>
                                <span class="text-success fw-bold">{{ number_format($plan->price_after_discount, 2) }}
                                    ر.س</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-users text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المستخدمين:</span>
                                <span>{{ $plan->isUnlimited() ? 'غير محدود (∞)' : $plan->included_users . ' مستخدم' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-building text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">حد العقارات:</span>
                                <span>{{ $plan->isUnlimited() ? 'غير محدود (∞)' : $plan->properties_limit ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-gavel text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">حد المزادات:</span>
                                <span>{{ $plan->isUnlimited() ? 'غير محدود (∞)' : $plan->active_auctions_limit ?? '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-server text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">حصة التخزين:</span>
                                <span>{{ $plan->isUnlimited() ? 'غير محدود (∞)' : ($plan->storage_quota_gb ? $plan->storage_quota_gb . ' GB' : '-') }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-world text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">إدارة الدومينات:</span>
                                <span>{{ $plan->domain_management_enabled ? 'مفعّلة' : 'غير مفعّلة' }}</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-calendar text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ الإتاحة:</span>
                                <span>{{ $plan->available_from ? $plan->available_from->format('Y-m-d') : 'متاحة فوراً' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            @if (!empty($plan->admin_notes))
                <div class="card mb-4">
                    <div class="card-header pb-2">
                        <h6 class="card-title mb-0"><i class="ti ti-notes text-primary me-2"></i>ملاحظات إدارية</h6>
                    </div>
                    <div class="card-body">
                        <p class="mb-0 text-muted small">{{ $plan->admin_notes }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- تفاصيل المؤشرات والاشتراكات (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- بطاقة المؤشرات المالية والتشغيلية --}}
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0"><i class="ti ti-chart-pie text-primary me-2"></i>المؤشرات المالية والتشغيلية
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-primary rounded p-2 me-3">
                                        <i class="ti ti-currency-riyal fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">السعر الأساسي للدورة</small>
                                        <span class="fw-bold">{{ number_format((float) $plan->price, 2) }} ر.س</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-warning rounded p-2 me-3">
                                        <i class="ti ti-percentage fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">نسبة الخصم</small>
                                        <span class="fw-bold">{{ $plan->discount_percentage ?? 0 }}%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-success rounded p-2 me-3">
                                        <i class="ti ti-tag fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">السعر النهائي</small>
                                        <span
                                            class="fw-bold text-success">{{ number_format($plan->price_after_discount, 2) }}
                                            ر.س</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-info rounded p-2 me-3">
                                        <i class="ti ti-calendar-event fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">سعر العرض السنوي</small>
                                        <span
                                            class="fw-bold">{{ $plan->yearly_renewal_price ? number_format((float) $plan->yearly_renewal_price, 2) . ' ر.س' : 'غير متوفر' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-secondary rounded p-2 me-3">
                                        <i class="ti ti-user-plus fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">ترخيص إضافي</small>
                                        <span
                                            class="fw-bold">{{ $plan->isUnlimited() ? 'غير مطلوب' : ($plan->extra_user_license_price ? number_format((float) $plan->extra_user_license_price, 2) . ' ر.س' : '0.00 ر.س') }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-danger rounded p-2 me-3">
                                        <i class="ti ti-gavel fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">حد المزادات</small>
                                        <span
                                            class="fw-bold">{{ $plan->isUnlimited() ? 'غير محدود (∞)' : $plan->active_auctions_limit ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- بطاقة ميزات الباقة التسويقية --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-list-check text-primary me-2"></i>ميزات الباقة التسويقية
                        ({{ count($plan->features ?? []) }})
                    </h5>
                    @if (!empty($plan->features) && count($plan->features) > 0)
                        <span class="badge bg-label-primary">{{ count($plan->features) }} ميزة</span>
                    @endif
                </div>
                <div class="card-body">
                    @if (!empty($plan->features) && count($plan->features) > 0)
                        <div class="list-group list-group-flush">
                            @foreach ($plan->features as $feature)
                                <div class="list-group-item d-flex align-items-center px-0">
                                    <i class="ti ti-circle-check text-success me-2 fs-5"></i>
                                    <span>{{ $feature }}</span>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-4 text-muted">
                            <i class="ti ti-file-text fs-1 mb-2"></i>
                            <p class="mb-0">لا توجد ميزات مسجلة لهذه الباقة.</p>
                        </div>
                    @endif
                </div>
            </div>

            {{-- بطاقة الاشتراكات والشركات المرتبطة --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-building-store text-primary me-2"></i>الاشتراكات والشركات المرتبطة بالباقة
                        ({{ $subscriptions->count() }})
                    </h5>
                    @if ($subscriptions->count() > 0)
                        <span class="badge bg-label-primary">{{ $subscriptions->count() }} اشتراك</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>الشركة</th>
                                <th>المسؤول</th>
                                <th>فترة الاشتراك</th>
                                <th>الحالة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($subscriptions as $sub)
                                @php
                                    $tenant = $sub->tenant;
                                    $primaryContact = $tenant?->primary_contact_name ?? $tenant?->owner?->name;
                                    $primaryEmail = $tenant?->primary_contact_email ?? $tenant?->owner?->email;
                                    $tenantUrl = $tenant ? route('path.dashboard', ['tenant' => $tenant->id]) : null;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <span class="avatar-initial rounded-circle bg-label-primary">
                                                    {{ mb_substr($tenant?->company_name ?? 'ش', 0, 1) }}
                                                </span>
                                            </div>
                                            <div>
                                                <a href="{{ $tenant ? route('admin.clients.show', $tenant->id) : '#' }}"
                                                    class="fw-semibold text-body">
                                                    {{ $tenant?->company_name ?? 'شركة غير محددة' }}
                                                </a>
                                                <small
                                                    class="text-muted d-block">{{ $tenant?->commercial_number ?: '-' }}</small>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div>{{ $primaryContact ?: '-' }}</div>
                                        <small class="text-muted">{{ $primaryEmail ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <div>
                                            {{ $sub->start_date ? \Carbon\Carbon::parse($sub->start_date)->format('Y-m-d') : '-' }}
                                        </div>
                                        <small class="text-muted">إلى
                                            {{ $sub->end_date ? \Carbon\Carbon::parse($sub->end_date)->format('Y-m-d') : '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $sub->status?->color() ?? 'secondary' }}">
                                            {{ $sub->status?->label() ?? 'غير محدد' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            @if ($tenant)
                                                <a href="{{ route('admin.clients.show', $tenant->id) }}"
                                                    class="btn btn-icon btn-text-secondary btn-sm"
                                                    title="عرض تفاصيل العميل">
                                                    <i class="ti ti-eye"></i>
                                                </a>
                                            @endif
                                            @if ($tenantUrl)
                                                <a href="{{ $tenantUrl }}"
                                                    class="btn btn-icon btn-text-secondary btn-sm"
                                                    title="لوحة تحكم الشركة (رابط مباشر)" target="_blank">
                                                    <i class="ti ti-external-link"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        لم تسجل أي شركة اشتراكاً في هذه الباقة حتى الآن.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
                url: `/superadmin/plans/${planId}/toggle-active`,
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
                            toastr.success(response.message || 'تم تحديث حالة الباقة بنجاح');
                        }
                        setTimeout(() => {
                            window.location.reload();
                        }, 1200);
                    } else {
                        if (typeof toastr !== 'undefined') {
                            toastr.error(response.message || 'تعذر تحديث حالة الباقة');
                        }
                    }
                },
                error: function(xhr) {
                    if (typeof toastr !== 'undefined') {
                        toastr.error(xhr.responseJSON?.message || 'حدث خطأ أثناء تحديث حالة الباقة');
                    }
                },
                complete: function() {
                    if (btn) btn.disabled = false;
                }
            });
        }
    </script>
@endsection
