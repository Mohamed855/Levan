@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل الشركة — ' . $company->company_name)

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
                        <a class="py-3 nav-link fw-bold active m-0" data-bs-toggle="tab" href="#companyDetails">
                            <i class="ti ti-building ti-sm me-1"></i><span>بيانات الشركة</span>
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#companySubscriptions">
                            <i class="ti ti-packages ti-sm me-1"></i><span>سجل الاشتراكات</span>
                            @if ($company->subscriptions->count() > 0)
                                <span
                                    class="badge bg-label-primary rounded-pill ms-auto">{{ $company->subscriptions->count() }}</span>
                            @endif
                        </a>
                    </li>

                    <li class="card rounded-0 mb-3 nav-item shadow-none">
                        <a class="py-3 nav-link fw-bold m-0" data-bs-toggle="tab" href="#companyActivities">
                            <i class="ti ti-activity ti-sm me-1"></i><span>أحدث الأنشطة</span>
                            @if ($activities->count() > 0)
                                <span
                                    class="badge bg-label-secondary rounded-pill ms-auto">{{ $activities->count() }}</span>
                            @endif
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        {{-- محتوى التبويبات --}}
        <div class="col-12 col-md-9 col-xl-9 order-1 order-md-0 mb-5">
            <div class="tab-content p-0">
                {{-- 1. تبويب بيانات الشركة --}}
                <div class="tab-pane fade show active" id="companyDetails">
                    <div class="card">
                        <div class="card-header py-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-building text-primary me-2"></i>
                                بيانات الشركة الأساسية
                            </h6>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ url('/' . $company->id . '/dashboard') }}" target="_blank"
                                    class="btn btn-sm btn-label-success">
                                    <i class="ti ti-dashboard me-1"></i>لوحة التحكم
                                </a>
                                @can('companies.edit')
                                    <a href="{{ route('admin.companies.edit', $company->id) }}"
                                        class="btn btn-sm btn-label-primary">
                                        <i class="ti ti-edit me-1"></i>تعديل
                                    </a>
                                @endcan
                                <a href="{{ route('admin.companies.index') }}" class="btn btn-secondary">
                                    <i class="ti ti-arrow-right me-1"></i>رجوع
                                </a>
                            </div>
                        </div>

                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            {{-- Header Card --}}
                            <div class="card border mb-4">
                                <div class="card-body d-flex align-items-center flex-wrap gap-3">
                                    <div class="flex-shrink-0">
                                        <div
                                            class="avatar avatar-xl bg-label-primary rounded p-2 d-flex align-items-center justify-content-center">
                                            <i class="ti ti-building fs-1 text-primary"></i>
                                        </div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                            <h4 class="fw-bold mb-0 text-primary">{{ $company->company_name }}</h4>
                                            @php
                                                $statusBadges = [
                                                    'active' => ['label' => 'نشط', 'class' => 'bg-label-success'],
                                                    'pending' => [
                                                        'label' => 'قيد المراجعة',
                                                        'class' => 'bg-label-warning',
                                                    ],
                                                    'suspended' => ['label' => 'موقوف', 'class' => 'bg-label-danger'],
                                                ];
                                                $badge = $statusBadges[$company->status] ?? [
                                                    'label' => $company->status,
                                                    'class' => 'bg-label-secondary',
                                                ];
                                            @endphp
                                            <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                        </div>
                                        <p class="text-muted mb-0 small">
                                            معرف الشركة: <span
                                                class="fw-semibold font-monospace text-dark">{{ $company->id }}</span>
                                            &bull; تاريخ التسجيل: {{ $company->created_at?->format('Y-m-d') }}
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-4">
                                {{-- البيانات الأساسية --}}
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-dark mb-3"><i class="ti ti-id me-1 text-primary"></i>البيانات
                                        التجارية</h6>
                                    <ul class="list-group list-group-flush border rounded">
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">السجل التجاري</span>
                                            <span
                                                class="fw-semibold text-dark">{{ $company->commercial_number ?: '-' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">الرقم الضريبي</span>
                                            <span class="fw-semibold text-dark">{{ $company->tax_number ?: '-' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">المدينة</span>
                                            <span class="fw-semibold text-dark">{{ $company->city?->name ?: '-' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">العنوان</span>
                                            <span class="fw-semibold text-dark">{{ $company->address ?: '-' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">رقم الإصدار</span>
                                            <span
                                                class="badge bg-label-info">{{ $company->version?->version ?: '-' }}</span>
                                        </li>
                                    </ul>
                                </div>

                                {{-- المسؤول الرئيسي --}}
                                <div class="col-md-6">
                                    <h6 class="fw-bold text-dark mb-3"><i class="ti ti-user me-1 text-primary"></i>المسؤول
                                        الإداري الرئيسي</h6>
                                    <ul class="list-group list-group-flush border rounded">
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">الاسم</span>
                                            <span
                                                class="fw-semibold text-dark">{{ $company->primary_contact_name ?: '-' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">البريد الإلكتروني</span>
                                            <span
                                                class="fw-semibold text-dark">{{ $company->primary_contact_email ?: '-' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">رقم الجوال</span>
                                            <span
                                                class="fw-semibold text-dark">{{ $company->primary_contact_phone ?: '-' }}</span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">المعرف الشامل للمستخدم</span>
                                            <span
                                                class="text-dark small font-monospace">{{ $company->primary_user_global_uid ?: '-' }}</span>
                                        </li>
                                    </ul>

                                    {{-- إعدادات الشركة --}}
                                    <h6 class="fw-bold text-dark mt-4 mb-3"><i
                                            class="ti ti-settings me-1 text-primary"></i>إعدادات الشركة الخاصة</h6>
                                    <ul class="list-group list-group-flush border rounded">
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">تنبيهات البريد الإلكتروني</span>
                                            <span
                                                class="badge {{ $settings->email_notifications_enabled ? 'bg-label-success' : 'bg-label-secondary' }}">
                                                {{ $settings->email_notifications_enabled ? 'مفعلة' : 'معطلة' }}
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">التحقق الثنائي (2FA)</span>
                                            <span
                                                class="badge {{ $settings->two_factor_enabled ? 'bg-label-success' : 'bg-label-secondary' }}">
                                                {{ $settings->two_factor_enabled ? 'مفعل' : 'معطل' }}
                                            </span>
                                        </li>
                                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                            <span class="text-muted">التكاملات الخارجية</span>
                                            <span
                                                class="badge {{ $settings->external_integrations_enabled ? 'bg-label-success' : 'bg-label-secondary' }}">
                                                {{ $settings->external_integrations_enabled ? 'مفعلة' : 'معطلة' }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 2. تبويب الاشتراكات --}}
                <div class="tab-pane fade" id="companySubscriptions">
                    <div class="card">
                        <div class="card-header py-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-packages text-primary me-2"></i>
                                اشتراكات الشركة
                            </h6>
                            @can('subscriptions.create')
                                @if (!$company->lastSubscription || $company->lastSubscription->status->value === 'expired')
                                    <a href="{{ route('admin.subscriptions.create') }}?company_id={{ $company->id }}"
                                        class="btn btn-sm btn-primary">
                                        <i class="ti ti-plus me-1"></i>إضافة اشتراك جديد
                                    </a>
                                @endif
                            @endcan
                        </div>
                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped w-100">
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
                                            <th>الإجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($company->subscriptions as $sub)
                                            @php
                                                $period =
                                                    $sub->currentPeriod ??
                                                    $sub->periods->sortByDesc('period_end')->first();
                                                $days = app(
                                                    \App\Services\shared\Subscription\SubscriptionManager::class,
                                                )->calculateDaysRemaining($period);
                                                $isActive =
                                                    $sub->status ===
                                                    \App\Enums\Admin\Subscriptions\SubscriptionStatus::ACTIVE;
                                            @endphp
                                            <tr>
                                                <td><span
                                                        class="fw-bold text-primary">{{ $sub->subscription_number }}</span>
                                                </td>
                                                <td>{{ $sub->plan?->name ?: '-' }}</td>
                                                <td>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <span class="badge bg-label-info"><i
                                                                class="ti ti-users ti-xs me-1"></i>{{ $sub->total_users }}</span>
                                                        <span class="badge bg-label-secondary"><i
                                                                class="ti ti-building ti-xs me-1"></i>{{ $sub->plan?->properties_limit ?? '∞' }}</span>
                                                        <span class="badge bg-label-secondary"><i
                                                                class="ti ti-database ti-xs me-1"></i>{{ $sub->plan?->storage_quota_gb ? $sub->plan->storage_quota_gb . 'GB' : '∞' }}</span>
                                                    </div>
                                                </td>
                                                <td>{{ $sub->starts_at?->format('Y-m-d') ?: '-' }}</td>
                                                <td>{{ $period?->period_end?->format('Y-m-d') ?: ($sub->ends_at?->format('Y-m-d') ?: '-') }}
                                                </td>
                                                <td>
                                                    @if ($days === null)
                                                        <span class="badge bg-label-secondary">-</span>
                                                    @elseif ($days <= 0)
                                                        <span class="badge bg-label-danger">منتهي</span>
                                                    @elseif ($days <= 7)
                                                        <span class="badge bg-label-danger fw-bold">{{ $days }}
                                                            يوم</span>
                                                    @elseif ($days <= 30)
                                                        <span
                                                            class="badge bg-label-warning fw-semibold">{{ $days }}
                                                            يوماً</span>
                                                    @else
                                                        <span class="badge bg-label-success">{{ $days }}
                                                            يوماً</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    <span
                                                        class="badge bg-{{ $sub->status->color() }}">{{ $sub->status->label() }}</span>
                                                </td>
                                                <td>
                                                    @can('subscriptions.toggle_status')
                                                        <div class="form-check form-switch mb-0">
                                                            <input type="checkbox" class="form-check-input js-sub-toggle"
                                                                data-url="{{ route('admin.subscriptions.toggle-status', $sub->id) }}"
                                                                {{ $isActive ? 'checked' : '' }}>
                                                        </div>
                                                    @else
                                                        -
                                                    @endcan
                                                </td>
                                                <td>
                                                    @can('subscriptions.delete')
                                                        <button type="button"
                                                            class="btn btn-icon btn-label-danger js-sub-delete-btn"
                                                            data-id="{{ $sub->id }}"
                                                            data-number="{{ $sub->subscription_number }}">
                                                            <i class="ti ti-trash"></i>
                                                        </button>
                                                        <form id="delete-sub-form-{{ $sub->id }}"
                                                            action="{{ route('admin.subscriptions.destroy', $sub->id) }}"
                                                            method="POST" style="display:none;">
                                                            @csrf
                                                            @method('DELETE')
                                                        </form>
                                                    @endcan
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center py-4 text-muted">لا توجد اشتراكات
                                                    مسجلة لهذه الشركة.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- 3. تبويب أحدث الأنشطة --}}
                <div class="tab-pane fade" id="companyActivities">
                    <div class="card">
                        <div class="card-header py-4">
                            <h6 class="card-title mb-0">
                                <i class="ti ti-activity text-primary me-2"></i>
                                سجل الأنشطة والعمليات للشركة
                            </h6>
                        </div>
                        <div class="border-1 border-light border-dashed mb-2"></div>
                        <div class="card-body">
                            <ul class="timeline ms-2">
                                @forelse ($activities as $activity)
                                    <li class="timeline-item timeline-item-transparent mb-4">
                                        <span class="timeline-point timeline-point-primary"></span>
                                        <div class="timeline-event">
                                            <div class="timeline-header mb-1">
                                                <h6 class="mb-0 fw-semibold">{{ $activity->description }}</h6>
                                                <small
                                                    class="text-muted">{{ $activity->created_at?->diffForHumans() }}</small>
                                            </div>
                                            <p class="text-muted mb-1 small">
                                                بواسطة: {{ $activity->causer?->name ?? 'النظام' }}
                                                @if ($activity->event)
                                                    &bull; الحدث: <span
                                                        class="badge bg-label-info">{{ $activity->event }}</span>
                                                @endif
                                            </p>
                                        </div>
                                    </li>
                                @empty
                                    <li class="text-muted text-center py-4">لا توجد أنشطة مسجلة لهذه الشركة بعد.</li>
                                @endforelse
                            </ul>
                        </div>
                    </div>
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
                        Swal.fire({
                            icon: 'success',
                            title: res.message || 'تم تحديث حالة الاشتراك بنجاح',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 2500
                        });
                        location.reload();
                    },
                    error: function() {
                        checkbox.prop('disabled', false);
                        checkbox.prop('checked', !checkbox.prop('checked'));
                        Swal.fire({
                            icon: 'error',
                            title: 'حدث خطأ أثناء تغيير حالة الاشتراك',
                            toast: true,
                            position: 'top-end',
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            });

            // Delete subscription confirmation
            $(document).on('click', '.js-sub-delete-btn', function(e) {
                e.preventDefault();
                const id = $(this).data('id');
                const number = $(this).data('number');
                const form = document.getElementById('delete-sub-form-' + id);

                Swal.fire({
                    title: 'هل أنت متأكد من حذف الاشتراك نهائياً؟',
                    text: 'سيتم حذف الاشتراك رقم (' + number + ') نهائياً مع كافة فتراته ومقاعده.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'نعم، حذف نهائي',
                    cancelButtonText: 'إلغاء',
                    customClass: {
                        confirmButton: 'btn btn-danger me-3',
                        cancelButton: 'btn btn-secondary'
                    },
                    buttonsStyling: false
                }).then(function(result) {
                    if (result.isConfirmed && form) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
