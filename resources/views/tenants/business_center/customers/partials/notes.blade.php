<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">ملاحظات العميل</h5>
        </div>
    </div>

    <div class="card-body">
        <div class="border rounded p-3 mb-4">
            <form id="customerNotesForm">
                @csrf

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="mb-0">إضافة ملاحظة جديدة</h6>
                    <button type="button" class="btn btn-outline-primary btn-sm js-add-note-field-btn">
                        إضافة ملاحظة
                    </button>
                </div>

                <!-- حاوي الحقول الديناميكية -->
                <div id="notes-container" class="mb-3">
                    <!-- سيتم إضافة الحقول هنا -->
                </div>

                <div id="save-notes-section" style="display: none;">
                    <div class="text-end">
                        <button type="button" class="btn btn-primary js-save-notes-btn">
                            <span class="spinner-border spinner-border-sm me-2" id="save-notes-spinner"
                                style="display: none;"></span>
                            حفظ
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- عرض الملاحظات المحفوظة -->
        <div>
            <h6 class="mb-3">الملاحظات المحفوظة</h6>

            <div id="saved-notes">
                @php
                    $customerNotes = collect();
                    try {
                        if (method_exists($customer, 'notes')) {
                            $customerNotes = $customer->notes()->with('createdBy:id,name')->latest()->get();
                        }
                    } catch (\Exception $e) {
                        $customerNotes = collect();
                    }
                @endphp

                @include('tenants.business_center.customers.customer_notes.list', [
                    'customerNotes' => $customerNotes,
                ])
            </div>
        </div>
    </div>
</div>

<script>
    window.customerId = {{ $customer->id }};
</script>
