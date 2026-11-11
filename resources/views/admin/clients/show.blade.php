@extends('admin.layouts.layoutMaster')

@php
    $target = $client ?? $company;
    $activeSub = $target->activeSubscription ?? $target->lastSubscription;
@endphp

@section('title', 'تفاصيل العميل — ' . $target->company_name)

@section('content')
    <div class="d-flex justify-content-end align-items-center mb-4 flex-wrap gap-2">
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ route('admin.clients.index') }}" class="btn btn-secondary">
                <i class="ti ti-arrow-right me-1"></i> العودة
            </a>
            @canany(['clients.edit', 'companies.edit'])
                <a href="{{ route('admin.clients.edit', $target->id) }}" class="btn btn-primary">
                    <i class="ti ti-edit me-1"></i> تعديل العميل
                </a>
            @endcanany
            <a href="{{ route('path.dashboard', ['tenant' => $target->id]) }}" target="_blank"
                class="btn btn-outline-primary">
                <i class="ti ti-dashboard me-1"></i> لوحة التحكم
            </a>
        </div>
    </div>

    <div class="row">
        {{-- كارت الملف الأساسي للعميل (العمود الأيمن) --}}
        <div class="col-xl-4 col-lg-5 col-md-5 mb-4">
            <div class="card mb-4">
                <div class="card-body text-center">
                    <div class="mx-auto mb-3">
                        <div class="avatar avatar-xl bg-label-primary rounded-circle p-3 d-flex align-items-center justify-content-center mx-auto shadow-sm"
                            style="width: 100px; height: 100px;">
                            <i class="ti ti-building fs-1 text-primary"></i>
                        </div>
                    </div>
                    <h4 class="mb-1 fw-bold text-primary">{{ $target->company_name }}</h4>
                    <p class="mb-2 text-muted fw-medium">معرف العميل: {{ $target->id }}</p>
                    <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">
                        @if ($activeSub)
                            <span class="badge bg-label-{{ $activeSub->status->color() }} fs-6">
                                {{ $activeSub->status->label() }}
                            </span>
                        @else
                            <span class="badge bg-label-secondary fs-6">بدون اشتراك</span>
                        @endif
                        <span class="badge bg-label-info fs-6">
                            {{ $activeSub?->plan?->name ?? 'عميل لوت' }}
                        </span>
                    </div>

                    <div class="d-flex justify-content-around my-3 py-2 border-top border-bottom">
                        <div>
                            <h5 class="mb-0">@date($target->created_at)</h5>
                            <small class="text-muted">تاريخ التسجيل</small>
                        </div>
                        <div>
                            <h5 class="mb-0 text-success">{{ $target->subscriptions->count() }}</h5>
                            <small class="text-muted">الاشتراكات</small>
                        </div>
                    </div>

                    <div class="info-container text-start">
                        <ul class="list-unstyled mb-0">
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-file-text text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">السجل التجاري:</span>
                                <span dir="ltr">{{ $target->commercial_number ?: '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-receipt-tax text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الرقم الضريبي:</span>
                                <span dir="ltr">{{ $target->tax_number ?: '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-map-pin text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المدينة:</span>
                                <span>{{ $target->city?->name ?: '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-home text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">العنوان:</span>
                                <span>{{ $target->address ?: '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-user-circle text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">المسؤول:</span>
                                <span>{{ $target->primary_contact_name ?: '-' }}</span>
                            </li>
                            <li class="mb-3 d-flex align-items-center">
                                <i class="ti ti-mail text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">البريد:</span>
                                <a href="mailto:{{ $target->primary_contact_email }}"
                                    class="text-truncate">{{ $target->primary_contact_email ?: '-' }}</a>
                            </li>
                            <li class="mb-2 d-flex align-items-center">
                                <i class="ti ti-phone text-primary me-2 fs-5"></i>
                                <span class="fw-bold me-2">الهاتف:</span>
                                <span dir="ltr">{{ $target->primary_contact_phone ?: '-' }}</span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- سجل الاشتراكات وسجل الأنشطة (العمود الأيسر) --}}
        <div class="col-xl-8 col-lg-7 col-md-7">
            {{-- كارت سجل الاشتراكات --}}
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-packages text-primary me-2"></i>سجل الاشتراكات
                    </h5>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-label-primary">{{ $target->subscriptions->count() }} اشتراك</span>
                        @can('subscriptions.create')
                            @if (!$target->lastSubscription || $target->lastSubscription->status->value === 'expired')
                                <a href="{{ route('admin.subscriptions.create') }}?company_id={{ $target->id }}"
                                    class="btn btn-sm btn-primary">
                                    <i class="ti ti-plus me-1"></i>إضافة اشتراك جديد
                                </a>
                            @endif
                        @endcan
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>رقم الاشتراك</th>
                                <th>الباقة</th>
                                <th>حدود الباقة</th>
                                <th>البداية</th>
                                <th>النهاية</th>
                                <th>الأيام المتبقية</th>
                                <th>الحالة</th>
                                <th>تغيير الحالة</th>
                                <th class="text-center">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($target->subscriptions as $sub)
                                @php
                                    $period =
                                        $sub->currentPeriod ??
                                        $sub->periods->sortByDesc('period_end')->first();
                                    $days = app(
                                        \App\Services\Shared\Subscription\SubscriptionManager::class,
                                    )->calculateDaysRemaining($period);
                                    $isActive =
                                        $sub->status ===
                                        \App\Enums\Admin\Subscriptions\SubscriptionStatus::ACTIVE;
                                @endphp
                                <tr>
                                    <td>
                                        <span class="fw-bold text-primary">{{ $sub->subscription_number }}</span>
                                    </td>
                                    <td>{{ $sub->plan?->name ?: '-' }}</td>
                                    <td>
                                        <div class="d-flex flex-wrap justify-content-center gap-1">
                                            <span class="badge bg-label-info"><i
                                                    class="ti ti-users ti-xs me-1"></i>{{ $sub->total_users }}</span>
                                            <span class="badge bg-label-primary"><i
                                                    class="ti ti-building ti-xs me-1"></i>{{ $sub->plan?->properties_limit ?? '∞' }}</span>
                                            <span class="badge bg-label-danger"><i
                                                    class="ti ti-database ti-xs me-1"></i>{{ $sub->plan?->storage_quota_gb ? $sub->plan->storage_quota_gb . 'GB' : '∞' }}</span>
                                        </div>
                                    </td>
                                    <td>@date($sub->starts_at)</td>
                                    <td>@date($period?->period_end ?? $sub->ends_at)</td>
                                    <td>
                                        @if ($days === null)
                                            <span class="badge bg-label-secondary">-</span>
                                        @elseif ($days <= 0)
                                            <span class="badge bg-label-danger">منتهي</span>
                                        @elseif ($days <= 7)
                                            <span class="badge bg-label-danger">{{ $days }} يوم</span>
                                        @elseif ($days <= 30)
                                            <span class="badge bg-label-warning">{{ $days }} يوماً</span>
                                        @else
                                            <span class="badge bg-label-success">{{ $days }} يوماً</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge bg-label-{{ $sub->status->color() }}">{{ $sub->status->label() }}</span>
                                    </td>
                                    <td>
                                        @can('subscriptions.toggle_status')
                                            <div class="form-check form-switch mb-0 d-flex justify-content-center">
                                                <input type="checkbox"
                                                    class="form-check-input js-sub-toggle text-center"
                                                    data-url="{{ route('admin.subscriptions.toggle-status', $sub->id) }}"
                                                    {{ $isActive ? 'checked' : '' }}>
                                            </div>
                                        @else
                                            -
                                        @endcan
                                    </td>
                                    <td>
                                        <x-actions.row>
                                            @can('subscriptions.delete')
                                                <button type="button"
                                                    class="btn btn-icon btn-label-danger js-sub-delete-btn"
                                                    data-url="{{ route('admin.subscriptions.destroy', $sub->id) }}"
                                                    data-number="{{ $sub->subscription_number }}"
                                                    title="حذف الاشتراك">
                                                    <i class="ti ti-trash"></i>
                                                </button>
                                            @endcan
                                        </x-actions.row>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4 text-muted">لا توجد اشتراكات مسجلة لهذا العميل.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- كارت سجل الأنشطة والعمليات --}}
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center mb-3">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-history text-primary me-2"></i>سجل النشاطات
                    </h5>
                    <span class="badge bg-label-primary">آخر النشاطات</span>
                </div>
                <div class="card-body">
                    <ul class="timeline mb-0">
                        @forelse ($activities as $activity)
                            <li class="timeline-item timeline-item-transparent">
                                <span class="timeline-point timeline-point-primary"></span>
                                <div class="timeline-event">
                                    <div class="timeline-header mb-1">
                                        <h6 class="mb-0 text-capitalize">{{ $activity->description }}</h6>
                                        <small class="text-muted">{{ $activity->created_at?->diffForHumans() }}</small>
                                    </div>
                                    <p class="text-muted mb-0 small">
                                        بواسطة: {{ $activity->causer?->name ?? 'النظام' }}
                                        @if ($activity->event)
                                            &bull; الحدث: <span class="badge bg-label-info">{{ $activity->event }}</span>
                                        @endif
                                    </p>
                                </div>
                            </li>
                        @empty
                            <div class="text-center text-muted py-4">
                                <i class="ti ti-history-off fs-2 d-block mb-2"></i>
                                لا توجد نشاطات مسجلة لهذا العميل
                            </div>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Subscription status toggle
            $(document).on('change', '.js-sub-toggle', function() {
                const checkbox = $(this);
                const url = checkbox.data('url');
                checkbox.prop('disabled', true);

                $.ajax({
                    url: url,
                    type: 'PATCH',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                        'Accept': 'application/json'
                    },
                    success: function(res) {
                        checkbox.prop('disabled', false);
                        if (typeof toastr !== 'undefined') {
                            toastr.success(res.message || 'تم تحديث حالة الاشتراك بنجاح');
                        }
                        location.reload();
                    },
                    error: function() {
                        checkbox.prop('disabled', false);
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        if (typeof toastr !== 'undefined') {
                            toastr.error('حدث خطأ أثناء تغيير حالة الاشتراك');
                        }
                    }
                });
            });

            // Delete subscription confirmation
            $(document).on('click', '.js-sub-delete-btn', function(e) {
                e.preventDefault();
                const number = $(this).data('number');

                window.confirmRecordDelete({
                    url: $(this).data('url'),
                    title: 'حذف نهائي للاشتراك؟',
                    text: 'سيتم حذف الاشتراك رقم (' + number + ') نهائياً مع كافة فتراته ومقاعده.',
                    confirmText: 'نعم، حذف نهائي',
                    redirect: @json(route('admin.subscriptions.index'))
                });
            });
        });
    </script>
@endpush
