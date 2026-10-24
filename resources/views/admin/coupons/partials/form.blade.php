<section class="card">
    <div class="card-body">
        <div class="row g-3">
            <x-shared.form.controls.input name="name" label="الاسم" :value="old('name', $coupon->name)" required/>
            <x-shared.form.controls.input name="code" label="الكود" :value="old('code', $coupon->code)" required/>
            <x-shared.form.controls.select name="discount_type" label="نوع الخصم" :options="['percentage' => 'نسبة مئوية', 'fixed' => 'قيمة ثابتة']" :value="old('discount_type', $coupon->discount_type)" required/>
            <x-shared.form.controls.input name="discount_value" type="number" min="0.01" step="0.01" label="قيمة الخصم" :value="old('discount_value', $coupon->discount_value)" required/>
            <x-shared.form.controls.input name="max_discount" type="number" min="0" step="0.01" label="الحد الأقصى لقيمة الخصم (اختياري)" :value="old('max_discount', $coupon->max_discount)"/>
            <x-shared.form.controls.input name="expires_at" type="date" label="تاريخ الانتهاء" :value="old('expires_at', $coupon->expires_at?->format('Y-m-d'))"/>
            <x-shared.form.controls.input name="usage_limit" type="number" min="1" label="حد الاستخدام (اختياري)" :value="old('usage_limit', $coupon->usage_limit)"/>
            <x-shared.form.controls.select name="is_active" label="الحالة" :options="['1' => 'نشط', '0' => 'غير نشط']" :value="old('is_active', $coupon->exists ? (int) $coupon->is_active : 1)" required/>
        </div>
    </div>
</section>

<button class="btn btn-primary mt-4">حفظ</button>
