@php($isEdit = $auction->exists)
@php($templateVersions = old('template_versions', $auction->template_versions ?? []))
<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">بيانات المزاد</h5>
            </div>
            <div class="card-body row g-3">
                <x-form.controls.select name="assignment_id" label="الإسناد" :value="$auction->assignment_id" :options="$assignments
                    ->map(
                        fn($assignment) => [
                            'value' => $assignment->id,
                            'label' => $assignment->assignment_number . ' — ' . $assignment->name,
                        ],
                    )
                    ->all()" required
                    :select2="false" col="col-md-4" />
                <div class="col-12">
                    <label class="form-label">العقارات المرتبطة</label>
                    <select class="form-select" name="property_ids[]" multiple data-auction-properties>
                        @foreach ($properties as $property)
                            <option value="{{ $property->id }}" data-assignment="{{ $property->assignment_id }}"
                                @selected(in_array($property->id, old('property_ids', $auction->properties->pluck('id')->all() ?? [])))>{{ $property->property_number }} — {{ $property->name }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">تظهر العقارات التابعة للإسناد المختار فقط.</small>
                </div>
                <x-form.controls.input name="name" label="اسم المزاد" :value="$auction->name" required col="col-md-8" />
                <x-form.controls.textarea name="description" label="وصف المزاد" :value="$auction->description" required
                    col="col-12" />
                <x-form.controls.input name="start_date" label="تاريخ البداية" type="date" :value="$auction->start_date?->format('Y-m-d')" required
                    col="col-md-3" />
                <x-form.controls.input name="start_time" label="وقت البداية" type="time" :value="$auction->start_time" required
                    col="col-md-3" />
                <x-form.controls.input name="end_date" label="تاريخ النهاية" type="date" :value="$auction->end_date?->format('Y-m-d')" required
                    col="col-md-3" />
                <x-form.controls.input name="end_time" label="وقت النهاية" type="time" :value="$auction->end_time" required
                    col="col-md-3" />
                <x-form.controls.select name="type" label="نوع المزاد" :value="$auction->type" :options="['electronic' => 'إلكتروني', 'in_person' => 'حضوري', 'hybrid' => 'هجين']" required
                    :select2="false" col="col-md-4" />
                <x-form.controls.select name="category" label="التصنيف" :value="$auction->category" :options="[
                    'residential' => 'سكني',
                    'commercial' => 'تجاري',
                    'investment' => 'استثماري',
                    'educational' => 'تعليمي',
                    'lands' => 'أراضٍ',
                    'mixed_use' => 'متعدد الاستخدامات',
                ]" required
                    :select2="false" col="col-md-4" />
                <x-form.controls.input name="city" label="المدينة" :value="$auction->city" required col="col-md-4" />
                <x-form.controls.input name="expected_properties_count" label="عدد العقارات المتوقع" type="number"
                    :value="$auction->expected_properties_count ?? 1" required col="col-md-4" />
                <x-form.controls.input name="supervising_entity" label="الجهة المشرفة" :value="$auction->supervising_entity"
                    col="col-md-4" />
                <x-form.controls.input name="platform_name" label="اسم المنصة" :value="$auction->platform_name" required
                    col="col-md-4" />
                <x-form.controls.input name="platform_url" label="رابط المنصة" type="url" :value="$auction->platform_url"
                    col="col-md-6" />
                <x-form.controls.textarea name="notes" label="ملاحظات" :value="$auction->notes" col="col-md-6" />
                <x-form.controls.image-upload name="cover_image" label="صورة الغلاف" :value="$auction->cover_image ? Storage::disk('public')->url($auction->cover_image) : null"
                    col="col-md-6" />
            </div>
        </div>
    </div>
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">قوالب العرض المنشورة</h5>
                <small class="text-muted">اختر النسخة التي سيستخدمها كل نوع من مواد العرض.</small>
            </div>
            <div class="card-body row g-3">
                @foreach (['brochure' => 'الكتيب', 'billboard' => 'اللوحات الإعلانية', 'social' => 'منشورات التواصل الاجتماعي'] as $key => $label)
                    <x-form.controls.select name="template_versions[{{ $key }}]" :label="$label"
                        :value="$templateVersions[$key] ?? null" :options="$templateOptions[$key]" :select2="false" col="col-md-4" />
                @endforeach
            </div>
        </div>
    </div>
</div>
<div class="d-flex gap-2 mt-4">
    <a class="btn btn-label-secondary" href="{{ route('tenant.auctions.index') }}">العودة للرئيسية</a>
    @if (!$isEdit)
        <button class="btn btn-label-primary" type="button" data-save-draft>
            <i class="ti ti-device-floppy me-1"></i>حفظ مسودة
        </button>
    @endif
    <button class="btn btn-primary" type="submit">{{ $isEdit ? 'حفظ التعديلات' : 'إنشاء المزاد' }}</button>
</div>
