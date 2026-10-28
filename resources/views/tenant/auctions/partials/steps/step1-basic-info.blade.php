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

    @if ($isEdit)
        {{-- الإسناد: readonly لأنه غير قابل للتغيير بعد الإنشاء --}}
        <div class="col-md-6">
            <label class="form-label">الإسناد المرتبط بالمزاد</label>
            <input type="text" class="form-control" readonly
                value="{{ $auction->assignment?->name }} ({{ $auction->assignment?->assignment_number }})">
            <input type="hidden" name="assignment_id" value="{{ $auction->assignment_id }}">
            <div class="form-text">لا يمكن تغيير الإسناد بعد إنشاء المزاد.</div>
        </div>
    @else
        <x-form.controls.select 
            name="assignment_id" 
            id="assignment_id" 
            label="الإسناد المرتبط بالمزاد"
            required 
            col="col-md-6" 
            placeholder="اختر الإسناد المسجل..." 
            :options="$assignments->map(fn($a) => [
                'value' => $a->id,
                'label' => $a->name . ' (' . $a->assignment_number . ')',
            ])->toArray()">
            <x-slot:append>
                <div id="selected-assignment-card" class="card bg-primary border-primary mt-3 d-none">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-2"><i class="ti ti-file-certificate me-1"></i>بيانات الإسناد المختار:</h6>
                        <div class="small">
                            <div><strong>رقم الإسناد:</strong> <span id="asg-summary-num">-</span></div>
                            <div><strong>الجهة المسندة:</strong> <span id="asg-summary-entity">-</span></div>
                            <div><strong>الحالة:</strong> <span id="asg-summary-status">-</span></div>
                            <div><strong>فترة الإسناد:</strong> <span id="asg-summary-dates">-</span></div>
                        </div>
                    </div>
                </div>
            </x-slot:append>
        </x-form.controls.select>
    @endif

    <x-form.controls.input 
        name="supervising_entity" 
        id="supervising_entity"
        label="الجهة المشرفة على المزاد" 
        placeholder="مثال: مركز الإسناد والتصفية - إنفاذ"
        :value="old('supervising_entity', $auction->supervising_entity ?? '')"
        required 
        col="col-md-6" 
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
