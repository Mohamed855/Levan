@php
    $latest = $template->versions->first();
    $schema = old('schema', $latest ? json_encode($latest->schema, JSON_UNESCAPED_UNICODE) : null);
    $isEdit = $template->exists;
@endphp

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card h-100"><div class="card-body">
            <x-shared.form.controls.input name="name" label="اسم القالب" :value="$template->name" required col="col-12" />
            @if ($isEdit)
                <x-shared.form.controls.input name="type_display" label="نوع القالب" :value="$template->type" disabled col="col-12" />
            @else
                <x-shared.form.controls.select name="type" label="نوع القالب" :value="$template->type" :options="['brochure' => 'كتيب مزاد متعدد الصفحات', 'billboard' => 'لوحة إعلانية', 'social' => 'منشور اجتماعي']" required :select2="false" col="col-12" />
            @endif
            <x-shared.form.controls.textarea name="description" label="وصف القالب" :value="$template->description" col="col-12" />
            <input type="hidden" name="is_active" value="0">
            <div class="form-check form-switch mt-2"><input class="form-check-input" id="is_active" name="is_active" value="1" type="checkbox" @checked(old('is_active', $template->is_active ?? true))><label class="form-check-label" for="is_active">القالب نشط</label></div>
            <hr>
            <h6>خصائص العنصر المحدد</h6><div data-properties></div>
        </div></div>
    </div>
    <div class="col-lg-8">
        <div class="card"><div class="card-header d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div><h5 class="mb-1">مصمم القالب</h5><small class="text-muted">ارسم المحتوى وحدد مواضع البيانات والصور على كل صفحة من القالب.</small></div>
            <div class="d-flex gap-2" data-page-tabs></div>
        </div><div class="card-body" data-template-designer data-type="{{ $isEdit ? $template->type : old('type', 'brochure') }}" data-schema="{{ e($schema) }}">
            <input type="hidden" name="schema" data-schema-input value="{{ $schema }}">
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="text">إضافة نص</button>
                <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="variable">إضافة متغير</button>
                <button class="btn btn-outline-primary btn-sm" type="button" data-add-element="image">إضافة صورة</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-add-page>إضافة صفحة</button>
                <label class="d-flex align-items-center gap-2 ms-auto small">لون الخلفية <input class="form-control form-control-color" data-background-color type="color" value="#ffffff"></label>
            </div>
            <div class="template-designer__canvas" data-canvas></div>
            @error('schema')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div></div>
    </div>
</div>
<div class="d-flex gap-2 mt-4"><a href="{{ route('admin.auction-templates.index') }}" class="btn btn-label-secondary">العودة للرئيسية</a>@if (! $isEdit)<button class="btn btn-label-primary" type="button" data-save-draft>حفظ مسودة</button>@endif<button class="btn btn-primary" type="submit">{{ $isEdit ? 'حفظ التعديلات' : 'إنشاء القالب' }}</button></div>
