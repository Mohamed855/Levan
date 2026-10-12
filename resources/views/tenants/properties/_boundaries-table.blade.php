<div class="d-flex align-items-center justify-content-between gap-2 mt-4 mb-3">
    <h6 class="mb-0"><i class="ti ti-ruler-2 me-1 text-primary"></i> حدود العقار وأطوالها</h6>
    <button type="button" class="btn btn-sm btn-primary" id="btn-add-border">
        <i class="ti ti-plus me-1"></i> إضافة حد جديد
    </button>
</div>

@php
    $boundaryDirectionOptions = [
        'north' => 'الشمال',
        'south' => 'الجنوب',
        'east' => 'الشرق',
        'west' => 'الغرب',
    ];
@endphp

<div class="table-responsive" data-form-field data-field-name="boundaries">
    <table class="table table-striped table-bordered w-100">
        <thead>
            <tr>
                <th style="width: 22%;">الجهة <span class="text-danger">*</span></th>
                <th style="width: 25%;">نوع الحد <span class="text-danger">*</span></th>
                <th style="width: 28%;">وصف الحد <span class="text-danger">*</span></th>
                <th style="width: 17%;">الطول (م) <span class="text-danger">*</span></th>
                <th style="width: 8%;" class="text-center">حذف</th>
            </tr>
        </thead>
        <tbody id="boundaries-list">
            @foreach ($bordersData as $idx => $border)
                <tr class="boundary-row">
                    <td>
                        <x-form.controls.select name="boundaries[{{ $idx }}][direction]"
                            id="boundaries-{{ $idx }}-direction" :options="$boundaryDirectionOptions"
                            :value="$border['direction'] ?? null" placeholder="اختر الجهة" required col="" />
                    </td>
                    <td>
                        <x-form.controls.input name="boundaries[{{ $idx }}][border_type]"
                            id="boundaries-{{ $idx }}-border-type" :value="$border['border_type'] ?? null"
                            placeholder="شارع / قطعة..." required col="" />
                    </td>
                    <td>
                        <x-form.controls.input name="boundaries[{{ $idx }}][border_description]"
                            id="boundaries-{{ $idx }}-border-description" :value="$border['border_description'] ?? null"
                            placeholder="عرض 20 متر..." required col="" />
                    </td>
                    <td>
                        <x-form.controls.input name="boundaries[{{ $idx }}][length]"
                            id="boundaries-{{ $idx }}-length" type="number" step="0.01"
                            :value="$border['length'] ?? null" placeholder="0.00" required col="" />
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-icon btn-sm btn-label-danger btn-remove-border" aria-label="حذف الحد">
                            <i class="ti ti-trash"></i>
                        </button>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<template id="boundary-row-template">
    <tr class="boundary-row">
        <td>
            <x-form.controls.select name="boundaries[__INDEX__][direction]"
                id="boundaries-__INDEX__-direction" :options="$boundaryDirectionOptions"
                placeholder="اختر الجهة" required col="" />
        </td>
        <td>
            <x-form.controls.input name="boundaries[__INDEX__][border_type]"
                id="boundaries-__INDEX__-border-type" placeholder="شارع / قطعة..." required col="" />
        </td>
        <td>
            <x-form.controls.input name="boundaries[__INDEX__][border_description]"
                id="boundaries-__INDEX__-border-description" placeholder="عرض 20 متر..." required col="" />
        </td>
        <td>
            <x-form.controls.input name="boundaries[__INDEX__][length]"
                id="boundaries-__INDEX__-length" type="number" step="0.01"
                placeholder="0.00" required col="" />
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-icon btn-sm btn-label-danger btn-remove-border" aria-label="حذف الحد">
                <i class="ti ti-trash"></i>
            </button>
        </td>
    </tr>
</template>
