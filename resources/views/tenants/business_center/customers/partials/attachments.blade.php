<div class="card">
    <div class="card-header">
        <h5 class="card-title mb-0">مرفقات العميل</h5>
    </div>

    <div class="card-body">
        <!-- نموذج إضافة المرفقات -->
        <form id="customerAttachmentsForm" enctype="multipart/form-data">
            @csrf

            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">إضافة مرفقات جديدة</h6>
                    <button type="button" class="btn btn-sm btn-primary js-add-attachment-field-btn">
                        <i class="ti ti-plus me-1"></i>إضافة مرفق
                    </button>
                </div>

                <!-- حاوي الحقول الديناميكية -->
                <div id="attachments-container">
                    <!-- سيتم إضافة الحقول هنا -->
                </div>

                <!-- زر الحفظ -->
                <div class="mt-3 text-end" id="save-section" style="display: none;">
                    <button type="button" class="btn btn-primary js-save-attachments-btn">
                        <span class="spinner-border spinner-border-sm me-2" id="save-spinner"
                            style="display: none;"></span>
                        حفظ
                    </button>
                </div>
            </div>
        </form>

        <hr>

        <!-- عرض المرفقات المحفوظة -->
        <div class="mb-3">
            <h6 class="mb-3">المرفقات المحفوظة</h6>
            <div id="saved-attachments">
                @php
                    $customerAttachments = $customer->attachments()->with('createdBy:id,name')->latest()->get();
                @endphp

                @include('tenants.business_center.customers.customer_attachments.list', ['customerAttachments' => $customerAttachments])
            </div>
        </div>
    </div>
</div>

<script>
    window.customerId = {{ $customer->id }};
</script>