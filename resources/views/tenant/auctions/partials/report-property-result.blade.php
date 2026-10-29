@php
    $prefix = 'property_results['.$propertyId.']';
    $result = $result ?? [];
@endphp
<article class="card mb-3 border" data-property-result="{{ $propertyId }}">
    <header class="card-header py-2"><h6 class="mb-0" data-result-title>{{ $propertyName ?? 'نتيجة العقار' }}</h6></header>
    <div class="card-body"><div class="row g-3">
        <x-form.controls.select :name="$prefix.'[status]'" label="الحالة" :options="__('auction_reports.property_result_statuses', [], 'ar')" :value="$result['status'] ?? 'pending'" :select2="true" col="col-md-4" />
        @foreach (['opening_price' => 'سعر الافتتاح', 'sale_price' => 'سعر البيع', 'participants_count' => 'عدد المشاركين', 'bids_count' => 'عدد المزايدات', 'board_count' => 'عدد اللوحات'] as $key => $label)
            <x-form.controls.input :name="$prefix.'['.$key.']'" :label="$label" type="number" :value="$result[$key] ?? null" :step="in_array($key, ['opening_price', 'sale_price'], true) ? '0.01' : '1'" col="col-md-4" />
        @endforeach
        @foreach (['unsold_reason' => 'سبب عدم البيع', 'board_dimensions' => 'مقاس اللوحة', 'board_location' => 'موقع اللوحة', 'board_notes' => 'ملاحظات اللوحة'] as $key => $label)
            <x-form.controls.input :name="$prefix.'['.$key.']'" :label="$label" :value="$result[$key] ?? null" col="col-md-6" />
        @endforeach
    </div></div>
</article>
