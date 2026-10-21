<div class="card">
    <div class="card-body">
        <div class="row g-4">
            <x-shared.form.controls.input name="name" label="اسم الباقة" :value="$plan->name" required col="col-md-6" />
            <x-shared.form.controls.input name="code" label="رمز الباقة" :value="$plan->code" required col="col-md-6" />

            <x-shared.form.controls.select name="plan_type" label="نوع الباقة" :value="$plan->plan_type" :options="['basic' => 'أساسية', 'professional' => 'احترافية', 'enterprise' => 'مؤسسية']" :select2="false" required col="col-md-4" />
            <x-shared.form.controls.input name="color" label="اللون" :value="$plan->color" required col="col-md-4" />
            <x-shared.form.controls.select name="subscription_billing_cycle" label="دورة الفوترة" :value="$plan->subscription_billing_cycle" :options="['monthly' => 'شهري', 'quarterly' => 'ربع سنوي', 'yearly' => 'سنوي']" :select2="false" required col="col-md-4" />

            <x-shared.form.controls.input name="price" type="number" label="السعر" :value="$plan->price" min="0" step="0.01" required col="col-md-4" />
            <x-shared.form.controls.input name="discount_percentage" type="number" label="نسبة الخصم" :value="$plan->discount_percentage" min="0" max="100" step="0.01" col="col-md-4" />
            <x-shared.form.controls.select name="limits_mode" label="وضع الحدود" :value="$plan->limits_mode" :options="['limited' => 'محدودة', 'unlimited' => 'غير محدودة']" :select2="false" required col="col-md-4" />

            <x-shared.form.controls.input name="included_users" type="number" label="حد المستخدمين" :value="$plan->included_users" min="1" col="col-md-3" />
            <x-shared.form.controls.input name="properties_limit" type="number" label="حد العقارات" :value="$plan->properties_limit" min="1" col="col-md-3" />
            <x-shared.form.controls.input name="storage_quota_gb" type="number" label="التخزين بالجيجابايت" :value="$plan->storage_quota_gb" min="1" col="col-md-3" />
            <x-shared.form.controls.input name="active_auctions_limit" type="number" label="حد المزادات النشطة" :value="$plan->active_auctions_limit" min="1" col="col-md-3" />

            <x-shared.form.controls.textarea name="description" label="الوصف" :value="$plan->description" col="col-12" rows="4" />
            <x-shared.form.controls.textarea name="features_text" label="المزايا" :value="implode(PHP_EOL, old('features', $plan->features ?? []))" required help="أضف ميزة واحدة في كل سطر." col="col-12" rows="5" />

            <input type="hidden" name="is_active" value="0">
            <input type="hidden" name="domain_management_enabled" value="0">
            <x-shared.form.controls.checkbox name="is_active" label="الباقة نشطة" :checked="$plan->is_active ?? true" col="col-md-6">يمكن عرض الباقة للعملاء عند تفعيلها.</x-shared.form.controls.checkbox>
            <x-shared.form.controls.checkbox name="domain_management_enabled" label="إدارة النطاقات" :checked="$plan->domain_management_enabled" col="col-md-6">تتيح للعميل إدارة نطاقه ضمن اشتراك الباقة.</x-shared.form.controls.checkbox>
        </div>
    </div>
</div>

<div class="mt-4 d-flex gap-2">
    <a class="btn btn-label-secondary" href="{{ route('admin.plans.index') }}">العودة للرئيسية</a>
    @if (! $plan->exists)
        <button class="btn btn-label-primary" type="button" data-save-draft>حفظ كمسودة</button>
    @endif
    <button class="btn btn-primary" type="submit">{{ $plan->exists ? 'حفظ التعديلات' : 'إنشاء الباقة' }}</button>
</div>
