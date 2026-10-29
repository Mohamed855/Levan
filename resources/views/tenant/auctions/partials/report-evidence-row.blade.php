@php($meta = $meta ?? [])
<fieldset class="row g-3 border rounded p-3 mb-3" data-evidence-index="{{ $index }}">
    <legend class="float-none w-auto fs-6" data-evidence-name>{{ $filename ?? 'تصنيف المرفق' }}</legend>
    <x-form.controls.input :name="$prefix . '[display_name]'" label="اسم المرفق / الوسيط" :value="$meta['display_name'] ?? null" col="col-md-6" />
    <x-form.controls.select :name="$prefix . '[section]'" label="قسم التقرير" :options="collect(__('auction_reports.sections', [], 'ar'))->except('campaigns')->all()" :value="$meta['section'] ?? 'marketing_evidence'" :select2="false"
        col="col-md-3" />
    <x-form.controls.select :name="$prefix . '[channel]'" label="القناة" :options="__('auction_reports.channels', [], 'ar')" :value="$meta['channel'] ?? null" :select2="false"
        col="col-md-3" />
    <x-form.controls.select :name="$prefix . '[kind]'" label="نوع المحتوى" :options="__('auction_reports.kinds', [], 'ar')" :value="$meta['kind'] ?? 'screenshot'"
        :select2="false" col="col-md-3" />
    <x-form.controls.select :name="$prefix . '[fit]'" label="عرض المرفق" :options="__('auction_reports.fits', [], 'ar')" :value="$meta['fit'] ?? null"
        :select2="false" col="col-md-3" />
    <x-form.controls.select :name="$prefix . '[link_role]'" label="دور الرابط المرتبط" :options="__('auction_reports.link_roles', [], 'ar')" :value="$meta['link_role'] ?? null"
        :select2="false" col="col-md-3" />
    <x-form.controls.input :name="$prefix . '[display_order]'" label="ترتيب العرض" type="number" :value="$meta['display_order'] ?? (is_numeric($index) ? $index : 0)" col="col-md-3" />
    <x-form.controls.input :name="$prefix . '[focal_x]'" label="نقطة التركيز الأفقية (%)" type="number" :value="$meta['focal_x'] ?? 50"
        step="any" col="col-md-6" />
    <x-form.controls.input :name="$prefix . '[focal_y]'" label="نقطة التركيز الرأسية (%)" type="number" :value="$meta['focal_y'] ?? 50"
        step="any" col="col-md-6" />
</fieldset>
