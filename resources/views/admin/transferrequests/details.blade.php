@extends('admin.layouts.layoutMaster')
@section('title', 'تفاصيل طلب الاشتراك')

@section('vendor-style')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

@section('vendor-script')
@vite(['resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

@section('page-style')
    @vite(['resources/scss/admin/pages/transfer-details.scss'])
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
                معلومات الشركة
            </h5>
            <div class="info-grid">
                <div class="info-item">
                    <div class="icon">
                        <i class="ti ti-building"></i>
                    </div>
                    <div class="content">
                        <span class="label">اسم الشركة</span>
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
