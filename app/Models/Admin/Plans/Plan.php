<?php

declare(strict_types=1);

namespace App\Models\Admin\Plans;

use App\Enums\Admin\Plans\PlanColor;
use App\Enums\Admin\Plans\PlanLimitsMode;
use App\Enums\Admin\Plans\PlanType;
use App\Enums\Admin\Subscriptions\SubscriptionBillingCycle;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\Subscription\Subscription;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Plan extends Model
{
    use HasFactory, SoftDeletes, ActivityLogTrait;

    protected $connection = 'mysql';

    protected $fillable = [
        'name',
        'code',
        'plan_type',
        'color',
        'description',
        'price',
        'yearly_renewal_price',
        'extra_user_license_price',
        'discount_percentage',
        'limits_mode',
        'included_users',
        'properties_limit',
        'storage_quota_gb',
        'active_auctions_limit',
        'domain_management_enabled',
        'features',
        'is_active',
        'sort_order',
        'subscription_billing_cycle',
        'subscription_duration_type',
        'available_from',
        'admin_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'price'                      => 'decimal:2',
        'yearly_renewal_price'       => 'decimal:2',
        'extra_user_license_price'   => 'decimal:2',
        'discount_percentage'        => 'float',
        'included_users'             => 'integer',
        'properties_limit'           => 'integer',
        'storage_quota_gb'           => 'integer',
        'active_auctions_limit'      => 'integer',
        'domain_management_enabled'  => 'boolean',
        'is_active'                  => 'boolean',
        'sort_order'                 => 'integer',
        'features'                   => 'array',
        'available_from'             => 'datetime',
        'plan_type'                  => PlanType::class,
        'limits_mode'                => PlanLimitsMode::class,
        'color'                      => PlanColor::class,
        'subscription_billing_cycle' => SubscriptionBillingCycle::class,
    ];

    protected static function booted(): void
    {
        static::deleting(function (Plan $plan) {
            if ($plan->subscriptions()->exists()) {
                throw new \DomainException(__('plans.errors.cannot_delete_used_plan'));
            }
        });
    }

    /*
    |============================================================================
    |                          Activity Log
    |============================================================================
    */
    public static function getLogName(): string
    {
        return 'إدارة الباقات';
    }

    public static function getModelNameArabic(): string
    {
        return 'باقة';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'name'                       => 'اسم الباقة',
            'code'                       => 'كود الباقة',
            'plan_type'                  => 'نوع الباقة',
            'color'                      => 'اللون',
            'description'                => 'الوصف',
            'price'                      => 'السعر الأساسي',
            'yearly_renewal_price'       => 'سعر العرض السنوي',
            'extra_user_license_price'   => 'سعر ترخيص المستخدم الإضافي',
            'discount_percentage'        => 'نسبة الخصم',
            'limits_mode'                => 'وضع الحدود',
            'included_users'             => 'عدد المستخدمين المشمولين',
            'properties_limit'           => 'حد العقارات',
            'storage_quota_gb'           => 'حصة التخزين (جيجابايت)',
            'active_auctions_limit'      => 'حد المزادات النشطة',
            'domain_management_enabled'  => 'إدارة الدومينات',
            'subscription_billing_cycle' => 'دورة الفوترة',
            'available_from'             => 'تاريخ الإتاحة',
            'admin_notes'                => 'ملاحظات إدارية',
            'features'                   => 'المميزات',
            'is_active'                  => 'الحالة',
            'created_by'                 => 'أنشئ بواسطة',
            'updated_by'                 => 'تم التحديث بواسطة',
        ];
    }

    public static function getValueTranslations(): array
    {
        return [
            'is_active' => [
                0 => 'غير نشط',
                1 => 'نشط',
            ],
        ];
    }

    /*
    |============================================================================
    |                               Relations
    |============================================================================
    */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'updated_by');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class, 'plan_id');
    }

    /*
    |============================================================================
    |                               Accessors & Helpers
    |============================================================================
    */
    public function getPriceAfterDiscountAttribute(): float
    {
        $discount = (float) ($this->discount_percentage ?? 0);
        $price = (float) ($this->price ?? 0);

        if ($discount <= 0) {
            return $price;
        }

        $discountAmount = ($price * $discount) / 100;

        return max(0.0, round($price - $discountAmount, 2));
    }

    public function getFeaturesAttribute($value): array
    {
        if (is_null($value)) {
            return [];
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return is_array($value) ? $value : [];
    }

    public function isUnlimited(): bool
    {
        if ($this->limits_mode instanceof PlanLimitsMode) {
            return $this->limits_mode->isUnlimited();
        }

        return (string) $this->limits_mode === 'unlimited';
    }

    public function isAvailable(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->available_from === null) {
            return true;
        }

        return $this->available_from->isPast() || $this->available_from->isToday();
    }

    public function isSelectable(): bool
    {
        return $this->is_active && $this->isAvailable();
    }

    public function hasSubscriptions(): bool
    {
        return $this->subscriptions()->exists();
    }

    /*
    |============================================================================
    |                               Scopes
    |============================================================================
    */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereNull('available_from')
              ->orWhere('available_from', '<=', now());
        });
    }

    public function scopeSelectable(Builder $query): Builder
    {
        return $query->active()->available();
    }

    public function scopeByBillingCycle(Builder $query, string|SubscriptionBillingCycle $cycle): Builder
    {
        $value = $cycle instanceof SubscriptionBillingCycle ? $cycle->value : $cycle;
        return $query->where('subscription_billing_cycle', $value);
    }

    public function scopeByPlanType(Builder $query, string|PlanType $type): Builder
    {
        $value = $type instanceof PlanType ? $type->value : $type;
        return $query->where('plan_type', $value);
    }

    public function scopeByLimitsMode(Builder $query, string|PlanLimitsMode $mode): Builder
    {
        $value = $mode instanceof PlanLimitsMode ? $mode->value : $mode;
        return $query->where('limits_mode', $value);
    }
}
