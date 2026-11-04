<div class="tab-pane fade" id="regionsSettings">
    <div class="card mb-6">
        <div class="card-header border-bottom py-3">
            <h5 class="card-title mb-0 d-flex align-items-center">
                <i class="ti ti-map-pin text-primary me-2 fs-4"></i>مناطق العمل المعتمدة
            </h5>
        </div>

        <form id="formRegionsSettings" method="POST" action="{{ tenant_route_name('settings.update') }}" novalidate
            data-form-validate data-validation-url="{{ tenant_route_name('wizard-drafts.validate-step') }}">
            @csrf
            @method('PUT')
            <input type="hidden" name="active_tab" value="#regionsSettings">
            <input type="hidden" name="regions_settings_submit" value="1">

            <div class="card-body pt-4">
                <div class="alert alert-primary d-flex align-items-center mb-4" role="alert">
                    <i class="ti ti-info-circle fs-3 me-2 flex-shrink-0"></i>
                    <div>
                        <div class="fw-bold mb-1">تحديد نطاق العمل الجغرافي للنظام</div>
                        <span class="small">
                            حدد المناطق الإدارية التي يعمل بها مكتبك أو شركتك. المناطق التي يتم اختيارها وتحديدها هنا هي فقط التي ستظهر للمستخدمين في جميع القوائم المنسدلة الخاصة بالمناطق في النظام (نماذج إضافة وتعديل العقارات، وفلاتر البحث).
                        </span>
                    </div>
                </div>

                <div class="row g-3">
                    <x-form.controls.select name="operational_regions" id="operational_regions" label="المناطق الإدارية المتاحة للعمل"
                        :options="$allRegions ?? []" :value="old('operational_regions', $settings->operational_regions ?? [])"
                        multiple placeholder="اختر المناطق التي يعمل بها المكتب..." col="col-12"
                        help="اختر منطقة واحدة أو أكثر. يمكنك البحث داخل القائمة أو النقر للإضافة والإزالة." />
                </div>

                <div class="mt-4 pt-2 border-top d-flex justify-content-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="ti ti-check me-1"></i>حفظ مناطق العمل
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
