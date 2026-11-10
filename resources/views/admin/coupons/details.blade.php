@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل الكوبون - ' . $couponDetails->name)


@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.coupons.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
            @can('coupons.edit')
                <a href="{{ route('admin.coupons.edit', $couponDetails->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل الكوبون
                </a>
            @endcan
        </div>
    </div>

    <div class="row">
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <div class="avatar avatar-xl bg-label-primary rounded-circle p-3 d-flex align-items-center justify-content-center mx-auto shadow-sm"
                            style="width: 100px; height: 100px;">
                            <i class="ti ti-discount-2 fs-1 text-primary"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-primary">{{ $couponDetails->code }}</h4>
                    <p class="mb-2 text-muted fw-medium">{{ $couponDetails->name }}</p>
                    <span class="badge bg-label-primary mb-3 fs-6">
                        @if ($couponDetails->discount_type === 'fixed')
                            خصم {{ number_format($couponDetails->discount_value, 2) }} ر.س
                        @else
                            خصم {{ number_format($couponDetails->discount_value, 0) }}%
                        @endif
                    </span>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">{{ $couponDetails->used_count }}</h5>
                            <small class="text-muted">الاستخدام</small>
                        </div>
                        <div>
                            <h5 class="mb-0">{{ $couponDetails->usage_limit ?: 'غير محدود' }}</h5>
                            <small class="text-muted">الحد الأقصى</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success">{{ $tenants->count() }}</h5>
                            <small class="text-muted">العملاء</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-percentage text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">نوع الخصم:</span>
                                <span>{{ $couponDetails->discount_type === 'fixed' ? 'قيمة ثابتة' : 'نسبة مئوية' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-coin text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الحد الأقصى للخصم:</span>
                                <span>{{ $couponDetails->max_discount ? number_format($couponDetails->max_discount, 2) . ' ر.س' : 'غير محدد' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-plus text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ البداية:</span>
                                <span>{{ system_date($couponDetails->start_date, 'فوري') }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-calendar-due text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ الانتهاء:</span>
                                <span>{{ system_date($couponDetails->expiry_date, 'مفتوح') }}</span>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-clock text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">تاريخ الإنشاء:</span>
                                <span>@date($couponDetails->created_at)</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            {{-- بطاقة منشئ الكوبون --}}
            @php
                $creator = $couponDetails->admin_user ?? $couponDetails->creator;
            @endphp
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <h6 class="card-title mb-0"><i class="ti ti-user-shield text-primary me-2"></i>أنشئ بواسطة</h6>
                </div>
                <div class="card-body">
                    @if ($creator)
                        <div class="d-flex align-items-center">
                            <div
                                class="avatar avatar-md me-3 bg-label-primary rounded-circle d-flex align-items-center justify-content-center">
                                <i class="ti ti-user fs-4 text-primary"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold">{{ $creator->name }}</h6>
                                <small class="text-muted">{{ $creator->email }}</small>
                            </div>
                        </div>
                    @else
                        <div class="d-flex align-items-center py-1">
                            <div
                                class="avatar avatar-md me-3 bg-secondary rounded-circle d-flex align-items-center justify-content-center">
                                <i class="ti ti-user-off fs-4 text-white"></i>
                            </div>
                            <div>
                                <h6 class="mb-0 fw-bold text-muted">غير محدد</h6>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- تفاصيل المؤشرات وسجل الاستخدام (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- بطاقة مؤشرات الكوبون --}}
            <div class="card mb-4">
                <div class="card-header pb-2">
                    <h5 class="card-title mb-0"><i class="ti ti-chart-bar text-primary me-2"></i>مؤشرات الاستخدام</h5>
                </div>
                <div class="card-body">
                    @php
                        $totalBills = $tenants->sum(fn($t) => $t->bills->count());
                        $totalAmount = $tenants->sum(fn($t) => $t->bills->sum('amount'));
                        $usagePercent = $couponDetails->usage_limit
                            ? min(100, round(($couponDetails->used_count / $couponDetails->usage_limit) * 100, 1))
                            : null;
                    @endphp

                    <div class="row g-3 mb-3">
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-primary rounded text-center p-2 me-3">
                                        <i class="ti ti-building fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">العملاء المستفيدين</small>
                                        <span class="fw-bold fs-5">{{ $tenants->count() }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-success rounded text-center p-2 me-3">
                                        <i class="ti ti-receipt fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">إجمالي الفواتير</small>
                                        <span class="fw-bold fs-5">{{ $totalBills }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6 col-md-4">
                            <div class="card border shadow-none h-100">
                                <div class="card-body p-3 d-flex align-items-center">
                                    <div class="avatar bg-info rounded text-center p-2 me-3">
                                        <i class="ti ti-cash fs-4 text-white"></i>
                                    </div>
                                    <div>
                                        <small class="text-muted d-block">إجمالي مبالغ الفواتير</small>
                                        <span class="fw-bold fs-5">{{ number_format($totalAmount, 2) }} ر.س</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if ($usagePercent !== null)
                        <div class="mt-3">
                            <div class="d-flex justify-content-between mb-1">
                                <small class="fw-semibold">نسبة الاستهلاك من الحد الأقصى</small>
                                <small class="text-muted">{{ $couponDetails->used_count }} من
                                    {{ $couponDetails->usage_limit }} ({{ $usagePercent }}%)</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar {{ $usagePercent >= 90 ? 'bg-danger' : ($usagePercent >= 70 ? 'bg-warning' : 'bg-primary') }}"
                                    role="progressbar" style="width: {{ $usagePercent }}%"
                                    aria-valuenow="{{ $usagePercent }}" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- بطاقة سجل الاستخدام --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-receipt text-primary me-2"></i>سجل الاستخدام ({{ $tenants->count() }})
                    </h5>
                    @if ($tenants->count() > 0)
                        <span class="badge bg-label-primary">{{ $tenants->count() }} عميل</span>
                    @endif
                </div>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>العميل</th>
                                <th>المسؤول</th>
                                <th>عدد مرات الاستخدام</th>
                                <th>إجمالي المدفوع</th>
                                <th>آخر استخدام</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tenants as $tenant)
                                @php
                                    $primaryContact = $tenant->primary_contact_name ?? $tenant->owner?->name;
                                    $primaryEmail = $tenant->primary_contact_email ?? $tenant->owner?->email;
                                    $couponBills = $tenant->bills;
                                    $tenantTotalAmount = $couponBills->sum('amount');
                                    $lastBill = $couponBills->first();
                                @endphp
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.clients.show', $tenant->id) }}"
                                            class="fw-semibold text-body">
                                            {{ $tenant->company_name }}
                                        </a>
                                        <small class="text-muted d-block">{{ $tenant->commercial_number ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <div>{{ $primaryContact ?: '-' }}</div>
                                        <small class="text-muted">{{ $primaryEmail ?: '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-label-info">{{ $couponBills->count() }} مرة</span>
                                    </td>
                                    <td>
                                        <span class="fw-bold">{{ number_format($tenantTotalAmount, 2) }} ر.س</span>
                                    </td>
                                    <td>
                                        <div>@date($lastBill?->created_at)
                                        </div>
                                        <small
                                            class="text-muted">{{ $lastBill?->created_at ? $lastBill->created_at->diffForHumans() : '' }}</small>
                                    </td>
                                    <td>
                                        <x-actions.row>
                                            <x-actions.show :url="route('admin.clients.show', $tenant->id)" title="عرض تفاصيل العميل" />
                                        </x-actions.row>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        لم يقم أي عميل باستخدام هذا الكوبون حتى الآن.
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
                textArea.style.left = '-999999px';
                textArea.style.top = '-999999px';
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
                    btn.classList.remove('btn-outline-secondary');
                    btn.classList.add('btn-success');

                    setTimeout(() => {
                        btn.innerHTML = originalHtml;
                        btn.classList.remove('btn-success');
                        btn.classList.add('btn-outline-secondary');
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
