<div class="row">
    {{-- العمود الأيمن (معلومات المستخدم) --}}
    <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <div class="mx-auto mb-3">
                    <img src="{{ $user->image && Storage::disk('public')->exists($user->image) ? asset('storage/' . $user->image) : asset('static/brand/logos/logo.svg') }}"
                        alt="صورة المستخدم" class="rounded-circle border shadow-sm"
                        style="width: 100px; height: 100px; object-fit: cover;"
                        onerror="this.onerror=null;this.src='{{ asset('static/brand/logos/logo.svg') }}';">
                </div>
                <h5 class="mb-1 fw-bold">{{ $user->name }}</h5>
                <span class="badge bg-info mb-3">
                    {{ $role ? \App\Support\AccessLabels::role($role->name) : 'بدون دور' }}
                </span>

                <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                    <div>
                        <h6 class="mb-0 text-muted small">تاريخ الإضافة</h6>
                        <span class="fw-semibold">{{ optional($user->created_at)->format('Y-m-d') ?? '-' }}</span>
                    </div>
                    <div>
                        <h6 class="mb-0 text-muted small">آخر دخول</h6>
                        <span class="fw-semibold">{{ $user->last_login_at ? $user->last_login_at->locale('ar')->diffForHumans() : 'لم يسجل' }}</span>
                    </div>
                </div>

                <div class="info-container text-start">
                    <ul class="list-unstyled mb-0">
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-user-circle text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الاسم:</span>
                            <span class="text-muted">{{ $user->name }}</span>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-mail text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">البريد:</span>
                            <a href="mailto:{{ $user->email }}" class="text-truncate">{{ $user->email }}</a>
                        </li>
                        <li class="mb-3 d-flex align-items-center">
                            <i class="ti ti-phone text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الهاتف:</span>
                            <span class="text-muted" dir="ltr">{{ $user->phone ?? 'غير محدد' }}</span>
                        </li>
                        <li class="mb-2 d-flex align-items-center">
                            <i class="ti ti-id-badge text-primary me-2 fs-5"></i>
                            <span class="fw-bold me-2">الدور:</span>
                            <span class="badge bg-label-primary">{{ $role ? \App\Support\AccessLabels::role($role->name) : 'غير متوفر' }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    {{-- العمود الأيسر (النشاطات والكوبونات) --}}
    <div class="col-xl-8 col-lg-7 col-md-7">
        {{-- كارت سجل النشاطات --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h6 class="card-title mb-0">
                    <i class="ti ti-history text-warning me-2"></i> سجل النشاطات
                </h6>
                <span class="badge bg-label-secondary">آخر 5 نشاطات</span>
            </div>
            <div class="card-body">
                <ul class="timeline mb-0">
                    @forelse ($recentActivities as $activity)
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-primary"></span>
                            <div class="timeline-event">
                                <div class="timeline-header mb-1">
                                    <h6 class="mb-0 text-capitalize">{{ $activity->description }}</h6>
                                    <small class="text-muted">{{ $activity->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </li>
                    @empty
                        <div class="text-center text-muted py-4">
                            <i class="ti ti-history-off fs-2 d-block mb-2"></i>
                            لا توجد نشاطات مسجلة
                        </div>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- كارت الكوبونات المُسندة --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center py-3">
                <h6 class="card-title mb-0">
                    <i class="ti ti-ticket text-success me-2"></i> الكوبونات المُسندة
                </h6>
                <span class="badge bg-primary rounded-pill">{{ $coupons->count() }} كوبون</span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    @forelse ($coupons as $coupon)
                        @php
                            $expiryDate = $coupon->expiry_date ? \Carbon\Carbon::parse($coupon->expiry_date) : null;
                            $isExpired = $expiryDate && $expiryDate->isPast();
                            $isExhausted = $coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit;
                            $isActive = $coupon->is_active && !$isExpired && !$isExhausted;
                        @endphp
                        <div class="col-md-6">
                            <div class="card border h-100 shadow-none p-3 {{ $isActive ? 'border-primary' : 'border-secondary' }}">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0 fw-bold">{{ $coupon->name }}</h6>
                                    @if (!$coupon->is_active)
                                        <span class="badge bg-label-secondary">غير نشط</span>
                                    @elseif ($isExpired)
                                        <span class="badge bg-label-danger">منتهي</span>
                                    @elseif ($isExhausted)
                                        <span class="badge bg-label-warning">مستنفد</span>
                                    @else
                                        <span class="badge bg-label-success">نشط</span>
                                    @endif
                                </div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="text-muted small">الكود:</span>
                                    <code class="bg-light px-2 py-1 rounded fw-bold cursor-pointer"
                                        onclick="navigator.clipboard.writeText('{{ $coupon->code }}'); toastr.success('تم نسخ الكود');"
                                        title="انقر للنسخ">
                                        {{ $coupon->code }}
                                    </code>
                                </div>
                                <div class="d-flex justify-content-between text-muted small mt-auto pt-2 border-top">
                                    <span>الاستخدام: {{ $coupon->used_count ?? 0 }} / {{ $coupon->usage_limit ?? '∞' }}</span>
                                    <span>{{ $expiryDate ? $expiryDate->format('Y-m-d') : 'بدون انتهاء' }}</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">
                            <i class="ti ti-ticket-off fs-2 d-block mb-2"></i>
                            لا توجد كوبونات مسندة لهذا المستخدم
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
