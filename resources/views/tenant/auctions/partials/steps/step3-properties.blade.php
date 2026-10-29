@php
    $isEdit = isset($auction) && $auction->exists;
    $initialCount = $isEdit ? $auction->properties->count() : 0;
    $initialProperties = $isEdit ? $auction->properties : collect([]);
    $fallbackImage = asset('static/brand/logos/logo.svg');
@endphp

<div data-form-field data-field-name="property_ids">
    <div id="selected-property-inputs">
        @if ($isEdit && $auction->properties)
            @foreach ($auction->properties as $prop)
                <input type="hidden" name="property_ids[]" value="{{ $prop->id }}" data-property-input="{{ $prop->id }}">
            @endforeach
        @endif
    </div>

    <x-tenants.properties.property-selection-table
        title="العقارات المرتبطة بالإسناد المختار"
        subtitle="حدد العقارات التي سيتم إدراجها رسمياً في هذا المزاد."
        :showStats="true"
        :initialCount="$initialCount"
        tableId="auction-properties-table"
    />

    <div id="property_ids-error" class="invalid-feedback d-block" data-form-error role="alert" hidden></div>
</div>

<x-tenants.properties.selected-properties-box
    title="العقارات المحددة"
    :count="$initialCount"
    :properties="$initialProperties"
    :fallbackImage="$fallbackImage"
    id="selected-properties-cards"
    countId="selected-properties-count"
/>
