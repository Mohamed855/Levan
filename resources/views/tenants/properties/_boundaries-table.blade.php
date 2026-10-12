<div class="d-flex align-items-center justify-content-between gap-2 mt-4 mb-3">
    <h6 class="mb-0"><i class="ti ti-ruler-2 me-1 text-primary"></i> حدود العقار وأطوالها</h6>
    <button type="button" class="btn btn-sm btn-primary" id="btn-add-border">
        <i class="ti ti-plus me-1"></i> إضافة حد جديد
    </button>
</div>

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
                        <select name="boundaries[{{ $idx }}][direction]" class="form-select form-select-sm" data-form-control required>
                            <option value="north" {{ ($border['direction'] ?? '') === 'north' ? 'selected' : '' }}>الشمال</option>
                            <option value="south" {{ ($border['direction'] ?? '') === 'south' ? 'selected' : '' }}>الجنوب</option>
                            <option value="east" {{ ($border['direction'] ?? '') === 'east' ? 'selected' : '' }}>الشرق</option>
                            <option value="west" {{ ($border['direction'] ?? '') === 'west' ? 'selected' : '' }}>الغرب</option>
                        </select>
                    </td>
                    <td>
                        <input type="text" name="boundaries[{{ $idx }}][border_type]" class="form-control form-control-sm" value="{{ $border['border_type'] ?? '' }}" placeholder="شارع / قطعة..." data-form-control required>
                    </td>
                    <td>
                        <input type="text" name="boundaries[{{ $idx }}][border_description]" class="form-control form-control-sm" value="{{ $border['border_description'] ?? '' }}" placeholder="عرض 20 متر..." data-form-control required>
                    </td>
                    <td>
                        <input type="number" step="0.01" name="boundaries[{{ $idx }}][length]" class="form-control form-control-sm" value="{{ $border['length'] ?? '' }}" placeholder="0.00" data-form-control required>
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
