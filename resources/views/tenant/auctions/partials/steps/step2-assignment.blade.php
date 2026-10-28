@php
    $isEdit = isset($auction) && $auction->exists;
@endphp

<div class="row g-4">
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
        name="project_manager_name" 
        label="اسم مدير المشروع"
        placeholder="مثال: محمد عبدالله" 
        :value="old('project_manager_name', $auction->project_manager_name ?? '')"
        required 
        col="col-md-6" 
    />

    <x-form.controls.input 
        name="platform_name" 
        label="منصة المزاد"
        placeholder="مثال: منصة مباشر للمزادات" 
        :value="old('platform_name', $auction->platform_name ?? '')"
        required 
        col="col-md-6" 
    />

    <x-form.controls.input 
        name="platform_url" 
        type="url" 
        label="رابط المزاد على المنصة"
        placeholder="https://mubasher.sa/auctions/..." 
        :value="old('platform_url', $auction->platform_url ?? '')"
        col="col-md-6" 
    />

    <x-form.controls.textarea 
        name="notes" 
        label="ملاحظات الجهة والمنصة"
        placeholder="أي تفاصيل أو اشتراطات خاصة بالجهة أو المنصة..." 
        :value="old('notes', $auction->notes ?? '')"
        col="col-md-6"
        rows="3" 
    />
</div>
