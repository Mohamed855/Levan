<div class="row g-4">
    <div class="col-12">
        <div class="alert alert-info mb-0">
            <i class="ti ti-info-circle me-1"></i>
            تُؤخذ بيانات الملاك والصك والمساحة والاستخدام والموقع والوكالة المسجلة من ملف العقار عند الحفظ. تُستخدم القيم المدخلة لاستكمال البيانات الناقصة فقط.
        </div>
    </div>

    @forelse ($data['properties'] as $index => $propertyData)
        @php
            $property = $auction->properties->firstWhere('id', $propertyData['property_id']);
        @endphp
        <div class="col-12 {{ !$loop->first ? 'border-top pt-4' : '' }}">
            <div class="d-flex align-items-center justify-content-between mb-3">
                <h6 class="fw-bold mb-0">
                    <i class="ti ti-building me-1"></i>{{ $property?->name ?? 'عقار' }} — {{ $property?->property_number ?? '-' }}
                </h6>
                <span class="badge bg-label-primary">{{ $property?->type_label ?? 'عقار' }}</span>
            </div>
            <input type="hidden" name="data[properties][{{ $index }}][property_id]" value="{{ $propertyData['property_id'] }}">
            <div class="row g-4">
                @foreach ($schema->propertyFields() as $key => $field)
                    @include('tenant.auction-establishment-requests.field', [
                        'path' => 'properties.' . $index . '.' . $key,
                        'readonly' => false,
                    ])
                @endforeach
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="alert alert-warning mb-0">لا توجد عقارات مرتبطة بهذا المزاد.</div>
        </div>
    @endforelse
</div>
