<div class="card">
    <div class="card-body row g-3">
        <x-shared.form.controls.input name="assignment_number" label="رقم الإسناد" :value="$assignment->assignment_number" required col="col-md-4" />
        <x-shared.form.controls.input name="name" label="اسم الإسناد" :value="$assignment->name" required col="col-md-8" />
        <x-shared.form.controls.input name="issuing_entity" label="الجهة المصدرة" :value="$assignment->issuing_entity" required col="col-md-6" />
        <x-shared.form.controls.input name="city" label="المدينة" :value="$assignment->city" required col="col-md-3" />
        <x-shared.form.controls.input name="district" label="الحي" :value="$assignment->district" col="col-md-3" />
        <x-shared.form.controls.select name="category" label="التصنيف" :value="$assignment->category" :options="['lands' => 'أراضٍ', 'villas' => 'فلل', 'commercial_buildings' => 'مبانٍ تجارية', 'educational' => 'تعليمي', 'industrial' => 'صناعي']" required :select2="false" col="col-md-4" />
        <x-shared.form.controls.input name="start_date" label="تاريخ البداية" type="date" :value="$assignment->start_date?->format('Y-m-d')" required col="col-md-4" />
        <x-shared.form.controls.input name="end_date" label="تاريخ النهاية" type="date" :value="$assignment->end_date?->format('Y-m-d')" required col="col-md-4" />
        <x-shared.form.controls.textarea name="notes" label="ملاحظات" :value="$assignment->notes" required col="col-12" />
        <div class="col-12"><label class="form-label">العقارات</label><select class="form-select" multiple name="property_ids[]">@foreach($properties as $property)<option value="{{ $property->id }}" @selected(in_array($property->id, old('property_ids', $assignment->properties->pluck('id')->all() ?? [])))>{{ $property->property_number }} — {{ $property->name }}</option>@endforeach</select></div>
    </div>
</div>
<div class="mt-4 d-flex gap-2"><a class="btn btn-label-secondary" href="{{ route('tenant.assignments.index') }}">العودة للرئيسية</a>@if (! $assignment->exists)<button class="btn btn-label-primary" type="button" data-save-draft>حفظ مسودة</button>@endif<button class="btn btn-primary">{{ $assignment->exists ? 'حفظ التعديلات' : 'إنشاء الإسناد' }}</button></div>
