{{-- ملف: resources/views/business_center/customers/customer_sms/simple-tab.blade.php --}}

<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">
                الرسائل النصية
            </h5>
            <button type="button" class="btn btn-primary btn-sm js-create-sms-btn">
                <i class="ti ti-plus me-1"></i>إرسال رسالة جديدة
            </button>
        </div>
    </div>

    <div class="card-body p-0">
        <!-- عرض الرسائل المحفوظة -->
        <div class="p-3" id="saved-sms-messages">
            @php
                $customerSmsMessages = collect();
                try {
                    if (method_exists($customer, 'smsMessages')) {
                        $customerSmsMessages = $customer
                            ->smsMessages()
                            ->with(['messageTemplate:id,name', 'sentBy:id,name'])
                            ->recent()
                            ->get();
                    }
                } catch (\Exception $e) {
                    $customerSmsMessages = collect();
                }
            @endphp

            @if ($customerSmsMessages->count() > 0)
                @foreach ($customerSmsMessages as $sms)
                    <div class="card sms-card mb-3" id="sms-{{ $sms->id }}">
                        <div class="card-body">
                            <!-- الصف الأول: البيانات الأساسية -->
                            <div class="row g-3 mb-3">
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex flex-column">
                                        <label class="form-label small text-muted mb-1">رقم الهاتف</label>
                                        <span class="fw-semibold text-primary">{{ $sms->phone_number }}</span>
                                        <small class="text-muted">{{ $sms->phone_type_label }}</small>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex flex-column">
                                        <label class="form-label small text-muted mb-1">القالب المستخدم</label>
                                        <span
                                            class="fw-semibold text-dark">{{ $sms->template_name ?? 'قالب محذوف' }}</span>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex flex-column">
                                        <label class="form-label small text-muted mb-1">حالة الإرسال</label>
                                        <span
                                            class="badge {{ $sms->status_class }} rounded-pill">{{ $sms->status_label }}</span>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6">
                                    <div class="d-flex flex-column">
                                        <label class="form-label small text-muted mb-1">تاريخ الإرسال</label>
                                        <span class="fw-semibold text-success">
                                            {{ $sms->sent_at_formatted ?? $sms->created_at_formatted }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- الصف الثاني: محتوى الرسالة -->
                            <div class="row mb-3">
                                <div class="col-12">
                                    <label class="form-label small text-muted mb-1">محتوى الرسالة</label>
                                    <div class="border rounded p-2 bg-light">
                                        <p class="mb-0 small">{{ $sms->message_preview }}</p>
                                        @if (strlen($sms->message_content) > 100)
                                            <button type="button" class="btn btn-link btn-sm p-0 small js-toggle-sms-btn"
                                                data-id="{{ $sms->id }}">
                                                عرض كامل
                                            </button>
                                            <div id="full-message-{{ $sms->id }}" style="display: none;">
                                                <hr class="my-2">
                                                <p class="mb-0 small">{{ $sms->message_content }}</p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- الصف الثالث: معلومات إضافية والإجراءات -->
                            <div class="row align-items-center">
                                <!-- معلومات إضافية -->
                                <div class="col-lg-8 col-md-7">
                                    <div class="d-flex flex-wrap align-items-center gap-3">
                                        <!-- المرسل -->
                                        @if ($sms->sentBy)
                                            <div class="d-flex align-items-center text-muted small">
                                                <i class="ti ti-user me-1"></i>
                                                <span>{{ $sms->sentBy->name }}</span>
                                            </div>
                                        @endif

                                        <!-- سبب الفشل إن وجد -->
                                        @if ($sms->status === 'failed' && $sms->failure_reason)
                                            <div class="d-flex align-items-center text-danger small">
                                                <i class="ti ti-alert-circle me-1"></i>
                                                <span>{{ $sms->failure_reason }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                <!-- أزرار الإجراءات -->
                                <div class="col-lg-4 col-md-5">
                                    <div class="d-flex justify-content-end gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-danger js-delete-sms-btn"
                                            data-id="{{ $sms->id }}" title="حذف الرسالة">
                                            <i class="ti ti-trash me-1"></i>
                                            <span class="d-none d-sm-inline">حذف</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            @else
                <div class="border-0">
                    <div class="card-body text-center py-5">
                        <div class="mb-4">
                            <i class="ti ti-message-circle text-muted" style="font-size: 4rem;"></i>
                        </div>
                        <h5 class="text-muted mb-2">لا توجد رسائل نصية</h5>
                    </div>
                </div>
            @endif
        </div>

        {{-- إحصائيات بسيطة --}}
        @php
            $statistics = app(\App\Services\Tenants\BusinessCenter\Customer\CustomerSmsService::class)->getSimpleStatistics(
                $customer->id,
            );
        @endphp

        @if ($statistics['total_count'] > 0)
            <div class="border-top">
                <div class="card bg-light border-0 m-3 mt-0">
                    <div class="card-body py-3">
                        <h6 class="card-title mb-3 text-center">
                            <i class="ti ti-chart-bar me-2"></i>ملخص الرسائل النصية
                        </h6>
                        <div class="row g-3">
                            <div class="col-6 col-lg-3">
                                <div class="text-center">
                                    <div class="fs-4 fw-bold text-primary">{{ $statistics['total_count'] }}</div>
                                    <small class="text-muted">إجمالي الرسائل</small>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="text-center">
                                    <div class="fs-4 fw-bold text-success">{{ $statistics['sent_count'] }}</div>
                                    <small class="text-muted">رسائل مرسلة</small>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="text-center">
                                    <div class="fs-4 fw-bold text-warning">{{ $statistics['pending_count'] }}</div>
                                    <small class="text-muted">في الانتظار</small>
                                </div>
                            </div>
                            <div class="col-6 col-lg-3">
                                <div class="text-center">
                                    <div class="fs-4 fw-bold text-danger">{{ $statistics['failed_count'] }}</div>
                                    <small class="text-muted">فشل الإرسال</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<!-- Container للـ Modals -->
<div id="customer-sms-modals-container"></div>

<script>
    window.customerId = {{ $customer->id }};
</script>
