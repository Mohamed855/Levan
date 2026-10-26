<div class="tab-pane fade" id="printSettings">
    <div class="card mb-6">
        <div class="card-body">
            <form id="formPrintSettings" method="POST" action="{{ tenant_route_name('settings.update') }}"
                enctype="multipart/form-data" novalidate data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
                @csrf
                @method('PUT')
                <input type="hidden" name="active_tab" id="" value="#printSettings">

                <div class="row g-4 mb-6">
                    <x-form.controls.file name="horizontal_header_image" label="الترويسة الأفقية"
                        :value="$settings->horizontal_header_image ? tasset($settings->horizontal_header_image) : null"
                        accept="image/png,image/jpeg,image/webp" help="JPG, PNG أو WebP" col="col-md-4" />

                    <x-form.controls.file name="horizontal_footer_image" label="الفوتر الأفقي"
                        :value="$settings->horizontal_footer_image ? tasset($settings->horizontal_footer_image) : null"
                        accept="image/png,image/jpeg,image/webp" help="JPG, PNG أو WebP" col="col-md-4" />

                    <x-form.controls.file name="signature" label="الختم الخاص بالمؤسسة"
                        :value="$settings->signature ? tasset($settings->signature) : null"
                        accept="image/png,image/jpeg,image/webp" help="JPG, PNG أو WebP" col="col-md-4" />
                </div>
                <!-- زر الحفظ -->
                <div class="mt-2 d-flex justify-content-end">
                    <button type="button" class="btn btn-secondary me-3" data-bs-toggle="modal"
                        data-bs-target="#previewModal">معاينة</button>

                    <button type="submit" class="btn btn-primary me-3">تحديث </button>
                </div>
            </form>
        </div>
    </div>
</div>
<x-bootstrap.modal id="previewModal" size="lg" title="معاينة الترويسة والفوتر" icon="ti-eye">
    <div class="text-right mb-4">
        <img src="{{ tasset('storage/' . $settings->horizontal_header_image) }}" alt="الترويسة"
            class="mb-3 w-25 h-auto" id="previewHorizontalHeader" />
    </div>

    <div class="text-end" style="margin-top: 200px">
        <img src="{{ tasset('storage/' . $settings->signature) }}" alt="الختم" class="mb-3 w-20 h-20"
            id="previewSignature" />
    </div>

    <div class="text-center mb-4">
        <img src="{{ tasset('storage/' . $settings->horizontal_footer_image) }}" alt="الفوتر"
            class="mb-3 w-100 h-auto" id="previewHorizontalFooter" />
    </div>
    <x-slot:footer>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إغلاق</button>
    </x-slot:footer>
</x-bootstrap.modal>
