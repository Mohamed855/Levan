<div class="row g-3 border rounded p-3 mb-3" data-broker-row>
    <input type="hidden" name="campaigns[brokers][metrics][brokers][{{ $brokerIndex }}][key]" value="{{ $broker['key'] }}">
    @if (!empty($broker['evidence_id']))
        <input type="hidden" name="campaigns[brokers][metrics][brokers][{{ $brokerIndex }}][evidence_id]" value="{{ $broker['evidence_id'] }}">
    @endif
    <x-form.controls.input :name="'campaigns[brokers][metrics][brokers][' . $brokerIndex . '][name]'"
        label="اسم الوسيط" :value="$broker['name']" maxlength="200" col="col-md-5" required />
    <x-form.controls.file :name="'campaigns[brokers][evidence][' . $broker['key'] . ']'"
        label="ملف الوسيط" accept=".pdf,.png,.jpg,.jpeg,.webp" help="صورة أو PDF، حتى 10MB"
        :required="empty($broker['evidence_id'])" col="col-md-5" />
    <div class="col-md-2 d-flex align-items-center">
        <button type="button" class="btn btn-outline-danger" data-remove-broker aria-label="حذف الوسيط">
            <i class="ti ti-trash me-1"></i> حذف
        </button>
    </div>
    @if ($brokerFile)
        <div class="col-12"><x-ui.file-card :file="$brokerFile" /></div>
    @endif
</div>
