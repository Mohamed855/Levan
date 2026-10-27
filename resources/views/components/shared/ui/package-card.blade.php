@props([
    'plan' => null,
    'preview' => false,
    'actionUrl' => null,
    'actionLabel' => 'اطلب الآن',
])

@php
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
    $activeAuctionsLimit = old('active_auctions_limit', $plan?->active_auctions_limit);
@endphp

<article {{ $attributes->class(['package-card'])->merge(['data-package-card' => $preview ? '' : null, 'data-plan-color' => $planColor]) }}>
    <header class="package-card__header">
        <span class="package-card__eyebrow" data-plan-field="type">{{ $planType }}</span>
        <h3 class="package-card__title" data-plan-field="name">{{ $planName }}</h3>
        <p class="package-card__description" data-plan-field="description">{{ $description }}</p>
    </header>

    <section class="package-card__price" aria-label="السعر">
        <span class="package-card__discount" data-plan-field="discount" @if ($discount <= 0) hidden @endif>
            وفر <span data-plan-field="discount-value">{{ $discount }}</span>%
        </span>
        <div class="package-card__price-line">
            <span class="package-card__price-value" data-plan-field="final-price">{{ number_format($finalPrice, 2) }}</span>
            <span class="package-card__currency">ر.س</span>
        </div>
        <span class="package-card__price-note" data-plan-field="billing-cycle">لكل {{ $billingCycle }}</span>
        <span class="package-card__original-price" data-plan-field="original-price" @if ($discount <= 0) hidden @endif>
            {{ number_format($price, 2) }} ر.س
        </span>
    </section>

    <section class="package-card__limits" aria-label="حدود الباقة">
        <div class="package-card__limit">
            <i class="ti ti-users" aria-hidden="true"></i>
            <span>المستخدمون</span>
            <strong data-plan-field="users">{{ $isUnlimited ? 'غير محدود' : (($includedUsers ?? '—') . ' مستخدم') }}</strong>
        </div>
        <div class="package-card__limit">
            <i class="ti ti-building-estate" aria-hidden="true"></i>
            <span>العقارات</span>
            <strong data-plan-field="properties">{{ $isUnlimited ? 'غير محدود' : (($propertiesLimit ?? '—') . ' عقار') }}</strong>
        </div>
        <div class="package-card__limit">
            <i class="ti ti-cloud" aria-hidden="true"></i>
            <span>التخزين</span>
            <strong data-plan-field="storage">{{ $isUnlimited ? 'غير محدود' : (($storageQuota ?? '—') . ' GB') }}</strong>
        </div>
        <div class="package-card__limit">
            <i class="ti ti-gavel" aria-hidden="true"></i>
            <span>المزادات</span>
            <strong data-plan-field="auctions">{{ $isUnlimited ? 'غير محدود' : (($activeAuctionsLimit ?? '—') . ' مزاد') }}</strong>
        </div>
    </section>

    @if ($preview || !empty($features))
        <section class="package-card__features">
            <h4>تشمل الباقة</h4>
            <ul data-plan-features>
                @forelse ($features as $feature)
                    @if (filled($feature))
                        <li><i class="ti ti-circle-check" aria-hidden="true"></i><span>{{ $feature }}</span></li>
                    @endif
                @empty
                    <li class="package-card__empty-feature">أضف ميزات الباقة لتظهر هنا.</li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($actionUrl)
        <a href="{{ $actionUrl }}" class="package-card__action">{{ $actionLabel }}<i class="ti ti-arrow-left" aria-hidden="true"></i></a>
    @endif
</article>
