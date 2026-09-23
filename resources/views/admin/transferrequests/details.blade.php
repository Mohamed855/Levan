@extends('admin.layouts.layoutMaster')
@section('title', 'تفاصيل طلب الاشتراك')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-style')
<style>
    .transfer-requests-page {
        padding: 20px;
        background: #f5f5f9;
    }

    .swal2-actions .swal2-deny{

        display:none !important;

    }
    .page-header {
        background: #e3d8d1 !important;
        border-radius: 12px;
        padding: 13px 30px;
        margin-bottom: 24px;
        color: white;
        box-shadow: 0 4px 12px rgba(76, 193, 187, 0.2);
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-header h4 {
        margin: 0;
        font-weight: 600;
        font-size: 1.5rem;
        color: white;
    }

    .page-header p {
        margin: 8px 0 0 0;
        opacity: 0.95;
        font-size: 0.95rem;
    }

    .back-btn {
        background: rgba(255, 255, 255, 0.2);
        color: white;
        border: none;
        padding: 6px 20px;
        border-radius: 8px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 600;
    }

    .back-btn:hover {
        background: rgba(255, 255, 255, 0.3);
        transform: translateY(-2px);
    }

    .request-card {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.06);
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
        margin-bottom: 20px;
    }

    .request-header {
        background: #e3d8d1 !important;
        padding: 14px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .subscription-number {
        color: white;
        font-size: 1.3rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .status-badge {
        padding: 8px 20px;
        border-radius: 20px;
        font-size: 0.9rem;
        font-weight: 600;
        background: rgba(255, 255, 255, 0.25);
        color: white;
        backdrop-filter: blur(10px);
    }

    .request-body {
        padding: 30px;
    }

    .section-title {
        color: #372632;
        font-weight: 700;
        font-size: 1.1rem;
        margin-bottom: 20px;
        padding-bottom: 10px;
        border-bottom: 2px solid #4cc1bb;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .info-item {
        display: flex;
        align-items: start;
        gap: 15px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 10px;
        transition: all 0.3s;
    }

    .info-item:hover {
        background: #e9ecef;
        transform: translateY(-2px);
    }

    .info-item .icon {
        width: 45px;
        height: 45px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        background: #e3d8d1;
        color: white;
        flex-shrink: 0;
    }

    .info-item .content {
        flex: 1;
    }

    .info-item .label {
        font-size: 0.85rem;
        color: #6c757d;
        margin-bottom: 5px;
        display: block;
    }

    .info-item .value {
        font-weight: 700;
        color: #372632;
        font-size: 1rem;
    }

    .plan-details {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border-radius: 12px;
        padding: 25px;
        margin-bottom: 30px;
        border-right: 5px solid #4cc1bb;
        position: relative;
        overflow: hidden;
    }

    .plan-details::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 100px;
        height: 100px;
        background: rgba(76, 193, 187, 0.1);
        border-radius: 50%;
        transform: translate(30%, -30%);
    }

    .plan-details h6 {
        color: #372632;
        font-weight: 700;
        margin: 0 0 15px 0;
        font-size: 1.2rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .plan-description {
        color: #6c757d;
        margin-bottom: 20px;
        font-size: 0.95rem;
    }

    .plan-features {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 12px;
    }

    .plan-feature {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #495057;
        font-size: 0.95rem;
        padding: 8px 12px;
        background: white;
        border-radius: 6px;
        transition: all 0.3s;
    }

    .plan-feature:hover {
        background: rgba(76, 193, 187, 0.1);
        transform: translateX(-5px);
    }

    .plan-feature i {
        color: #28a745;
        font-size: 1.1rem;
    }

    .period-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 15px;
        border-radius: 15px;
        font-size: 0.85rem;
        font-weight: 600;
        background: rgba(76, 193, 187, 0.15);
        color: #4cc1bb;
    }

    .attachment-section {
        margin-top: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #fff9e6 0%, #fff3cd 100%);
        border-radius: 10px;
        border: 2px dashed #ffc107;
    }

    .attachment-section h6 {
        color: #372632;
        font-weight: 700;
        margin: 0 0 15px 0;
        font-size: 1rem;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .attachment-preview {
        display: flex;
        flex-wrap: wrap;
        gap: 15px;
    }

    .attachment-item {
        position: relative;
        width: 120px;
        height: 120px;
        border-radius: 10px;
        overflow: hidden;
        border: 3px solid #fff;
        box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15);
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .attachment-item:hover {
        border-color: #4cc1bb;
        transform: scale(1.1);
        box-shadow: 0 5px 20px rgba(76, 193, 187, 0.4);
        z-index: 10;
    }

    .attachment-item img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .actions-bar {
        display: flex;
        gap: 15px;
        padding-top: 25px;
        border-top: 2px solid #e9ecef;
        margin-top: 30px;
    }

    .btn-approve {
        background: #e3d8d1 !important;
        border: none;
        color: white;
        padding: 7px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-approve:hover {
        opacity: 0.9;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(76, 193, 187, 0.4);
    }

    .btn-reject {
        background: #372632 !important;
        border: none;
        color: white;
        padding: 7px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }

    .btn-reject:hover {
        background: #1a1d2e !important;
        transform: translateY(-3px);
        box-shadow: 0 6px 20px rgba(47, 51, 73, 0.4);
    }

    .timeline-section {
        background: white;
        border-radius: 12px;
        padding: 25px;
        border: 1px solid #e9ecef;
    }

    .timeline-item {
        display: flex;
        gap: 15px;
        padding: 15px;
        border-right: 3px solid #e9ecef;
        position: relative;
    }

    .timeline-item:last-child {
        border-right: none;
    }

    .timeline-dot {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: #4cc1bb;
        position: absolute;
        right: -7.5px;
        top: 20px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .info-grid {
            grid-template-columns: 1fr;
        }

        .plan-features {
            grid-template-columns: 1fr;
        }

        .actions-bar {
            flex-direction: column;
        }

        .page-header {
            flex-direction: column;
            gap: 15px;
            text-align: center;
        }
    }

</style>
@endsection

@section('content')
<div class="transfer-requests-page">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h4>
                <i class="ti ti-file-invoice me-2"></i>
                تفاصيل طلب الاشتراك
            </h4>
            <p>معلومات كاملة عن طلب التحويل البنكي</p>
        </div>
        <button type="button" class="back-btn" onclick="window.history.back()">
            <i class="ti ti-arrow-right"></i>
            <span>رجوع</span>
        </button>
    </div>

    {{-- Request Card --}}
    <div class="request-card">
        {{-- Card Header --}}
        <div class="request-header">
            <div class="subscription-number">
                <i class="ti ti-file-text"></i>
                <span>{{ $subscriptionDetails->subscription_number }}</span>
            </div>
            <div class="status-badge">
                @if($subscriptionDetails->status->value === 'suspended')
                <i class="ti ti-player-pause me-1"></i> معلق
                @elseif($subscriptionDetails->status->value === 'pending')
                <i class="ti ti-clock me-1"></i> قيد الانتظار
                @elseif($subscriptionDetails->status->value === 'active')
                <i class="ti ti-check me-1"></i> نشط
                @else
                {{ $subscriptionDetails->status->label() }}
                @endif
            </div>
        </div>

        {{-- Card Body --}}
        <div class="request-body">
            {{-- Tenant Information --}}
            <h5 class="section-title">
                <i class="ti ti-building"></i>
                معلومات المنشأة
            </h5>
            <div class="info-grid">
                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-building"></i>
                    </div>
                    <div class="content">
                        <span class="label">اسم المنشأة</span>
                        <div class="value">{{ $subscriptionDetails->tenant->company_name ?? '-' }}</div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-id"></i>
                    </div>
                    <div class="content">
                        <span class="label">السجل التجاري</span>
                        <div class="value">{{ $subscriptionDetails->tenant->commercial_number ?? '-' }}</div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-server"></i>
                    </div>
                    <div class="content">
                        <span class="label">معرف العميل</span>
                        <div class="value">{{ $subscriptionDetails->tenant_id }}</div>
                    </div>
                </div>
            </div>

            {{-- Subscription Information --}}
            <h5 class="section-title">
                <i class="ti ti-file-dollar"></i>
                معلومات الاشتراك
            </h5>
            <div class="info-grid">
                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-calendar"></i>
                    </div>
                    <div class="content">
                        <span class="label">تاريخ بداية الاشتراك</span>
                        <div class="value">{{ $subscriptionDetails->starts_at?->format('Y-m-d H:i') ?? '-' }}</div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-calendar-event"></i>
                    </div>
                    <div class="content">
                        <span class="label">موعد الدفعة القادمة</span>
                        <div class="value">{{ $subscriptionDetails->next_payment_date?->format('Y-m-d') ?? '-' }}</div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-currency-riyal"></i>
                    </div>
                    <div class="content">
                        <span class="label">قيمة الاشتراك</span>
                        <div class="value" style="color: #28a745; font-size: 1.2rem;">
                            {{ number_format($subscriptionDetails->price, 2) }} ر.س
                        </div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-users"></i>
                    </div>
                    <div class="content">
                        <span class="label">عدد المستخدمين</span>
                        <div class="value">
                            {{ $subscriptionDetails->used_users }} / {{ $subscriptionDetails->total_users }}
                            <small class="text-muted">(مستخدم / متاح)</small>
                        </div>
                    </div>
                </div>

                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-refresh"></i>
                    </div>
                    <div class="content">
                        <span class="label">التجديد التلقائي</span>
                        <div class="value">
                            @if($subscriptionDetails->auto_renew)
                            <span class="badge bg-success">مفعّل</span>
                            @else
                            <span class="badge bg-secondary">غير مفعّل</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($subscriptionDetails->periods->isNotEmpty())
                @php $currentPeriod = $subscriptionDetails->periods->first(); @endphp
                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-clock"></i>
                    </div>
                    <div class="content">
                        <span class="label">الفترة الحالية</span>
                        <div class="value">
                            {{ $currentPeriod->period_start?->format('Y-m-d') }}
                            <i class="ti ti-arrow-left mx-1"></i>
                            {{ $currentPeriod->period_end?->format('Y-m-d') }}
                        </div>
                    </div>
                </div>
                @endif
            </div>

            {{-- Plan Details --}}
            <h5 class="section-title">
                <i class="ti ti-package"></i>
                تفاصيل الباقة
            </h5>
            <div class="plan-details">
                <h6>
                    <i class="ti ti-star-filled" style="color: #ffc107;"></i>
                    {{ $subscriptionDetails->plan->name }}
                    @if($subscriptionDetails->periods->isNotEmpty())
                    <span class="period-badge">
                        <i class="ti ti-calendar-time"></i>
                        {{ $subscriptionDetails->periods->first()->period_number }} يوم
                    </span>
                    @endif
                </h6>
                <p class="plan-description">{{ $subscriptionDetails->plan->description }}</p>

                <div class="plan-features">
                    @foreach($subscriptionDetails->plan->features as $feature)
                    <div class="plan-feature">
                        <i class="ti ti-circle-check-filled"></i>
                        <span>{{ $feature }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Bank Transfer Attachments --}}
            @if($subscriptionDetails->transfer_bank_attachment && count($subscriptionDetails->transfer_bank_attachment) > 0)
            <h5 class="section-title">
                <i class="ti ti-paperclip"></i>
                إيصالات التحويل البنكي
            </h5>
            <div class="attachment-section">
                <h6>
                    <i class="ti ti-file-invoice"></i>
                    عدد الإيصالات: {{ count($subscriptionDetails->transfer_bank_attachment) }}
                </h6>
                <div class="attachment-preview">
                    @foreach($subscriptionDetails->transfer_bank_attachment as $index => $attachment)
                    <div class="attachment-item" onclick="window.open('{{ asset('storage/' . $attachment) }}', '_blank')" title="اضغط للعرض">
                        <img src="{{ asset('storage/' . $attachment) }}" alt="إيصال التحويل {{ $index + 1 }}">
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Actions Bar --}}
            <div class="actions-bar">
                <button type="button" class="btn-approve" onclick="approveRequest({{ $subscriptionDetails->id }})">
                    <i class="ti ti-check"></i>
                    <span>الموافقة وتفعيل الاشتراك</span>
                </button>
                <button type="button" class="btn-reject" onclick="rejectRequest({{ $subscriptionDetails->id }})">
                    <i class="ti ti-x"></i>
                    <span>رفض الطلب</span>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function approveRequest(id) {
        Swal.fire({
            title: 'تأكيد الموافقة'
            , text: 'هل أنت متأكد من الموافقة على هذا الطلب وتفعيل الاشتراك؟'
            , icon: 'question'
            , showCancelButton: true
            , confirmButtonText: 'نعم، الموافقة'
            , cancelButtonText: 'إلغاء'
            , confirmButtonColor: '#4cc1bb'
            , cancelButtonColor: '#372632'
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'جاري المعالجة...'
                    , allowOutsideClick: false
                    , didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Ajax request
                $.ajax({
                    url: `/admin/transfer-requests/${id}/approve`
                    , method: 'POST'
                    , data: {
                        _token: '{{ csrf_token() }}'
                    }
                    , success: function(response){
                        Swal.fire({
                            title: 'تم بنجاح!'
                            , text: 'تم الموافقة على الطلب وتفعيل الاشتراك'
                            , icon: 'success'
                            , confirmButtonColor: '#4cc1bb'
                        }).then(() => {
                            window.location.href = '{{ route('admin.transfer-requests.index') }}';
                        });
                    }

                    , error: function(xhr) {
                        Swal.fire({
                            title: 'خطأ!'
                            , text: 'حدث خطأ أثناء الموافقة على الطلب'
                            , icon: 'error'
                            , confirmButtonColor: '#f44336'
                        });
                    }
                });
            }
        });
    }

    function rejectRequest(id) {
        Swal.fire({
            title: 'سبب الرفض'
            , input: 'textarea'
            , inputLabel: 'يرجى كتابة سبب رفض الطلب'
            , inputPlaceholder: 'مثال: البيانات غير مكتملة، الإيصال غير واضح، ...'
            , showCancelButton: true
            , confirmButtonText: 'رفض الطلب'
            , cancelButtonText: 'إلغاء'
            , confirmButtonColor: '#f44336'
            , cancelButtonColor: '#6c757d'
            , inputValidator: (value) => {
                if (!value) {
                    return 'يرجى كتابة سبب الرفض';
                }
                if (value.length < 10) {
                    return 'يرجى كتابة سبب مفصل (10 أحرف على الأقل)';
                }
            }
        }).then((result) => {
            if (result.isConfirmed) {
                // Show loading
                Swal.fire({
                    title: 'جاري المعالجة...'
                    , allowOutsideClick: false
                    , didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Ajax request
                $.ajax({
                    url: `/admin/transfer-requests/${id}/reject`
                    , method: 'POST'
                    , data: {
                        _token: '{{ csrf_token() }}'
                        , reason: result.value
                    }
                    , success: function(response) {
                        Swal.fire({
                            title: 'تم الرفض'
                            , text: 'تم رفض الطلب  بنجاح'
                            , icon: 'info'
                            , confirmButtonColor: '#372632'
                        }).then(() => {
                            window.location.href = '{{ route('admin.transfer-requests.index') }}';
                        });
                    }
                    , error: function(xhr) {
                        Swal.fire({
                            title: 'خطأ!'
                            , text: 'حدث خطأ أثناء رفض الطلب'
                            , icon: 'error'
                            , confirmButtonColor: '#f44336'
                        });
                    }
                });
            }
        });
    }

</script>

@endsection
