@php
    $isEdit = isset($auction) && $auction->exists;
@endphp

<div class="row g-4">
    <x-form.controls.input 
        name="platform_name" 
        id="platform_name"
        label="منصة المزاد" 
        placeholder="مثال: منصة مباشر للمزادات" 
        :value="old('platform_name', $auction->platform_name ?? '')"
        required 
        col="col-md-6" 
    />

    <x-form.controls.input 
        name="platform_url" 
        id="platform_url"
        type="url" 
        label="رابط المزاد على المنصة" 
        placeholder="https://mubasher.sa/auctions/..." 
        :value="old('platform_url', $auction->platform_url ?? '')"
        col="col-md-6" 
    />

    <x-form.controls.input 
        name="project_manager_name" 
        id="project_manager_name"
        label="اسم مدير المشروع" 
        placeholder="مثال: محمد عبدالله" 
        :value="old('project_manager_name', $auction->project_manager_name ?? '')"
        required 
        col="col-md-6" 
    />

    <x-form.controls.textarea 
        name="notes" 
        id="notes"
        label="ملاحظات المنصة والمشروع" 
        placeholder="أي تفاصيل أو اشتراطات خاصة بالمنصة أو إدارة المشروع..." 
        :value="old('notes', $auction->notes ?? '')"
        col="col-md-6" 
        rows="3" 
    />
</div>
