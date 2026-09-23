<?php

namespace App\Models\Admin\Coupons;

use App\Enums\Admin\Coupons\CouponDiscountType;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Coupon extends Model
{
    use HasFactory, SoftDeletes, ActivityLogTrait;

    protected $connection = 'mysql';

    protected $fillable = [
        'code',
        'name',
        'discount_type',
        'discount_value',
        'max_discount',
        'expiry_date',
        'is_active',
        'usage_limit',
        'used_count',
        'admin_user_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'discount_type'  => CouponDiscountType::class,

        'expiry_date'    => 'date',
        'is_active'      => 'boolean',
        'discount_value' => 'decimal:2',
        'max_discount'   => 'decimal:2'
    ];

    /*
    |============================================================================
    |============================================================================
    |                          Activity Log
    |============================================================================
    |============================================================================
    */
    public static function getLogName(): string
    {
        return 'إدارة الكوبونات';
    }

    public static function getModelNameArabic(): string
    {
        return 'كوبون';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'code'           => 'كود الكوبون',
            'name'           => 'اسم الكوبون',
            'discount_type'  => 'نوع الخصم',
            'discount_value' => 'قيمة الخصم',
            'max_discount'   => 'الحد الأقصى للخصم',
            'expiry_date'    => 'تاريخ الانتهاء',
            'is_active'      => 'الحالة',
            'usage_limit'    => 'حد الاستخدام',
            'used_count'     => 'عدد مرات الاستخدام',
            'created_by'     => 'أنشئ بواسطة',
            'updated_by'     => 'تم التحديث بواسطة',
        ];
    }

    public static function getIgnoredAttributes(): array
    {
        return [
            'used_count',
        ];
    }

    public static function getValueTranslations(): array
    {
        return [
            'discount_type' => [
                'fixed'      => 'خصم ثابت',
                'percentage' => 'نسبة مئوية',
            ],
            'is_active' => [
                0  => 'غير نشط',
                1  => 'نشط',
            ],
        ];
    }

    /*
    |============================================================================
    |============================================================================
    |                           Scope
    |============================================================================
    |============================================================================
    */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                    ->orWhere('expiry_date', '>=', now());
            });
    }

    public function scopeAvailable($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('usage_limit')
                ->orWhereRaw('used_count < usage_limit');
        });
    }

    /*
    |============================================================================
    |============================================================================
    |                        Helper Methods
    |============================================================================
    |============================================================================
    */
    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    // هل الوصل الحد للنهاية
    public function hasReachedLimit(): bool
    {
        return $this->usage_limit && $this->used_count >= $this->usage_limit;
    }

    public function incrementUsage(): void
    {
        $this->increment('used_count');
    }

    // حساب الخصم من المبلغ حسب هل نسبة او مبلغ
    public function calculateDiscount(float $amount): float
    {
        $discount = 0;

        if ($this->discount_type === CouponDiscountType::PERCENTAGE) {
            $discount = ($amount * $this->discount_value) / 100;

            // Apply max discount if set
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }
        } else {
            $discount = $this->discount_value;

            // Ensure discount doesn't exceed amount
            if ($discount > $amount) {
                $discount = $amount;
            }
        }

        return round($discount, 2);
    }

    public function isValid(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function admin_user()
    {
        return $this->belongsTo(AdminUser::class, 'admin_user_id');
    }
}
