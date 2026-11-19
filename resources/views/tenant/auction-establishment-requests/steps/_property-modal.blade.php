{{-- نافذة منبثقة واحدة موحدة لاستكمال بيانات ومرفقات العقار عبر JavaScript (نفس أسلوب مودال الإقفال وخارج النموذج الرئيسي) --}}
<x-bootstrap.modal id="establishmentPropertyModal" size="xl" scrollable="true" :staticBackdrop="true">
    <x-slot:header>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <i class="ti ti-building fs-4 text-primary"></i>
            <h5 class="modal-title fw-bold mb-0" id="establishmentPropertyModalTitle">
                استكمال بيانات العقار
            </h5>
            <code class="copyable-code" id="establishmentPropertyModalNumber">-</code>
            <span class="badge bg-label-info" id="establishmentPropertyModalType">-</span>
        </div>
    </x-slot:header>

    <div id="establishmentPropertyModalBody">
        <input type="hidden" id="modal_prop_id">
        <input type="hidden" id="modal_prop_index">

        <div class="alert alert-info py-2 px-3 mb-4 d-flex align-items-center">
            <i class="ti ti-info-circle me-2"></i>
            <small>البيانات المستوردة من سجل العقار والمزاد والإسناد (الملاك، الصك، المساحة، النوع، الاستخدام، المدينة)
                مقفلة تلقائياً وغير قابلة للتعديل للحفاظ على مطابقة السجلات.</small>
        </div>

        <div class="row g-4">
            {{-- المالك / الملاك --}}
            <x-form.controls.input name="modal_prop_owner_name" id="modal_prop_owner_name" label="المالك / الملاك"
                required col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1"
                style="pointer-events: none;" help="مستورد من سجل ملاك العقار" />

            {{-- رقم الصك --}}
            <x-form.controls.input name="modal_prop_deed_number" id="modal_prop_deed_number"
                label="رقم الصك / الصحيفة العقارية" required inputmode="numeric" pattern="[0-9]+" col="col-md-6"
                :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1" style="pointer-events: none;"
                help="مستورد من وثيقة ملكية العقار" />

            {{-- رقم عقد المزاد --}}
            <x-form.controls.input name="modal_prop_contract_number" id="modal_prop_contract_number"
                label="رقم عقد المزاد / الإسناد" required maxlength="100" col="col-md-6"
                help="رقم الإسناد أو عقد الوساطة للمزاد" />

            {{-- المساحة --}}
            <x-form.controls.input name="modal_prop_area" id="modal_prop_area" label="المساحة (م²)" type="number"
                min="0.01" step="any" required col="col-md-6" :readonly="true"
                class="bg-light cursor-not-allowed" tabindex="-1" style="pointer-events: none;"
                help="مستورد من مساحة العقار" />

            {{-- نوع العقار --}}
            <x-form.controls.input name="modal_prop_type" id="modal_prop_type" label="نوع العقار" required
                col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1"
                style="pointer-events: none;" help="مستورد من تصنيف العقار" />

            {{-- استخدام العقار --}}
            <x-form.controls.input name="modal_prop_usage" id="modal_prop_usage" label="استخدام العقار" required
                col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1"
                style="pointer-events: none;" help="مستورد من تصنيف استخدام العقار" />

            {{-- الاستخدام الآخر (إن وجد) --}}
            <div class="col-md-6 d-none" id="modal_prop_usage_other_col">
                <x-form.controls.input name="modal_prop_usage_other" id="modal_prop_usage_other" label="الاستخدام الآخر"
                    col="col-12" />
            </div>

            {{-- عمر العقار --}}
            <x-form.controls.input name="modal_prop_age" id="modal_prop_age" label="عمر العقار بالسنوات (صفر للأرض)"
                type="number" min="0" max="200" step="1" required col="col-md-6" />

            {{-- المدينة / الحي --}}
            <x-form.controls.input name="modal_prop_city_district" id="modal_prop_city_district" label="المدينة / الحي"
                required col="col-md-6" :readonly="true" class="bg-light cursor-not-allowed" tabindex="-1"
                style="pointer-events: none;" help="مستورد من موقع العقار" />

            {{-- الحالة النظامية --}}
            <x-form.controls.select name="modal_prop_status" id="modal_prop_status" label="الحالة النظامية"
                :options="[
                    'clear' => 'سليم',
                    'seized' => 'محجوز',
                    'mortgaged' => 'مرهون',
                    'endowed' => 'وقف',
                    'other' => 'أخرى',
                ]" required col="col-md-6" />

            {{-- تفاصيل الحالة الأخرى --}}
            <div class="col-md-6 d-none" id="modal_prop_status_other_col">
                <x-form.controls.input name="modal_prop_status_other" id="modal_prop_status_other"
                    label="تفاصيل الحالة الأخرى" col="col-12" />
            </div>

            {{-- نسبة الملكية المعروضة --}}
            <x-form.controls.input name="modal_prop_ownership_share" id="modal_prop_ownership_share"
                label="نسبة الملكية المعروضة %" type="number" min="0.01" max="100" step="any"
                required col="col-md-6" />

            {{-- قيمة افتتاح المزاد --}}
            <x-form.controls.input name="modal_prop_opening_value" id="modal_prop_opening_value"
                label="قيمة افتتاح المزاد" type="number" min="0" step="0.01" :price="true" required
                col="col-md-4" />

            {{-- مبلغ الدخول --}}
            <x-form.controls.input name="modal_prop_entry_amount" id="modal_prop_entry_amount" label="مبلغ الدخول"
                type="number" min="0" step="0.01" :price="true" required col="col-md-4" />

            {{-- القيمة التقديرية --}}
            <x-form.controls.input name="modal_prop_estimated_value" id="modal_prop_estimated_value"
                label="القيمة التقديرية" type="number" min="0" step="0.01" :price="true" required
                col="col-md-4" />

            {{-- الإفصاح الخاص بالعقار --}}
            <x-form.controls.textarea name="modal_prop_disclosure" id="modal_prop_disclosure"
                label="الإفصاح الخاص بالعقار" required col="col-12" rows="2" />

            {{-- بيانات تركيب اللوحات للعقار --}}
            <x-form.controls.input name="modal_prop_board_count" id="modal_prop_board_count" label="عدد اللوحات"
                type="number" min="1" step="1" required col="col-md-6" />

            <x-form.controls.input name="modal_prop_board_dimensions" id="modal_prop_board_dimensions"
                label="مقاس اللوحات" placeholder="مثال: 3م × 2م" maxlength="255" required col="col-md-6" />

            <div class="col-md-6">
                <x-form.controls.file name="modal_file_board_before[]" id="modal_file_board_before"
                    label="صور تركيب اللوحات" multiple accept=".png,.jpg,.jpeg,.webp" col="col-12" required
                    help="صور تركيب اللوحات على العقار (PNG, JPG, WEBP — حتى 10MB لكل صورة)." />
            </div>

            <div class="col-md-6">
                <x-form.controls.file name="modal_file_valuation" id="modal_file_valuation"
                    label="نموذج التقييم العقاري" accept=".pdf,.png,.jpg,.jpeg" col="col-12" required
                    help="ملف نموذج التقييم العقاري المعتمد (PDF أو صورة — حتى 10MB)." />
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>إلغاء
        </button>
        <button type="button" class="btn btn-primary" id="btn-save-establishment-prop-modal">
            <i class="ti ti-check me-1"></i>حفظ
        </button>
    </x-slot:footer>
</x-bootstrap.modal>
