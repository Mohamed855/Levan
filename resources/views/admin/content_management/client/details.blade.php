@extends('admin.layouts.layoutMaster')

@section('title', 'تفاصيل العميل')

@section('vendor-style')
@endsection

@section('page-style')
<style>
    .client-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .client-info-card {
        background: white;
        border-radius: 10px;
        padding: 30px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-bottom: 20px;
    }

    .client-details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
    }

    .detail-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .detail-label {
        font-size: 0.85rem;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }

    .detail-value {
        font-weight: 600;
        color: #372632;
        font-size: 1rem;
    }

    .logo-container {
        text-align: center;
    }

    .logo-img {
        max-width: 200px;
        height: auto;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }

    .status-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 12px;
        font-size: 0.85rem;
        font-weight: 700;
    }

    .status-badge.active {
        background: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }

    .status-badge.inactive {
        background: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }

    .action-buttons {
        display: flex;
        gap: 10px;
    }

    .btn-back {
        background: #6c757d;
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        border: none;
        cursor: pointer;
        font-weight: 600;
    }

    .btn-back:hover {
        background: #5a6268;
    }
</style>
@endsection

@section('content')
<div class="client-header">
    <h4>تفاصيل العميل</h4>
    <div class="action-buttons">
        <a href="{{ route('admin.client.edit', $client->id) }}" class="btn btn-primary">
            <i class="ti ti-edit me-1"></i> تعديل
        </a>
        <a href="{{ route('admin.client.index') }}" class="btn-back">
            <i class="ti ti-arrow-back me-1"></i> رجوع
        </a>
    </div>
</div>

<div class="client-info-card">
    <div class="client-details-grid">
        <div class="detail-item">
            <span class="detail-label">اسم العميل</span>
            <span class="detail-value">{{ $client->name }}</span>
        </div>

        <div class="detail-item">
            <span class="detail-label">الحالة</span>
            <span class="status-badge {{ $client->status ? 'active' : 'inactive' }}">
                {{ $client->status ? 'نشط' : 'غير نشط' }}
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label">تاريخ الإنشاء</span>
            <span class="detail-value">{{ $client->created_at->format('Y-m-d H:i') }}</span>
        </div>

        <div class="detail-item">
            <span class="detail-label">آخر تحديث</span>
            <span class="detail-value">{{ $client->updated_at->format('Y-m-d H:i') }}</span>
        </div>
    </div>

    @if($client->logo)
    <div class="detail-item logo-container" style="margin-top: 20px;">
        <span class="detail-label">الشعار</span>
        <img src="{{ asset('storage/' . $client->logo) }}" alt="{{ $client->name }}" class="logo-img">
    </div>
    @endif
</div>

@endsection
        padding: 20px;
        background: #f5f5f9;
    }

    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 24px;
    }

    .page-title {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .page-title h4 {
        margin: 0;
        color: #372632;
        font-weight: 700;
        font-size: 1.4rem;
    }

    .back-btn {
        background: #6c757d;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 6px;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .back-btn:hover {
        background: #5a6268;
        transform: translateY(-2px);
    }

    /* Coupon Card Design */
    .coupon-card-wrapper {
        max-width: 900px;
        margin: 0 auto;
    }

    .coupon-showcase {
        background: #e3d8d1;
        border-radius: 12px;
        padding: 30px;
        margin-bottom: 20px;
        position: relative;
        overflow: hidden;
        box-shadow: 0 4px 20px rgba(76, 193, 187, 0.3);
    }

    .coupon-showcase::before {
        content: '';
        position: absolute;
        top: -50px;
        right: -50px;
        width: 200px;
        height: 200px;
        background: rgba(255,255,255,0.1);
        border-radius: 50%;
    }

    .coupon-showcase::after {
        content: '';
        position: absolute;
        bottom: -30px;
        left: -30px;
        width: 150px;
        height: 150px;
        background: rgba(255,255,255,0.08);
        border-radius: 50%;
    }

    .coupon-code-display {
        text-align: center;
        position: relative;
        z-index: 1;
    }

    .coupon-code-display .label {
        color: rgba(255,255,255,0.9);
        font-size: 0.85rem;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .coupon-code-display .code {
        font-size: 1.5rem;
        font-weight: 900;
        color: white;
        letter-spacing: 4px;
        margin: 10px 0;
        font-family: 'Courier New', monospace;
        text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
    }

    .coupon-code-display .discount-value {
        font-size: 1.5rem;
        font-weight: 900;
        color: #fff;
        margin: 15px 0;
        text-shadow: 3px 3px 6px rgba(0,0,0,0.3);
    }

    .coupon-code-display .name {
        color: rgba(255,255,255,0.95);
        font-size: 1.1rem;
        margin-bottom: 20px;
    }

    .copy-code-btn {
        background: white;
        color: #4cc1bb;
        border: none;
        padding: 10px 30px;
        border-radius: 25px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 0.95rem;
    }

    .copy-code-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(255,255,255,0.3);
    }

    /* Details Grid */
    .details-container {
        background: white;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-bottom: 20px;
    }

    .section-title {
        color: #372632;
        font-weight: 700;
        font-size: 1rem;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #e9ecef;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .details-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 16px;
    }

    .detail-item {
        display: flex;
        align-items: start;
        gap: 12px;
        padding: 12px;
        background: #f8f9fa;
        border-radius: 8px;
        transition: all 0.3s;
    }

    .detail-item:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }

    .detail-icon {
        width: 38px;
        height: 38px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.1rem;
        background: #e3d8d1;
        color: white;
        flex-shrink: 0;
    }

    .detail-content {
        flex: 1;
        min-width: 0;
    }

    .detail-label {
        font-size: 0.75rem;
        color: #6c757d;
        margin-bottom: 4px;
        display: block;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .detail-value {
        font-weight: 700;
        color: #372632;
        font-size: 0.95rem;
        word-wrap: break-word;
    }

    /* Status Badges */
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 4px 12px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 700;
    }

    .status-badge.active {
        background: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }

    .status-badge.inactive {
        background: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }

    .status-badge.expired {
        background: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }

    /* Progress Bar */
    .usage-progress {
        margin-top: 8px;
    }

    .progress {
        height: 8px;
        border-radius: 10px;
        background: #e9ecef;
        overflow: hidden;
    }

    .progress-bar {
        height: 100%;
        background: #e3d8d1;
        border-radius: 10px;
        transition: width 0.3s ease;
    }

    .usage-text {
        font-size: 0.75rem;
        color: #6c757d;
        margin-top: 4px;
    }

    /* Admin Info Card */
    .admin-card {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 10px;
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 15px;
        border-right: 4px solid #4cc1bb;
    }

    .admin-card.no-admin {
        border-right-color: #6c757d;
    }

    .admin-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e3d8d1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .admin-info {
        flex: 1;
    }

    .admin-name {
        font-weight: 700;
        color: #372632;
        margin-bottom: 4px;
        font-size: 1rem;
    }

    .admin-email {
        color: #6c757d;
        font-size: 0.85rem;
    }

    .admin-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        background: rgba(76, 193, 187, 0.15);
        color: #4cc1bb;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 700;
        margin-top: 6px;
    }

    /* Tenants List */
    .tenants-list {
        display: grid;
        gap: 16px;
        margin-top: 16px;
    }

    .tenant-card {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 10px;
        padding: 20px;
        border-right: 4px solid #4cc1bb;
        transition: all 0.3s;
    }

    .tenant-card:hover {
        transform: translateX(-5px);
        box-shadow: 0 4px 12px rgba(76, 193, 187, 0.15);
    }

    .tenant-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 16px;
        padding-bottom: 16px;
        border-bottom: 2px solid #e9ecef;
    }

    .tenant-avatar {
        width: 55px;
        height: 55px;
        border-radius: 50%;
        background: #e3d8d1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.3rem;
        font-weight: 700;
        flex-shrink: 0;
    }

    .tenant-info {
        flex: 1;
    }

    .tenant-name {
        font-weight: 700;
        color: #372632;
        margin-bottom: 4px;
        font-size: 1.05rem;
    }

    .tenant-id {
        color: #6c757d;
        font-size: 0.8rem;
        font-family: 'Courier New', monospace;
    }

    .tenant-usage-badge {
        background: rgba(76, 193, 187, 0.15);
        color: #4cc1bb;
        padding: 6px 14px;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 700;
        white-space: nowrap;
    }

    /* Bills List */
    .bills-list {
        display: grid;
        gap: 10px;
    }

    .bill-item {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s;
        cursor: pointer;
        border: 2px solid transparent;
    }

    .bill-item:hover {
        background: #e9ecef;
        border-color: #4cc1bb;
        transform: translateX(-3px);
    }

    .bill-icon {
        width: 40px;
        height: 40px;
        border-radius: 8px;
        background: #e3d8d1;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 1.1rem;
        flex-shrink: 0;
    }

    .bill-details {
        flex: 1;
        min-width: 0;
    }

    .bill-number {
        font-weight: 700;
        color: #372632;
        font-size: 0.9rem;
        margin-bottom: 4px;
        font-family: 'Courier New', monospace;
    }

    .bill-meta {
        display: flex;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        font-size: 0.75rem;
        color: #6c757d;
    }

    .bill-meta-item {
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .bill-amount {
        font-weight: 700;
        color: #28a745;
        font-size: 1rem;
        white-space: nowrap;
    }

    .bill-status {
        padding: 4px 10px;
        border-radius: 10px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    .bill-status.paid {
        background: rgba(40, 167, 69, 0.15);
        color: #28a745;
    }

    .bill-status.pending {
        background: rgba(255, 193, 7, 0.15);
        color: #ffc107;
    }

    .bill-status.cancelled {
        background: rgba(220, 53, 69, 0.15);
        color: #dc3545;
    }

    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: #6c757d;
    }

    .empty-state i {
        font-size: 3rem;
        margin-bottom: 12px;
        opacity: 0.5;
    }

    .empty-state p {
        margin: 0;
        font-size: 0.95rem;
    }

    /* Actions */
    .actions-bar {
        display: flex;
        gap: 12px;
        margin-top: 20px;
    }

    .btn-action {
        flex: 1;
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        font-size: 0.9rem;
    }

    .btn-edit {
        background: #e3d8d1;
        color: white;
    }

    .btn-edit:hover {
        opacity: 0.9;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(76, 193, 187, 0.3);
    }

    .btn-delete {
        background: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
    }

    .btn-toggle {
        background: #ffc107;
        color: #372632;
    }

    .btn-toggle:hover {
        background: #e0a800;
        transform: translateY(-2px);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .details-grid {
            grid-template-columns: 1fr;
        }

        .coupon-code-display .code {
            font-size: 1.8rem;
            letter-spacing: 2px;
        }

        .coupon-code-display .discount-value {
            font-size: 2rem;
        }

        .actions-bar {
            flex-direction: column;
        }

        .page-header {
            flex-direction: column;
            gap: 12px;
        }

        .tenant-header {
            flex-wrap: wrap;
        }

        .bill-item {
            flex-wrap: wrap;
        }
    }
</style>
@endsection

@section('content')
<div class="client-header">
    <h4>تفاصيل العميل</h4>
    <div class="action-buttons">
        <a href="{{ route('admin.client.edit', $client->id) }}" class="btn btn-primary">
            <i class="ti ti-edit me-1"></i> تعديل
        </a>
        <a href="{{ route('admin.client.index') }}" class="btn-back">
            <i class="ti ti-arrow-back me-1"></i> رجوع
        </a>
    </div>
</div>

<div class="client-info-card">
    <div class="client-details-grid">
        <div class="detail-item">
            <span class="detail-label">اسم العميل</span>
            <span class="detail-value">{{ $client->name }}</span>
        </div>

        <div class="detail-item">
            <span class="detail-label">الحالة</span>
            <span class="status-badge {{ $client->status ? 'active' : 'inactive' }}">
                {{ $client->status ? 'نشط' : 'غير نشط' }}
            </span>
        </div>

        <div class="detail-item">
            <span class="detail-label">تاريخ الإنشاء</span>
            <span class="detail-value">{{ $client->created_at->format('Y-m-d H:i') }}</span>
        </div>

        <div class="detail-item">
            <span class="detail-label">آخر تحديث</span>
            <span class="detail-value">{{ $client->updated_at->format('Y-m-d H:i') }}</span>
        </div>
    </div>

    @if($client->logo)
    <div class="detail-item logo-container" style="margin-top: 20px;">
        <span class="detail-label">الشعار</span>
        <img src="{{ asset('storage/' . $client->logo) }}" alt="{{ $client->name }}" class="logo-img">
    </div>
    @endif
</div>

@endsection
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
