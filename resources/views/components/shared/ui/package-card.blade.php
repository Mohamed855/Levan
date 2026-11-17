@props([
    'plan' => null,
    'preview' => false,
    'actionUrl' => null,
    'actionLabel' => 'اطلب الآن',
    'isPopular' => false,
])

@php
    $isPopular = (bool) $isPopular;
    $rawColor = old('color', $plan?->color instanceof \BackedEnum ? $plan->color->value : $plan?->color ?? 'primary');
    $planColorEnum =
        $rawColor instanceof \App\Enums\Admin\Plans\PlanColor
            ? $rawColor
            : \App\Enums\Admin\Plans\PlanColor::tryFrom((string) $rawColor);

    $planColorValue = $planColorEnum?->value ?? (is_string($rawColor) ? $rawColor : 'primary');
    $planColorHex =
        $plan?->color_hex ??
        ($planColorEnum?->hex() ?? (is_string($rawColor) && str_starts_with($rawColor, '#') ? $rawColor : '#1D4F44'));

    $planName = old('name', $plan?->name ?? 'اسم الباقة');
    $planTypeValue = old('plan_type', $plan?->plan_type?->value ?? ($plan?->plan_type ?? ''));
    $planType = \App\Enums\Admin\Plans\PlanType::tryFrom($planTypeValue)?->label() ?? 'باقة مخصصة';
    $description = old('description', $plan?->description ?? 'أضف وصفاً واضحاً يشرح قيمة الباقة لعملائك.');
    $price = (float) old('price', $plan?->price ?? 0);
    $discount = (float) old('discount_percentage', $plan?->discount_percentage ?? 0);
    $finalPrice = max(0, $price - ($price * $discount) / 100);
    $billingCycleValue = old(
        'subscription_billing_cycle',
        $plan?->subscription_billing_cycle?->value ?? ($plan?->subscription_billing_cycle ?? ''),
    );
    $billingCycle =
        \App\Enums\Admin\Subscriptions\SubscriptionBillingCycle::tryFrom($billingCycleValue)?->label() ?? 'شهرياً';
    $limitsMode = old('limits_mode', $plan?->limits_mode?->value ?? ($plan?->limits_mode ?? 'limited'));
    $isUnlimited = $limitsMode === 'unlimited' || $plan?->isUnlimited();
    $features = array_values(
        array_filter(old('features', $plan?->features ?? []), static fn($feature) => filled($feature)),
    );
    $includedUsers = old('included_users', $plan?->included_users);
    $hasUnlimitedUsers = $plan ? $plan->hasUnlimitedUsers() : $includedUsers === null || $includedUsers === '';
    $extraLicensePrice = (float) old('extra_user_license_price', $plan?->extra_user_license_price ?? 0);
    $propertiesLimit = old('properties_limit', $plan?->properties_limit);
    $storageQuota = old('storage_quota_gb', $plan?->storage_quota_gb);
    $activeAuctionsLimit = old('active_auctions_limit', $plan?->active_auctions_limit);

    $styleAttr = "--package-card-accent: {$planColorHex};";
    if ($existingStyle = $attributes->get('style')) {
        $styleAttr = rtrim($existingStyle, '; ') . '; ' . $styleAttr;
    }
@endphp

<article
    {{ $attributes->except('style')->class(['package-card', 'package-card--popular' => $isPopular])->merge([
            'data-package-card' => $preview ? '' : null,
            'data-plan-color' => $planColorValue,
            'style' => $styleAttr,
        ]) }}>
    <header class="package-card__header">
        <div class="package-card__eyebrow-wrap">
            <span class="package-card__eyebrow" data-plan-field="type">{{ $planType }}</span>
            @if ($isPopular)
                <span class="package-card__popular-badge" aria-label="الباقة الأكثر طلباً">
                    <i class="ti ti-flame" aria-hidden="true"></i>
                    <span>الأكثر طلباً</span>
                </span>
            @endif
        </div>
        <h3 class="package-card__title" data-plan-field="name">{{ $planName }}</h3>
        <p class="package-card__description" data-plan-field="description">{{ $description }}</p>
    </header>

    <section class="package-card__price" aria-label="السعر">
        <span class="package-card__discount" data-plan-field="discount" @if ($discount <= 0) hidden @endif>
            وفر <span data-plan-field="discount-value">{{ $discount }}</span>%
        </span>
        <div class="package-card__price-line">
            <span class="package-card__price-value"
                data-plan-field="final-price">{{ number_format($finalPrice, 2) }}</span>
            <span class="package-card__currency"><span class="icon-saudi_riyal icon-saudi_riyal--dark"></span></span>
        </div>
        <span class="package-card__price-note" data-plan-field="billing-cycle">{{ $billingCycle }}</span>
        <span class="package-card__original-price" data-plan-field="original-price"
            @if ($discount <= 0) hidden @endif>
            {{ number_format($price, 2) }} <span class="icon-saudi_riyal icon-saudi_riyal--dark ms-1"></span>
        </span>
    </section>

    <section class="package-card__limits" aria-label="حدود الباقة">
        <div class="package-card__limit">
            <i class="ti ti-user" aria-hidden="true"></i>
            <span>المستخدمون</span>
            @if ($hasUnlimitedUsers)
                <strong data-plan-field="users">غير محدود</strong>
            @else
                <strong data-plan-field="users">{{ ($includedUsers ?? '—') . ' مستخدم' }}</strong>
                @if ($extraLicensePrice > 0)
                    <small class="package-card__extra-note">
                        +{{ number_format($extraLicensePrice, 2) }} <span
                            class="icon-saudi_riyal icon-saudi_riyal--dark"></span> / مستخدم إضافي
                    </small>
                @endif
            @endif
        </div>
        <div class="package-card__limit">
            <i class="ti ti-building-estate" aria-hidden="true"></i>
            <span>العقارات</span>
            <strong
                data-plan-field="properties">{{ $isUnlimited ? 'غير محدود' : ($propertiesLimit ?? '—') . ' عقار' }}</strong>
        </div>
        <div class="package-card__limit">
            <i class="ti ti-cloud" aria-hidden="true"></i>
            <span>التخزين</span>
            <strong
                data-plan-field="storage">{{ $isUnlimited ? 'غير محدود' : ($storageQuota ?? '—') . ' GB' }}</strong>
        </div>
        <div class="package-card__limit">
            <i class="ti ti-gavel" aria-hidden="true"></i>
            <span>المزادات</span>
            <strong
                data-plan-field="auctions">{{ $isUnlimited ? 'غير محدود' : ($activeAuctionsLimit ?? '—') . ' مزاد' }}</strong>
        </div>
    </section>

    @if ($preview || !empty($features))
        <section class="package-card__features">
            <h4>تشمل الباقة</h4>
            <ul data-plan-features>
                @forelse ($features as $feature)
                    <li><i class="ti ti-circle-check" aria-hidden="true"></i><span>{{ $feature }}</span></li>
                @endforelse
            </ul>
        </section>
    @endif

    @if ($actionUrl)
        <a href="{{ $actionUrl }}" class="package-card__action">{{ $actionLabel }}<i class="ti ti-arrow-left"
                aria-hidden="true"></i></a>
    @endif
</article>
