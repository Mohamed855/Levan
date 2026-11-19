@php
    $isEdit = isset($auction) && $auction->exists;
    $initialCount = $isEdit ? $auction->properties->count() : 0;
    $initialProperties = $isEdit ? $auction->properties : collect([]);
    $fallbackImage = asset('static/brand/logos/logo.svg');

    $estProps = $isEdit
        ? collect($auction->establishmentRequest?->data['properties'] ?? [])->keyBy('property_id')
        : collect([]);
    $estAttachments = $isEdit ? $auction->establishmentRequest?->data['attachments'] ?? [] : [];

    $existingAuctionPropertiesData = [];
    if ($isEdit && $auction->properties) {
        foreach ($auction->properties as $prop) {
            $pivot = $prop->pivot;
            $estP = $estProps->get($prop->id);

            $boardBeforeFiles = $auction->attachments
                ->where('report_section', 'board_before')
                ->where('property_id', $prop->id)
                ->values()
                ->map(
                    fn($file): array => [
                        'id' => $file->id,
                        'name' => $file->file_name ?? basename((string) $file->file_path),
                        'url' => tasset($file->file_path),
                    ],
                )
                ->all();

            $valAttachment = null;
            if (isset($estAttachments['valuation'][$prop->id])) {
                $valAttachment = [
                    'name' => $estAttachments['valuation'][$prop->id]['name'] ?? 'نموذج التقييم',
                    'url' => tenant_route_name('auction-establishment-requests.attachment', [
                        'auction' => $auction,
                        'type' => 'valuation',
                        'property' => $prop->id,
                    ]),
                ];
            } else {
                $valFile = $auction->attachments
                    ->where('category', 'valuation')
                    ->where('property_id', $prop->id)
                    ->first();
                if ($valFile) {
                    $valAttachment = [
                        'name' => $valFile->file_name ?? basename((string) $valFile->file_path),
                        'url' => tasset($valFile->file_path),
                    ];
                }
            }

            $boardData = is_array($pivot->board_data)
                ? $pivot->board_data
                : (is_string($pivot->board_data)
                    ? json_decode($pivot->board_data, true)
                    : []);
            $openingVal =
                $pivot->opening_price !== null ? (string) $pivot->opening_price : $estP['opening_value'] ?? '';
            $entryAmt = $pivot->entry_amount !== null ? (string) $pivot->entry_amount : $estP['entry_amount'] ?? '';
            $ownShare =
                $pivot->ownership_share !== null ? (string) $pivot->ownership_share : $estP['ownership_share'] ?? '100';
            $disc = (string) ($pivot->disclosure ?? ($estP['disclosure'] ?? ''));
            $bCount = (string) ($boardData['board_count'] ?? ($estP['board_count'] ?? '1'));
            $bDim = (string) ($boardData['board_dimensions'] ?? ($estP['board_dimensions'] ?? ''));

            $isComplete =
                filled($openingVal) &&
                filled($entryAmt) &&
                filled($ownShare) &&
                filled($disc) &&
                filled($bDim) &&
                !empty($valAttachment) &&
                !empty($boardBeforeFiles);

            $existingAuctionPropertiesData[$prop->id] = [
                'id' => (string) $prop->id,
                'name' => $prop->name,
                'number' => $prop->property_number ?? '-',
                'type' => $prop->type_label ?? '-',
                'city' => $prop->city ?? '-',
                'estimated_value' => (string) ($prop->estimated_value ?? '0'),
                'opening_value' => $openingVal,
                'entry_amount' => $entryAmt,
                'ownership_share' => $ownShare,
                'disclosure' => $disc,
                'board_count' => $bCount,
                'board_dimensions' => $bDim,
                'valuation_file' => $valAttachment,
                'board_before_files' => $boardBeforeFiles,
                'is_complete' => $isComplete,
            ];
        }
    }
@endphp

<div data-form-field data-field-name="property_ids">
    <div id="selected-property-inputs">
        @if ($isEdit && $auction->properties)
            @foreach ($auction->properties as $prop)
                <input type="hidden" name="property_ids[]" value="{{ $prop->id }}"
                    data-property-input="{{ $prop->id }}">
            @endforeach
        @endif
    </div>

    {{-- الحاوية المخفية لحقول بيانات العقارات التفصيلية للمزاد --}}
    <div id="auction-properties-data-container" class="d-none">
        @if ($isEdit && !empty($existingAuctionPropertiesData))
            @foreach ($existingAuctionPropertiesData as $propId => $pData)
                <div class="property-hidden-data" data-prop-hidden="{{ $propId }}">
                    <input type="hidden" name="properties_data[{{ $propId }}][opening_value]"
                        value="{{ $pData['opening_value'] }}" data-field="opening_value">
                    <input type="hidden" name="properties_data[{{ $propId }}][entry_amount]"
                        value="{{ $pData['entry_amount'] }}" data-field="entry_amount">
                    <input type="hidden" name="properties_data[{{ $propId }}][estimated_value]"
                        value="{{ $pData['estimated_value'] }}" data-field="estimated_value">
                    <input type="hidden" name="properties_data[{{ $propId }}][ownership_share]"
                        value="{{ $pData['ownership_share'] }}" data-field="ownership_share">
                    <input type="hidden" name="properties_data[{{ $propId }}][disclosure]"
                        value="{{ $pData['disclosure'] }}" data-field="disclosure">
                    <input type="hidden" name="properties_data[{{ $propId }}][board_count]"
                        value="{{ $pData['board_count'] }}" data-field="board_count">
                    <input type="hidden" name="properties_data[{{ $propId }}][board_dimensions]"
                        value="{{ $pData['board_dimensions'] }}" data-field="board_dimensions">
                    <div class="property-file-inputs" data-prop-files="{{ $propId }}"></div>
                </div>
            @endforeach
        @endif
    </div>

    <x-tenants.properties.property-selection-table title="العقارات المرتبطة بالإسناد المختار"
        subtitle="حدد العقارات التي سيتم إدراجها رسمياً في هذا المزاد." :showStats="true" :initialCount="$initialCount"
        tableId="auction-properties-table" />

    <div id="property_ids-error" class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
</div>

{{-- التنبيه التحذيري الذكي بين الجدول والبطاقات --}}
<div id="properties-completion-alert" class="alert alert-warning d-none align-items-center my-4" role="alert">
    <i class="ti ti-alert-triangle ti-md me-2"></i>
    <div class="fw-medium">برجاء استكمال بيانات العقارات للمتابعة</div>
</div>

{{-- سيكشن العقارات المحددة --}}
<x-tenants.properties.selected-properties-box title="العقارات المحددة (اضغط على كارت العقار لاستكمال أو تعديل بياناته)"
    :count="$initialCount" :properties="$initialProperties" :fallbackImage="$fallbackImage" id="selected-properties-cards"
    countId="selected-properties-count" />

{{-- المودال الموحد لاستكمال بيانات العقار --}}
<x-bootstrap.modal id="auctionPropertyModal" size="lg" scrollable="true" :staticBackdrop="true" data-validation-skip="true">
    <x-slot:header>
        <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-2 pe-3">
            <div class="d-flex align-items-center gap-2">
                <i class="ti ti-building fs-4 text-primary"></i>
                <h5 class="modal-title fw-bold mb-0" id="auctionPropertyModalTitle">
                    -
                </h5>
            </div>
            <div class="d-flex align-items-center gap-2">
                <code class="copyable-code d-inline-flex align-items-center justify-content-center px-2 py-0 m-0"
                    style="height: 22px; line-height: 1; font-size: 0.75rem;" id="auctionPropertyModalNumber">-</code>
                <span class="badge bg-label-info d-inline-flex align-items-center justify-content-center px-2 py-0"
                    style="height: 22px; line-height: 1; font-size: 0.75rem;" id="auctionPropertyModalType">-</span>
            </div>
        </div>
    </x-slot:header>

    <div id="auctionPropertyModalBody">
        <input type="hidden" id="modal_auction_prop_id">

        <div class="row g-4">
            {{-- القيمة التقديرية (Disabled) --}}
            <x-form.controls.input name="modal_auction_estimated_value" id="modal_auction_estimated_value"
                label="القيمة التقديرية" type="text" :price="true" col="col-md-4" :disabled="true"
                class="bg-light cursor-not-allowed" data-validation-skip="true" />

            {{-- قيمة افتتاح المزاد --}}
            <x-form.controls.input name="modal_auction_opening_value" id="modal_auction_opening_value"
                label="قيمة افتتاح المزاد" type="text" :price="true"
                :required="true" col="col-md-4" data-validation-skip="true" />

            {{-- مبلغ الدخول --}}
            <x-form.controls.input name="modal_auction_entry_amount" id="modal_auction_entry_amount" label="مبلغ الدخول"
                type="text" :price="true" :required="true" col="col-md-4" data-validation-skip="true" />

            {{-- نسبة الملكية المعروضة --}}
            <x-form.controls.input name="modal_auction_ownership_share" id="modal_auction_ownership_share"
                label="نسبة الملكية المعروضة (%)" type="number" min="0.01" max="100" step="any"
                :value="100" :required="true" col="col-md-6" data-validation-skip="true" />

            {{-- عدد ومقاس اللوحات --}}
            <x-form.controls.input name="modal_auction_board_count" id="modal_auction_board_count"
                label="عدد اللوحات" type="number" min="1" step="1" :value="1"
                :required="true" col="col-md-3" data-validation-skip="true" />

            <x-form.controls.input name="modal_auction_board_dimensions" id="modal_auction_board_dimensions"
                label="مقاس اللوحات" placeholder="مثال: 3م × 2م" maxlength="255" :required="true"
                col="col-md-3" data-validation-skip="true" />

            {{-- الإفصاح الخاص بالعقار --}}
            <x-form.controls.textarea name="modal_auction_disclosure" id="modal_auction_disclosure"
                label="الإفصاح الخاص بالعقار" col="col-12" rows="2" :required="true"
                placeholder="أدخل أي ملاحظات أو إفصاحات نظامية خاصة بهذا الأصل في المزاد..." data-validation-skip="true" />

            {{-- ملف التقييم وصور اللوحات --}}
            <div class="col-md-6">
                <x-form.controls.file name="modal_file_valuation" id="modal_file_valuation"
                    label="نموذج التقييم العقاري" accept=".pdf,.png,.jpg,.jpeg" col="col-12" :required="true"
                    help="ملف نموذج التقييم المعتمد (PDF أو صورة — حتى 10MB)." data-validation-skip="true" />
            </div>

            <div class="col-md-6">
                <x-form.controls.file name="modal_file_board_before[]" id="modal_file_board_before"
                    label="صور تركيب اللوحات" multiple accept=".png,.jpg,.jpeg,.webp" col="col-12"
                    :required="true" help="صور تركيب اللوحات على العقار (PNG, JPG, WEBP — حتى 10MB لكل صورة)." data-validation-skip="true" />
            </div>
        </div>
    </div>

    <x-slot:footer>
        <button type="button" class="btn btn-label-secondary" data-bs-dismiss="modal">
            <i class="ti ti-x me-1"></i>إلغاء
        </button>
        <button type="button" class="btn btn-primary" id="btn-save-auction-prop-modal">
            <i class="ti ti-check me-1"></i>حفظ
        </button>
    </x-slot:footer>
</x-bootstrap.modal>

<script>
    window.existingAuctionPropertiesData = @json($existingAuctionPropertiesData);
</script>
