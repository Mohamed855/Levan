<!-- مودال المشاركة عبر الواتساب -->
<div class="modal fade" id="whatsappShareModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">مشاركة عبر الواتساب</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="whatsappShareForm">
                    @csrf
                    <div class="row g-4">
                        <div class="col-12">
                            <label class="form-label fw-semibold mb-2">
                                اختر قالب الرسالة
                                <span class="text-danger">*</span>
                            </label>
                            <select name="template_id" id="whatsapp_template_id" class="form-control select2-modal"
                                data-placeholder="اختر قالب الرسالة" required>
                                <option value=""></option>
                                @foreach ($templates as $template)
                                    <option value="{{ $template->id }}" data-content="{{ $template->content }}">
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="invalid-feedback"></div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold mb-2">
                                اختر رقم الهاتف
                                <span class="text-danger">*</span>
                            </label>
                            <div>
                                <div class="card-body p-2">
                                    <div id="phone-options" class="d-none">
                                        <!-- سيتم ملؤها ديناميكيًا -->
                                    </div>
                                    <div id="no-phones-message" class="text-center text-muted py-2">
                                        <i class="ti ti-phone-off me-1"></i>
                                        لا توجد أرقام هواتف متاحة
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-semibold mb-2">معاينة الرسالة</label>
                            <div class="border rounded p-3"
                                style="min-height: 150px; max-height: 250px; overflow-y: auto;">
                                <textarea name="message" id="whatsapp_message" class="form-control border-0 bg-transparent p-0" rows="6" readonly
                                    placeholder="سيتم عرض الرسالة هنا بعد اختيار القالب" style="resize: none; box-shadow: none;"></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    إلغاء
                </button>
                <button type="button" id="openWhatsAppBtn" class="btn btn-primary" disabled>
                    <i class="ti ti-brand-whatsapp me-1"></i>
                    فتح الواتساب
                </button>
            </div>
        </div>
    </div>
</div>
