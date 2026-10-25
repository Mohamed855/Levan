<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
    'plan' => null,
    'preview' => false,
    'actionUrl' => null,
    'actionLabel' => 'اطلب الآن',
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
    'plan' => null,
    'preview' => false,
    'actionUrl' => null,
    'actionLabel' => 'اطلب الآن',
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
    $planColor = old('color', $plan?->color?->value ?? 'purple');
    $planName = old('name', $plan?->name ?? 'اسم الباقة');
    $planTypeValue = old('plan_type', $plan?->plan_type?->value ?? '');
    $planType = \App\Enums\Admin\Plans\PlanType::tryFrom($planTypeValue)?->label() ?? 'باقة مخصصة';
    $description = old('description', $plan?->description ?? 'أضف وصفاً واضحاً يشرح قيمة الباقة لعملائك.');
    $price = (float) old('price', $plan?->price ?? 0);
    $discount = (float) old('discount_percentage', $plan?->discount_percentage ?? 0);
    $finalPrice = max(0, $price - ($price * $discount / 100));
    $billingCycleValue = old('subscription_billing_cycle', $plan?->subscription_billing_cycle?->value ?? '');
    $billingCycle = \App\Enums\Admin\Subscriptions\SubscriptionBillingCycle::tryFrom($billingCycleValue)?->label() ?? 'شهري';
    $limitsMode = old('limits_mode', $plan?->limits_mode?->value ?? 'limited');
    $isUnlimited = $limitsMode === 'unlimited' || $plan?->isUnlimited();
    $features = array_values(array_filter(old('features', $plan?->features ?? []), static fn ($feature) => filled($feature)));
    $includedUsers = old('included_users', $plan?->included_users);
    $propertiesLimit = old('properties_limit', $plan?->properties_limit);
    $storageQuota = old('storage_quota_gb', $plan?->storage_quota_gb);
?>

<article <?php echo e($attributes->class(['package-card'])->merge(['data-package-card' => $preview ? '' : null, 'data-plan-color' => $planColor])); ?>>
    <header class="package-card__header">
        <span class="package-card__eyebrow" data-plan-field="type"><?php echo e($planType); ?></span>
        <h3 class="package-card__title" data-plan-field="name"><?php echo e($planName); ?></h3>
        <p class="package-card__description" data-plan-field="description"><?php echo e($description); ?></p>
    </header>

    <section class="package-card__price" aria-label="السعر">
        <span class="package-card__discount" data-plan-field="discount" <?php if($discount <= 0): ?> hidden <?php endif; ?>>
            وفر <span data-plan-field="discount-value"><?php echo e($discount); ?></span>%
        </span>
        <div class="package-card__price-line">
            <span class="package-card__price-value" data-plan-field="final-price"><?php echo e(number_format($finalPrice, 2)); ?></span>
            <span class="package-card__currency">ر.س</span>
        </div>
        <span class="package-card__price-note" data-plan-field="billing-cycle">لكل <?php echo e($billingCycle); ?></span>
        <span class="package-card__original-price" data-plan-field="original-price" <?php if($discount <= 0): ?> hidden <?php endif; ?>>
            <?php echo e(number_format($price, 2)); ?> ر.س
        </span>
    </section>

    <section class="package-card__limits" aria-label="حدود الباقة">
        <div class="package-card__limit">
            <i class="ti ti-users" aria-hidden="true"></i>
            <span>المستخدمون</span>
            <strong data-plan-field="users"><?php echo e($isUnlimited ? 'غير محدود' : (($includedUsers ?? '—') . ' مستخدم')); ?></strong>
        </div>
        <div class="package-card__limit">
            <i class="ti ti-building-estate" aria-hidden="true"></i>
            <span>العقارات</span>
            <strong data-plan-field="properties"><?php echo e($isUnlimited ? 'غير محدود' : (($propertiesLimit ?? '—') . ' عقار')); ?></strong>
        </div>
        <div class="package-card__limit">
            <i class="ti ti-cloud" aria-hidden="true"></i>
            <span>التخزين</span>
            <strong data-plan-field="storage"><?php echo e($isUnlimited ? 'غير محدود' : (($storageQuota ?? '—') . ' GB')); ?></strong>
        </div>
    </section>

    <section class="package-card__features">
        <h4>تشمل الباقة</h4>
        <ul data-plan-features>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::openLoop(); ?><?php endif; ?><?php $__empty_1 = true; $__currentLoopData = $features; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feature): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::startLoopIteration(); ?><?php endif; ?>
                <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(filled($feature)): ?>
                    <li><i class="ti ti-circle-check" aria-hidden="true"></i><span><?php echo e($feature); ?></span></li>
                <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::endLoop(); ?><?php endif; ?><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><?php \Livewire\Features\SupportCompiledWireKeys\SupportCompiledWireKeys::closeLoop(); ?><?php endif; ?>
                <li class="package-card__empty-feature">أضف ميزات الباقة لتظهر هنا.</li>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </ul>
    </section>

    <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if($actionUrl): ?>
        <a href="<?php echo e($actionUrl); ?>" class="package-card__action"><?php echo e($actionLabel); ?><i class="ti ti-arrow-left" aria-hidden="true"></i></a>
    <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
</article>
<?php /**PATH D:\Levan\resources\views/components/shared/ui/package-card.blade.php ENDPATH**/ ?>