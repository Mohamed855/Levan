@php($prefix = 'marketing[links]['.$index.']')
<div class="row g-3 mb-3 link-row" data-index="{{ $index }}">
    <x-form.controls.input :name="$prefix.'[label]'" label="تسمية الرابط" :value="$link['label'] ?? null" col="col-md-4" />
    <x-form.controls.select :name="$prefix.'[role]'" label="استخدام الرابط" :options="__('auction_reports.link_roles', [], 'ar')" :value="$link['role'] ?? 'other'" :select2="false" col="col-md-3" />
    <x-form.controls.input :name="$prefix.'[url]'" label="الرابط" type="url" :value="$link['url'] ?? null" col="col-md-4" />
    <div class="col-md-1 d-flex align-items-end"><button type="button" class="btn btn-icon btn-sm btn-danger remove-link" aria-label="حذف الرابط"><i class="ti ti-trash" aria-hidden="true"></i></button></div>
</div>
