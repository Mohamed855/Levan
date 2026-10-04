@extends('tenants.layouts.layoutMaster')

@section('title', 'تفاصيل الخطاب')

{{-- ═══ Vendor Styles ═══ --}}
@section('vendor-style')
    @vite(['resources/assets/vendor/libs/select2/select2.scss', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.scss'])
@endsection

{{-- ═══ Vendor Scripts ═══ --}}
@section('vendor-script')
    @vite(['resources/assets/vendor/libs/select2/select2.js', 'resources/assets/vendor/libs/sweetalert2/sweetalert2.js'])
@endsection

{{-- ═══ Toastr ═══ --}}
@section('toastr')
    @vite(['resources/css/toastr.css', 'resources/js/toastr.js'])
@endsection

{{-- ═══ Page Scripts ═══ --}}
@section('page-script')
    <script>
        window.routes = {
            companyLettersIndex: "{{ tenant_route_name('company-letters.index') }}",
            previewTemplate: "{{ tenant_route_name('company-letters.preview-template', $letter->id) }}",
            sendEmail: "{{ tenant_route_name('company-letters.send', $letter->id) }}"
        };
        window.csrfToken = "{{ csrf_token() }}";
        window.letterId = {{ $letter->id }};
    </script>

    @vite(['resources/assets/js/tenants/business-center/customers/letters/show.js'])
@endsection

{{-- ═══ Page Content ═══ --}}
@section('content')

    {{-- ═══ إحصائيات الخطاب ═══ --}}
    <div class="card mb-4">
        <div class="card-body">
            <div class="row text-center gy-3">
                <div class="col-md-3">
                    <h6 class="text-muted fw-normal">حالة الخطاب</h6>
                    <div class="border-top border-light border-dashed my-2"></div>
                    <p class="fs-5 mb-0">
                        <span class="badge {{ $letter->status_class }}">
                            {{ $letter->status_label }}
                        </span>
                    </p>
                </div>

                <div class="col-md-3">
                    <h6 class="text-muted fw-normal">نتيجة الاتصال</h6>
                    <div class="border-top border-light border-dashed my-2"></div>
                    <p class="fs-5 mb-0">
                        <span class="badge">
                            {{ $letter->customerFollow->contactResult->name ?? '-' }}
                        </span>
                    </p>
                </div>

                <div class="col-md-3">
                    <h6 class="text-muted fw-normal">المحصل</h6>
                    <div class="border-top border-light border-dashed my-2"></div>
                    <p class="fs-5 mb-0">{{ $letter->customerFollow->createdBy->name ?? '-' }}</p>
                </div>

                <div class="col-md-3">
                    <h6 class="text-muted fw-normal">تاريخ المتابعة</h6>
                    <div class="border-top border-light border-dashed my-2"></div>
                    <p class="fs-5 mb-0">{{ $letter->customerFollow->created_at->format('Y-m-d H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        {{-- ═══ معلومات العميل (العمود الأيسر) ═══ --}}
        <div class="col-12 col-lg-4">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">معلومات العميل</h5>
                    <span class="badge bg-label-primary">
                        {{ $letter->customer->customer_type === 'individual' ? 'فردي' : 'شركة' }}
                    </span>
                </div>
                <div class="border-1 border-light border-dashed"></div>
                <div class="card-body">
                    <div class="table-responsive text-nowrap">
                        <table class="table table-striped table-bordered">
                            <tbody class="table-border-bottom-0">
                                <tr>
                                    <td class="fw-medium">اسم العميل</td>
                                    <td>{{ $letter->customer->name }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">رقم الحساب</td>
                                    <td>
                                        <span class="badge bg-secondary">
                                            {{ $letter->customer->account_number ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">رقم الهوية</td>
                                    <td>{{ $letter->customer->identity_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">رقم الهاتف</td>
                                    <td>{{ $letter->customer->phone ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">الشركة</td>
                                    <td>{{ $letter->customer->company->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">المحفظة</td>
                                    <td>{{ $letter->customer->portfolio->name ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ تفاصيل المتابعة والنتيجة (العمود الأيمن) ═══ --}}
        <div class="col-12 col-lg-8">
            {{-- ═══ بيانات المتابعة ═══ --}}
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="ti ti-clipboard-text me-2"></i>
                        بيانات المتابعة
                    </h5>
                </div>
                <div class="border-1 border-light border-dashed"></div>
                <div class="card-body">
                    <div class="table-responsive text-nowrap">
                        <table class="table table-striped table-bordered">
                            <tbody class="table-border-bottom-0">
                                <tr>
                                    <td class="fw-medium" style="width: 40%">نتيجة الاتصال</td>
                                    <td>
                                        <span class="badge">
                                            {{ $letter->customerFollow->contactResult->name ?? '-' }}
                                        </span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">طريقة الاتصال</td>
                                    <td>{{ $letter->customerFollow->contactMethod->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">المحصل</td>
                                    <td>{{ $letter->customerFollow->createdBy->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td class="fw-medium">تاريخ المتابعة</td>
                                    <td>{{ $letter->customerFollow->created_at->format('Y-m-d H:i') }}</td>
                                </tr>
                                @if ($letter->customerFollow->notes)
                                    <tr>
                                        <td class="fw-medium">الملاحظات</td>
                                        <td>{{ $letter->customerFollow->notes }}</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- ═══ تفاصيل السداد الكامل ═══ --}}
            @if ($letter->action_type === 'payment_full' && $letter->customerFollow->fullPaymentDetails)
                <div class="card mb-4">
                    <div class="card-header bg-label-success">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-cash me-2"></i>
                            تفاصيل السداد الكامل
                        </h5>
                    </div>
                    <div class="border-1 border-light border-dashed"></div>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-striped table-bordered">
                                <tbody class="table-border-bottom-0">
                                    <tr>
                                        <td class="fw-medium" style="width: 40%">المبلغ المسدد</td>
                                        <td class="fw-bold text-success">
                                            {{ number_format($letter->customerFollow->fullPaymentDetails->payment_amount, 2) }}
                                            ريال
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">تاريخ السداد</td>
                                        <td>{{ $letter->customerFollow->fullPaymentDetails->payment_date }}</td>
                                    </tr>
                                    @if ($letter->collection)
                                        <tr>
                                            <td class="fw-medium">رقم السند</td>
                                            <td>
                                                <span class="badge bg-label-primary">
                                                    {{ $letter->collection->receipt_number }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">طريقة الدفع</td>
                                            <td>{{ $letter->collection->paymentMethod->name ?? '-' }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══ تفاصيل السداد الجزئي ═══ --}}
            @if ($letter->action_type === 'payment_partial' && $letter->customerFollow->partialPaymentDetails)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-coins me-2"></i>
                            تفاصيل السداد الجزئي
                        </h5>
                    </div>
                    <div class="border-1 border-light border-dashed"></div>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-striped table-bordered">
                                <tbody class="table-border-bottom-0">
                                    <tr>
                                        <td class="fw-medium" style="width: 40%">المبلغ المسدد</td>
                                        <td class="fw-bold text-info">
                                            {{ number_format($letter->customerFollow->partialPaymentDetails->partial_payment_amount, 2) }}
                                            ريال
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">تاريخ السداد</td>
                                        <td>{{ $letter->customerFollow->partialPaymentDetails->partial_payment_date }}</td>
                                    </tr>
                                    @if ($letter->collection)
                                        <tr>
                                            <td class="fw-medium">رقم السند</td>
                                            <td>
                                                <span class="badge bg-label-primary">
                                                    {{ $letter->collection->receipt_number }}
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">طريقة الدفع</td>
                                            <td>{{ $letter->collection->paymentMethod->name ?? '-' }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══ تفاصيل طلب المستندات ═══ --}}
            @if ($letter->action_type === 'document_request' && $letter->customerFollow->documentRequestDetails)
                <div class="card mb-4">
                    <div class="card-header bg-label-warning">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-file-text me-2"></i>
                            تفاصيل طلب المستندات
                        </h5>
                    </div>
                    <div class="border-1 border-light border-dashed"></div>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-striped table-bordered">
                                <tbody class="table-border-bottom-0">
                                    <tr>
                                        <td class="fw-medium" style="width: 40%">تاريخ الطلب</td>
                                        <td>{{ $letter->customerFollow->documentRequestDetails->documents_date }}</td>
                                    </tr>
                                    <tr>
                                        <td class="fw-medium">سبب الطلب</td>
                                        <td>{{ $letter->customerFollow->documentRequestDetails->documentReason->name ?? '-' }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══ تفاصيل طلب الاسترداد ═══ --}}
            @if ($letter->action_type === 'recovery_request' && $letter->customerFollow->recoveryDetails)
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-archive me-2"></i>
                            تفاصيل طلب الاسترداد
                        </h5>
                    </div>
                    <div class="border-1 border-light border-dashed"></div>
                    <div class="card-body">
                        <div class="table-responsive text-nowrap">
                            <table class="table table-striped table-bordered">
                                <tbody class="table-border-bottom-0">
                                    <tr>
                                        <td class="fw-medium" style="width: 40%">سبب الاسترداد</td>
                                        <td>{{ $letter->customerFollow->recoveryDetails->recovery_reason }}</td>
                                    </tr>
                                    @if ($letter->customerFollow->recoveryDetails->recovery_document_path)
                                        <tr>
                                            <td class="fw-medium">المستند المرفق</td>
                                            <td>
                                                <a href="{{ Storage::url($letter->customerFollow->recoveryDetails->recovery_document_path) }}"
                                                    target="_blank" class="btn btn-sm btn-outline-primary">
                                                    <i class="ti ti-download me-1"></i>
                                                    تحميل المستند
                                                </a>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- ═══ قسم إرسال البريد ═══ --}}
            @can('company_letters.send')
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="ti ti-mail me-2"></i>
                            إرسال البريد
                        </h5>
                    </div>
                    <div class="border-1 border-light border-dashed"></div>
                    <div class="card-body">
                        @if ($letter->status === 'sent')
                            {{-- إذا تم الإرسال مسبقاً --}}
                            <div class="alert alert-success">
                                <h6 class="alert-heading mb-2">
                                    <i class="ti ti-check me-1"></i>
                                    تم إرسال هذا الخطاب
                                </h6>
                                <div class="table-responsive">
                                    <table class="table table-sm mb-0">
                                        <tr>
                                            <td class="fw-medium" style="width: 40%">تاريخ الإرسال:</td>
                                            <td>{{ $letter->sent_at->format('Y-m-d H:i') }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">تم الإرسال بواسطة:</td>
                                            <td>{{ $letter->sentBy->name ?? '-' }}</td>
                                        </tr>
                                        <tr>
                                            <td class="fw-medium">البريد المرسل إليه:</td>
                                            <td>{{ $letter->sent_to }}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        @else
                            {{-- نموذج الإرسال --}}
                            <form id="send-email-form">
                                @csrf

                                <div class="mb-3">
                                    <label for="recipient_email" class="form-label">
                                        البريد الإلكتروني للشركة
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="email" id="recipient_email" name="recipient_email" class="form-control"
                                        placeholder="company@example.com" required>
                                </div>

                                <div class="mb-3">
                                    <label for="template_id" class="form-label">
                                        قالب البريد
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select id="template_id" name="template_id" class="form-control select2"
                                        data-placeholder="اختر القالب" required>
                                        <option value=""></option>
                                        @foreach ($templates as $template)
                                            <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- معاينة القالب --}}
                                <div id="template-preview" class="mb-3 d-none">
                                    <label class="form-label">معاينة القالب</label>
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <h6 id="preview-subject" class="mb-2"></h6>
                                            <div id="preview-content" class="text-muted small"
                                                style="white-space: pre-wrap;"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="button" id="preview-btn" class="btn btn-outline-primary" disabled>
                                        <i class="ti ti-eye me-1"></i>
                                        معاينة القالب
                                    </button>

                                    <button type="submit" id="send-btn" class="btn btn-primary" disabled>
                                        <i class="ti ti-send me-1"></i>
                                        إرسال البريد
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @endcan
        </div>
    </div>

@endsection
