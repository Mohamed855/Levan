@php
    $isEdit = isset($auction) && $auction->exists;
@endphp

<div class="row g-4">
    {{-- نتائج عقارات المزاد --}}
    <div class="col-12">
        <div class="d-flex align-items-center justify-content-between mb-2">
            <h6 class="fw-bold mb-0"><i class="ti ti-chart-bar me-1"></i>نتائج عقارات المزاد</h6>
            <small class="text-muted">النتائج اختيارية للمزادات المستقبلية، وتُسجَّل عند انتهاء المزاد.</small>
        </div>
        <div id="property-results-container">
            @if ($isEdit)
                @foreach ($auction->properties as $prop)
                    @php
                        $board = $prop->pivot->board_data ?? [];
                        $board = is_string($board) ? (json_decode($board, true) ?: []) : $board;
                        $result = ['status' => $prop->pivot->result_status] + collect($prop->pivot->getAttributes())->only(['opening_price', 'sale_price', 'participants_count', 'bids_count', 'unsold_reason'])->all();
                        foreach (['count', 'dimensions', 'location', 'notes'] as $key) { $result['board_'.$key] = $board[$key] ?? null; }
                        $result = old('property_results.'.$prop->id, $result);
                    @endphp
                    @include('tenant.auctions.partials.report-property-result', [
                        'propertyId' => $prop->id,
                        'propertyName' => $prop->name.' — '.$prop->property_number,
                        'result' => $result,
                    ])
                @endforeach
            @endif
            <p class="text-muted {{ $isEdit && $auction->properties->isNotEmpty() ? 'd-none' : '' }}" id="results-empty-msg">
                يرجى اختيار العقارات في الخطوة الثالثة لتسجيل نتائجها.
            </p>
        </div>
    </div>

    {{-- صور اللوحات الإعلانية --}}
    <div class="col-12 border-top pt-4">
        <h6 class="fw-bold mb-3"><i class="ti ti-billboard me-1"></i>صور اللوحات الإعلانية الميدانية</h6>
        <div class="row g-4">
            {{-- قبل الفك --}}
            <div class="col-md-6">
                <label class="form-label fw-bold mb-2">صور اللوحات قبل المزاد (إثبات التركيب)</label>
                <div id="board-before-container">
                    @if ($isEdit)
                        @foreach ($auction->properties as $prop)
                            @php
                                $existingBefore = $auction->attachments->where('report_section', 'board_before')->where('property_id', $prop->id);
                            @endphp
                            <div class="mb-3 p-3 border rounded" data-board-upload="before-{{ $prop->id }}">
                                <label class="form-label small fw-semibold mb-2">{{ $prop->name }} — إثبات التركيب</label>
                                @if ($existingBefore->isNotEmpty())
                                    <div class="assignment-file-grid mb-2">
                                        @foreach ($existingBefore as $att)
                                            <x-ui.file-card :file="$att" />
                                        @endforeach
                                    </div>
                                @endif
                                <x-form.controls.image-upload
                                    :name="'board_images_before['.$prop->id.'][]'"
                                    :id="'board_images_before_'.$prop->id"
                                    :label="null"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    compact
                                    col="col-12"
                                    title="اسحب أو اختر صور لوحات العقار"
                                    desc="صيغ JPG · PNG · WebP — أقصى حجم 10 ميجابايت"
                                />
                            </div>
                        @endforeach
                    @endif
                    <p class="text-muted small {{ $isEdit && $auction->properties->isNotEmpty() ? 'd-none' : '' }}" id="board-before-empty-msg">
                        اختر العقارات أولاً لإضافة صور لوحاتها.
                    </p>
                </div>
            </div>

            {{-- بعد الفك --}}
            <div class="col-md-6">
                <label class="form-label fw-bold mb-2">صور اللوحات بعد المزاد (إثبات الفك)</label>
                <div id="board-after-container">
                    @if ($isEdit)
                        @foreach ($auction->properties as $prop)
                            @php
                                $existingAfter = $auction->attachments->where('report_section', 'board_after')->where('property_id', $prop->id);
                            @endphp
                            <div class="mb-3 p-3 border rounded" data-board-upload="after-{{ $prop->id }}">
                                <label class="form-label small fw-semibold mb-2">{{ $prop->name }} — إثبات الفك</label>
                                @if ($existingAfter->isNotEmpty())
                                    <div class="assignment-file-grid mb-2">
                                        @foreach ($existingAfter as $att)
                                            <x-ui.file-card :file="$att" />
                                        @endforeach
                                    </div>
                                @endif
                                <x-form.controls.image-upload
                                    :name="'board_images_after['.$prop->id.'][]'"
                                    :id="'board_images_after_'.$prop->id"
                                    :label="null"
                                    accept="image/jpeg,image/png,image/webp"
                                    multiple
                                    compact
                                    col="col-12"
                                    title="اسحب أو اختر صور لوحات العقار"
                                    desc="صيغ JPG · PNG · WebP — أقصى حجم 10 ميجابايت"
                                />
                            </div>
                        @endforeach
                    @endif
                    <p class="text-muted small {{ $isEdit && $auction->properties->isNotEmpty() ? 'd-none' : '' }}" id="board-after-empty-msg">
                        اختر العقارات أولاً لإضافة صور لوحاتها.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- قوالب الجافاسكربت --}}
<template id="report-property-template">
    @include('tenant.auctions.partials.report-property-result', ['propertyId' => '__INDEX__', 'propertyName' => '', 'result' => []])
</template>

<template id="board-upload-before-template">
    <div class="mb-3 p-3 border rounded" data-board-upload="before-__PROPERTY_ID__">
        <label class="form-label small fw-semibold mb-2">__PROPERTY_NAME__ — إثبات التركيب</label>
        <x-form.controls.image-upload
            name="board_images_before[__PROPERTY_ID__][]"
            id="board_images_before___PROPERTY_ID__"
            :label="null"
            accept="image/jpeg,image/png,image/webp"
            multiple
            compact
            col="col-12"
            title="اسحب أو اختر صور لوحات العقار"
            desc="صيغ JPG · PNG · WebP — أقصى حجم 10 ميجابايت"
        />
    </div>
</template>

<template id="board-upload-after-template">
    <div class="mb-3 p-3 border rounded" data-board-upload="after-__PROPERTY_ID__">
        <label class="form-label small fw-semibold mb-2">__PROPERTY_NAME__ — إثبات الفك</label>
        <x-form.controls.image-upload
            name="board_images_after[__PROPERTY_ID__][]"
            id="board_images_after___PROPERTY_ID__"
            :label="null"
            accept="image/jpeg,image/png,image/webp"
            multiple
            compact
            col="col-12"
            title="اسحب أو اختر صور لوحات العقار"
            desc="صيغ JPG · PNG · WebP — أقصى حجم 10 ميجابايت"
        />
    </div>
</template>
