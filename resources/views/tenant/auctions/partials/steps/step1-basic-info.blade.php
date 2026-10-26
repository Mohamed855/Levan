@php
    $isEdit = isset($auction) && $auction->exists;
@endphp

<div class="row g-4">
    <x-form.controls.input 
        name="name" 
        label="اسم المزاد" 
        placeholder="مثال: مزاد جوار الحرم"
        :value="old('name', $auction->name ?? '')"
        required 
        col="col-md-6" 
    />

    <x-form.controls.textarea 
        name="description" 
        label="وصف المزاد"
        placeholder="اكتب وصفاً توضيحياً للمزاد..." 
        :value="old('description', $auction->description ?? '')"
        required 
        col="col-md-6" 
        rows="3" 
    />

    <x-form.controls.input 
        name="start_date" 
        type="date" 
        label="تاريخ بداية المزاد" 
        :value="old('start_date', $isEdit && $auction->start_date ? $auction->start_date->format('Y-m-d') : '')"
        required
        col="col-md-6" 
    />
    
    <x-form.controls.input 
        name="start_time" 
        type="time" 
        label="وقت البداية" 
        :value="old('start_time', $auction->start_time ?? '')"
        required
        col="col-md-6" 
    />

    <x-form.controls.input 
        name="end_date" 
        type="date" 
        label="تاريخ نهاية المزاد" 
        :value="old('end_date', $isEdit && $auction->end_date ? $auction->end_date->format('Y-m-d') : '')"
        required
        col="col-md-6" 
    />
    
    <x-form.controls.input 
        name="end_time" 
        type="time" 
        label="وقت النهاية" 
        :value="old('end_time', $auction->end_time ?? '')"
        required
        col="col-md-6" 
    />

    <x-form.controls.select 
        name="type" 
        label="نوع المزاد" 
        :value="old('type', $auction->type ?? '')"
        required 
        col="col-md-6"
        :enum="\App\Enums\Tenants\Auctions\AuctionType::class" 
    />

    <x-form.controls.select 
        name="category" 
        label="تصنيف المزاد" 
        :value="old('category', $auction->category ?? '')"
        required 
        col="col-md-6"
        :enum="\App\Enums\Tenants\Auctions\AuctionCategory::class" 
    />

    <x-form.controls.select 
        name="city" 
        label="المدينة الرئيسية" 
        :value="old('city', $auction->city ?? '')"
        required 
        col="col-md-6"
        :options="$cityOptions" 
    />

    <x-form.controls.input 
        name="expected_properties_count" 
        type="number"
        label="عدد العقارات المتوقع" 
        :value="old('expected_properties_count', $auction->expected_properties_count ?? 1)" 
        min="1" 
        required 
        col="col-md-6" 
    />
</div>
