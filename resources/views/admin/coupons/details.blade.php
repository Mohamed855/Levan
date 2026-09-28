@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل الكوبون')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('page-style')
    @vite(['resources/scss/admin/pages/coupons-details.scss'])
@endsection

@section('content')
<div class="coupon-details-page">
    <div class="page-header">
        <div class="page-title">
            <i class="ti ti-discount-2" style="font-size: 2rem; color: #4cc1bb;"></i>
            <h4>تفاصيل الكوبون</h4>
        </div>
        <button type="button" class="back-btn" onclick="window.history.back()">
            <i class="ti ti-arrow-right"></i>
            <span>رجوع</span>
        </button>
    </div>

    <div class="coupon-card-wrapper">
        {{-- Coupon Showcase --}}
        <div class="coupon-showcase">
            <div class="coupon-code-display">
                <div class="label">كود الكوبون</div>
                <div class="code">{{ $couponDetails->code }}</div>
                
                <div class="discount-value">
                    @if($couponDetails->discount_type === 'fixed')
                        {{ number_format($couponDetails->discount_value, 0) }} ر.س
                    @else
                        {{ number_format($couponDetails->discount_value, 0) }}%
                    @endif
                </div>
                
                <div class="name">{{ $couponDetails->name }}</div>
                
                <button type="button" class="copy-code-btn" onclick="copyCode('{{ $couponDetails->code }}')">
                    <i class="ti ti-copy"></i>
                    <span>نسخ الكود</span>
                </button>
            </div>
        </div>

        {{-- Details Section --}}
        <div class="details-container">
            <h5 class="section-title">
                <i class="ti ti-info-circle"></i>
                معلومات الكوبون
            </h5>

            <div class="details-grid">
                {{-- Status --}}
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ti ti-circle-check"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">الحالة</span>
                        <div class="detail-value">
                            @if($couponDetails->is_active)
                                @if($couponDetails->expiry_date && \Carbon\Carbon::parse($couponDetails->expiry_date)->isPast())
                                    <span class="status-badge expired">
                                        <i class="ti ti-clock-exclamation"></i>
                                        منتهي
                                    </span>
                                @else
                                    <span class="status-badge active">
                                        <i class="ti ti-circle-check"></i>
                                        نشط
                                    </span>
                                @endif
                            @else
                                <span class="status-badge inactive">
                                    <i class="ti ti-circle-x"></i>
                                    غير نشط
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Discount Type --}}
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ti ti-percentage"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">نوع الخصم</span>
                        <div class="detail-value">
                            @if($couponDetails->discount_type === 'fixed')
                                خصم ثابت
                            @else
                                نسبة مئوية
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Max Discount --}}
                @if($couponDetails->max_discount)
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ti ti-coin"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">الحد الأقصى للخصم</span>
                        <div class="detail-value">{{ number_format($couponDetails->max_discount, 2) }} ر.س</div>
                    </div>
                </div>
                @endif

                {{-- Expiry Date --}}
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ti ti-calendar-event"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">تاريخ الانتهاء</span>
                        <div class="detail-value">
                            @if($couponDetails->expiry_date)
                                {{ \Carbon\Carbon::parse($couponDetails->expiry_date)->format('Y-m-d') }}
                                <small class="text-muted d-block">
                                    @if(\Carbon\Carbon::parse($couponDetails->expiry_date)->isPast())
                                        (منتهي)
                                    @else
                                        ({{ \Carbon\Carbon::parse($couponDetails->expiry_date)->diffForHumans() }})
                                    @endif
                                </small>
                            @else
                                غير محدد
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Usage Limit --}}
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ti ti-hash"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">حد الاستخدام</span>
                        <div class="detail-value">
                            {{ $couponDetails->usage_limit ?? 'غير محدود' }}
                        </div>
                    </div>
                </div>

                {{-- Used Count --}}
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ti ti-users"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">مرات الاستخدام</span>
                        <div class="detail-value">
                            {{ $couponDetails->used_count }} مرة
                            @if($couponDetails->usage_limit)
                                <div class="usage-progress">
                                    <div class="progress">
                                        <div class="progress-bar" style="width: {{ ($couponDetails->used_count / $couponDetails->usage_limit) * 100 }}%"></div>
                                    </div>
                                    <div class="usage-text">
                                        {{ $couponDetails->used_count }} / {{ $couponDetails->usage_limit }} 
                                        ({{ round(($couponDetails->used_count / $couponDetails->usage_limit) * 100, 1) }}%)
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Created Date --}}
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ti ti-calendar-plus"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">تاريخ الإنشاء</span>
                        <div class="detail-value">
                            {{ \Carbon\Carbon::parse($couponDetails->created_at)->format('Y-m-d H:i') }}
                            <small class="text-muted d-block">
                                {{ \Carbon\Carbon::parse($couponDetails->created_at)->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                </div>

                {{-- Updated Date --}}
                @if($couponDetails->updated_at && $couponDetails->updated_at != $couponDetails->created_at)
                <div class="detail-item">
                    <div class="detail-icon">
                        <i class="ti ti-edit"></i>
                    </div>
                    <div class="detail-content">
                        <span class="detail-label">آخر تحديث</span>
                        <div class="detail-value">
                            {{ \Carbon\Carbon::parse($couponDetails->updated_at)->format('Y-m-d H:i') }}
                            <small class="text-muted d-block">
                                {{ \Carbon\Carbon::parse($couponDetails->updated_at)->diffForHumans() }}
                            </small>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Admin Info --}}
        <div class="details-container">
            <h5 class="section-title">
                <i class="ti ti-user-shield"></i>
                معلومات المستخدم المسند له الكوبون
            </h5>

            @if($couponDetails->admin_user)
                <div class="admin-card">
                    <div class="admin-avatar">
                        {{ mb_substr($couponDetails->admin_user->name, 0, 1) }}
                    </div>
                    <div class="admin-info">
                        <div class="admin-name">{{ $couponDetails->admin_user->name }}</div>
                        <div class="admin-email">{{ $couponDetails->admin_user->email }}</div>
                        @if($couponDetails->admin_user->is_super_admin)
                            <span class="admin-badge">
                                <i class="ti ti-crown"></i>
                                مدير عام
                            </span>
                        @endif
                    </div>
                </div>
            @else
                <div class="admin-card no-admin">
                    <div class="admin-avatar" style="background: #6c757d;">
                        <i class="ti ti-user-off"></i>
                    </div>
                    <div class="admin-info">
                        <div class="admin-name">غير مسند لمستخدم</div>
                        <div class="admin-email">لم يتم تعيين مستخدم لهذا الكوبون</div>
                    </div>
                </div>
            @endif
        </div>

        {{-- Tenants Who Used The Coupon --}}
        <div class="details-container">
            <h5 class="section-title">
                <i class="ti ti-building"></i>
                العملاء الذين استخدموا الكوبون
                <span class="tenant-usage-badge" style="margin-right: auto;">
                    {{ $tenants->count() }} {{ $tenants->count() == 1 ? 'عميل' : 'عملاء' }}
                </span>
            </h5>

            @if($tenants->count() > 0)
                <div class="tenants-list">
                    @foreach($tenants as $tenant)
                        <div class="tenant-card">
                            <div class="tenant-header">
                                <div class="tenant-avatar">
                                    {{ mb_substr($tenant->company_name ?? $tenant->id, 0, 1) }}
                                </div>
                                <div class="tenant-info">
                                    <div class="tenant-name">{{ $tenant->company_name ?? 'عميل بدون اسم' }}</div>
                                    <div class="tenant-id">{{ $tenant->id }}</div>
                                </div>
                                <span class="tenant-usage-badge">
                                    <i class="ti ti-receipt"></i>
                                    {{ $tenant->bills->count() }} {{ $tenant->bills->count() == 1 ? 'فاتورة' : 'فواتير' }}
                                </span>
                            </div>

                            {{-- Bills List --}}
                            <div class="bills-list">
                                @foreach($tenant->bills as $bill)
                                    <div class="bill-item" onclick="window.open('{{ route('admin.bills.show', $bill->id) }}', '_blank')">
                                        <div class="bill-icon">
                                            <i class="ti ti-file-invoice"></i>
                                        </div>
                                        <div class="bill-details">
                                            <div class="bill-number">{{ $bill->bill_number }}</div>
                                            <div class="bill-meta">
                                                <span class="bill-meta-item">
                                                    <i class="ti ti-calendar"></i>
                                                    {{ \Carbon\Carbon::parse($bill->created_at)->format('Y-m-d') }}
                                                </span>
                                                @if($bill->paid_at)
                                                    <span class="bill-meta-item">
                                                        <i class="ti ti-clock"></i>
                                                        {{ \Carbon\Carbon::parse($bill->paid_at)->format('H:i') }}
                                                    </span>
                                                @endif
                                                <span class="bill-meta-item">
                                                    <i class="ti ti-credit-card"></i>
                                                    {{ $bill->payment_method == 'online' ? 'دفع إلكتروني' : 'تحويل بنكي' }}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="bill-amount">
                                            {{ number_format($bill->amount, 2) }} ر.س
                                        </div>
                                        <span class="bill-status {{ $bill->status }}">
                                            @if($bill->status == 'paid')
                                                <i class="ti ti-check"></i> مدفوع
                                            @elseif($bill->status == 'pending')
                                                <i class="ti ti-clock"></i> قيد الانتظار
                                            @else
                                                <i class="ti ti-x"></i> ملغي
                                            @endif
                                        </span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="empty-state">
                    <i class="ti ti-users-off"></i>
                    <p>لم يستخدم أي عميل هذا الكوبون حتى الآن</p>
                </div>
            @endif
        </div>

        {{-- Actions --}}
        {{-- <div class="actions-bar">
            <button type="button" class="btn-action btn-edit" onclick="window.location.href='{{ route('admin.coupons.edit', $couponDetails->id) }}'">
                <i class="ti ti-edit"></i>
                <span>تعديل الكوبون</span>
            </button>
            
            <button type="button" class="btn-action btn-toggle" onclick="toggleStatus({{ $couponDetails->id }}, {{ $couponDetails->is_active ? 'false' : 'true' }})">
                <i class="ti ti-toggle-{{ $couponDetails->is_active ? 'right' : 'left' }}"></i>
                <span>{{ $couponDetails->is_active ? 'تعطيل' : 'تفعيل' }}</span>
            </button>
            
            <button type="button" class="btn-action btn-delete" onclick="deleteCoupon({{ $couponDetails->id }})">
                <i class="ti ti-trash"></i>
                <span>حذف</span>
            </button>
        </div> --}}
    </div>
</div>
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-script')
<script>
    function copyCode(code) {
        navigator.clipboard.writeText(code).then(() => {
            const btn = event.target.closest('button');
            const originalText = btn.innerHTML;
            
            btn.innerHTML = '<i class="ti ti-check"></i> تم النسخ!';
            btn.style.background = '#28a745';
            
            setTimeout(() => {
                btn.innerHTML = originalText;
                btn.style.background = 'white';
            }, 2000);
        }).catch(err => {
            alert('فشل نسخ الكود');
        });
    }

    function toggleStatus(id, newStatus) {
        Swal.fire({
            title: newStatus ? 'تفعيل الكوبون؟' : 'تعطيل الكوبون؟',
            text: 'هل أنت متأكد من تغيير حالة الكوبون؟',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'نعم، متأكد',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#4cc1bb',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/coupons/${id}/toggle`,
                    method: 'POST',
                    data: {
                        _token: '{{ csrf_token() }}',
                        is_active: newStatus
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'تم بنجاح!',
                            text: 'تم تغيير حالة الكوبون',
                            icon: 'success',
                            confirmButtonColor: '#4cc1bb'
                        }).then(() => {
                            location.reload();
                        });
                    },
                    error: function() {
                        Swal.fire('خطأ!', 'حدث خطأ أثناء تغيير الحالة', 'error');
                    }
                });
            }
        });
    }

    function deleteCoupon(id) {
        Swal.fire({
            title: 'حذف الكوبون؟',
            text: 'هل أنت متأكد؟ لن تتمكن من التراجع عن هذا!',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'نعم، احذف',
            cancelButtonText: 'إلغاء',
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: `/admin/coupons/${id}`,
                    method: 'DELETE',
                    data: {
                        _token: '{{ csrf_token() }}'
                    },
                    success: function(response) {
                        Swal.fire({
                            title: 'تم الحذف!',
                            text: 'تم حذف الكوبون بنجاح',
                            icon: 'success',
                            confirmButtonColor: '#4cc1bb'
                        }).then(() => {
                            window.location.href = '{{ route("admin.coupons.index") }}';
                        });
                    },
                    error: function() {
                        Swal.fire('خطأ!', 'حدث خطأ أثناء الحذف', 'error');
                    }
                });
            }
        });
    }
</script>
@endsection
